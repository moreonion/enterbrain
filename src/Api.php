<?php

namespace Drupal\enterbrain;

use Drupal\campaignion_opt_in\Values;
use \Drupal\little_helpers\ArrayConfig;
use \Drupal\little_helpers\Webform\Submission;
use \Drupal\webform_paymethod_select\WebformPaymentContext;

/**
 * Class for calling the enterbrain SOAP API.
 */
class Api extends \SoapClient {

  public static $defaultConfig = [
    'endpoint' => NULL,
    'appl_id' => NULL,
    'bank_appl_id' => NULL,
    'field_map' => [
      'titel' => ['title'],
      'vorname' => ['first_name'],
      'name' => ['last_name'],
      'strasse' => ['street_name', 'street_address'],
      'hausnr' => ['street_number'],
      'adrzus1' => ['adrzus1'],
      'plz' => ['zip_code', 'postcode'],
      'ort' => ['city', 'ort'],
      'email' => ['email'],
      'tel' => ['phone_number'],
      'gebdat' => ['date_of_birth'],
      'anrede_id' => ['salutation'],
    ],
    'defaults' => [
      'project_id' => '',
      'project_name' => '',
      'wc' => '',
    ],
  ];

  // One of: E or M or V or H or J.
  public static $intervalMap = [
    '1' => 'E',
    'y' => 'J',
    's' => 'H',
    'q' => 'V',
    'm' => 'M',
  ];

  /**
   * The application ID has to be added to each API call for authentication.
   *
   * @var string
   */
  protected $appId;

  /**
   * The application ID must be added to each BrainBank call for authentication.
   *
   * @var string
   */
  protected $bankAppId;

  /**
   * Map enterbrain data ids to form_keys.
   *
   * @var array
   */
  protected $fieldMap;

  protected $defaults;

  /**
   * Static constructor to create an API object from a config array.
   */
  public static function fromConfig($config = []) {
    if (!$config) {
      $config = variable_get('enterbrain_api_config', []);
    }
    ArrayConfig::mergeDefaults($config, static::$defaultConfig);
    return new static($config['endpoint'], $config['appl_id'], $config['bank_appl_id'], $config['field_map'], $config['defaults']);
  }

  /**
   * Constructor.
   */
  public function __construct($url, $appl_id, $bank_appl_id, $field_map, $defaults) {
    $context = stream_context_create([
      'ssl' => [
        // Set some SSL/TLS specific options.
        'verify_peer' => FALSE,
        'verify_peer_name' => FALSE,
        'allow_self_signed' => TRUE,
      ],
    ]);
    parent::__construct($url, [
      'soap_version' => SOAP_1_2,
      'stream_context' => $context,
    ]);
    $this->appId = $appl_id;
    $this->bankAppId = $bank_appl_id;
    $this->fieldMap = $field_map;
    $this->defaults = $defaults;
  }

  /**
   * Add appl_id as the first parameter.
   */
  protected function call($name, $app_id, $arguments) {
    $arguments = ['appl_id' => $app_id] + $arguments;
    $response = parent::__soapCall($name, ['parameters' => $arguments]);
    return $response->{$name . 'Result'};
    if ($result->returncode < 0) {
      throw new CronError("API: {$result->errormsg} - {$result->debugstring}");
    }
    return $response;
  }

  /**
   * Generate the "sonstige_info" field.
   *
   * @param \Drupal\little_helpers\Webform\Submission $s
   *   A webform submission object.
   *
   * @return string
   *   The generated info.
   */
  public function sonstigeInfo(Submission $s) {
    $d = [
      '[wc]' => $this->defaults['wc'],
      '[Projektname]' => $this->defaults['project_name'],
      '[Projekt-Id]' => $this->defaults['project_id'],
    ];
    if (module_exists('enterbrain_fields') && !empty($s->node->field_enterbrain)) {
      $w_node = entity_metadata_wrapper('node', $s->node);
      $w_enterbrain = entity_metadata_wrapper('field_collection_item', $w_node->field_enterbrain->value());
      $d = [
        '[wc]' => $w_enterbrain->field_enterbrain_wc->value(),
        '[Projektname]' => $w_enterbrain->field_enterbrain_project_name->value(),
        '[Projekt-Id]' => $w_enterbrain->field_enterbrain_project_id->value(),
      ];
    }
    if ($s->tracking->other) {
      $d['[wc]'] = $s->tracking->other;
    }
    $d['[true|false]'] = Values::submissionHasOptIn($s, 'email') ? 'true' : 'false';

    return strtr("WCintern=[wc], Verwendungszweck: [Projektname], Newsletter: [true|false], CRM-ID: [Projekt-Id]", $d);
  }

  /**
   * Generate and send data for a single payment to Enterbrain.
   *
   * @param \Payment $payment
   *   The payment to send.
   */
  public function sendPayment(\Payment $payment) {
    if (!$payment->contextObj || !($payment->contextObj instanceof WebformPaymentContext)) {
      // We really need the node and it must have our special fields!
      throw new CronError('Can only send payments when they were made using webform_paymehod_select.');
    }
    $s = $payment->contextObj->getSubmission();

    $interval = $s->valueByKey('donation_interval');
    $interval = isset(static::$intervalMap[$interval]) ? static::$intervalMap[$interval] : 'E';

    // Pre-define the data array so we have them in the right order but can use
    // keys to assign values.
    $args = [
      'anrede_id' => '',
      'titel' => '',
      'name' => '',
      'vorname' => '',
      'strasse' => '',
      'hausnr' => '',
      'plz' => '',
      'ort' => '',
      'staat_id' => '',
      'adrzus1' => '',
      'adrzus2' => '',
      'email' => '',
      'tel' => '',
      'gebdat' => '',
      'betrag' => $payment->totalAmount(TRUE),
      'rythmus' => $interval,
      'starttermin' => date('c', $payment->getStatus()->created),
      'inhaber1' => '',
      'inhaber2' => '',
      'ktonr' => '',
      'blz' => '',
      'bic' => '',
      'iban' => '',
      'bankname' => '',
      'jzwb' => '',
      'bemerkung' => '',
      'sonstige_info' => '',
      'name_pate' => '',
      'geschenkjn' => '',
      'transnr' => '',
      'transcode' => '',
      'quelle' => '',
      'request_id' => NULL,
    ];

    // The enterbrain encoding for this field is rather odd.
    $maps['anrede_id'] = [
      // Map common salutations.
      'mr' => 2989,
      'mrs' => 2990,
      'ms' => 2990,
      // Map gender values.
      'm' => 2989,
      'f' => 2990,
    ];

    foreach ($this->fieldMap as $key => $candidates) {
      if ($value = $payment->contextObj->valueByKeys($candidates)) {
        if (isset($maps[$key][$value])) {
          $value = $maps[$key][$value];
        }
        $args[$key] = $value;
      }
    }

    $args['sonstige_info'] = $this->sonstigeInfo($s);
    drupal_alter('enterbrain_payment_data', $args, $payment, $s);
    $result = $this->call('BrainBUND_NeuerFoerderer2', $this->appId, $args);
    if ($result->returncode < 0) {
      throw new CronError("API: {$result->errormsg} - {$result->debugstring}");
    }
  }

  /**
   * Check an IBAN for validity and optional get the BIC.
   *
   * @param string $iban
   *   The string to check.
   *
   * @return array
   *   API response includes the following keys:
   *   - valid (TRUE|FALSE): Whether this is a valid key.
   *   - bic (only if available): The BIC code for this IBAN.
   *   - error (if not valid): A reason for why this IBAN is invalid.
   */
  public function checkIBAN($iban) {
    $result = $this->call('BrainBank_TestIban', $this->bankAppId, ['iban' => $iban]);
    $ret['valid'] = $result->returncode == 0;
    if ($ret['valid']) {
      $ret['bic'] = isset($result->errormsg) ? $result->errormsg : NULL;
    }
    else {
      switch ($result->returncode) {
        case 12:
          $ret['error'] = 'Invalid characters';
          break;
        case 13:
        case 22:
          $ret['error'] = 'Checksum error';
          break;
        case 25:
          $ret['error'] = 'IBAN is not from SEPA country.';
          break;
        case 26:
          $ret['error'] = 'IBAN format is not valid for this country.';
          break;
      }
    }
    return $ret;
  }

}
