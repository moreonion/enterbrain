<?php

namespace Drupal\enterbrain;

use \Drupal\little_helpers\ArrayConfig;
use \Drupal\little_helpers\Webform\Submission;

class Api extends \SoapClient {

  public static $default_config = [
    'endpoint' => NULL,
    'appl_id' => NULL,
    'field_map' => [
      'titel' => ['title'],
      'vorname' => ['first_name'],
      'name' => ['last_name'],
      'strasse' => ['street', 'street_address'],
      'hausnr' => ['house_number'],
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

  // E or M or V or H or J
  public static $interval_map = [
    '1' => 'E',
    'y' => 'J',
    'm' => 'M',
  ];

  /**
   * The application ID has to be added to each API call for authentication.
   */
  protected $appId;
  
  /**
   * Map enterbrain data ids to form_keys.
   */
  protected $fieldMap;

  protected $defaults;

  public static function fromConfig($config = []) {
    if (!$config) {
      $config = variable_get('enterbrain_api_config', []);
    }
    ArrayConfig::mergeDefaults($config, static::$default_config);
    return new static($config['endpoint'], $config['appl_id'], $config['field_map'], $config['defaults']);
  }

  public function __construct($url, $appl_id, $field_map, $defaults) {
    $context = stream_context_create([
      'ssl' => [
        // set some SSL/TLS specific options
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
      ],
    ]);
    parent::__construct($url, [
      'soap_version' => SOAP_1_2,
      'stream_context' => $context,
    ]);
    $this->appId = $appl_id;
    $this->fieldMap = $field_map;
    $this->defaults = $defaults;
  }
  
  /**
   * Add appl_id as the first parameter.
   */
  protected function call($name, $arguments) {
    $arguments = ['appl_id' => $this->appId] + $arguments;
    try {
      $response = parent::__soapCall($name, ['parameters' => $arguments]);
    }
    $result = $response[$name . 'Result'];
    if ($result->returncode < 0) {
      throw new CronError("API: {$result->errormsg} - {$result->debugstring}");
    }
    return $response;
  }

  public function sonstigeInfo(Submission $s) {
    $optin = FALSE;
    foreach ($s->webform->componentsByType('newsletter') as $component) {
      $value = $s->valuesByCid($component['cid']);
      if (!empty($value['subscribed'])) {
        $optin = TRUE;
        break;
      }
    }


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
    $d['[true|false]'] = $optin ? 'true' : 'false';

    return strtr("WCintern=[wc], Verwendungszweck: [Projektname], Newsletter: [true|false], CRM-ID: [Projekt-Id]", $d);
  }

  public function sendPayment(\Payment $payment) {
    if (!$payment->contextObj || !($payment->contextObj instanceof \Drupal\webform_paymethod_select\WebformPaymentContext)) {
      // We really need the node and it must have our special fields!
      throw new CronError('Can only send payments when they were made using webform_paymehod_select.');
    }
    $s = $payment->contextObj->getSubmission();

    $interval = $s->valueByKey('donation_interval');
    $interval = isset(static::$interval_map[$interval]) ? static::$interval_map[$interval] : 'E';

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

    if ($payment->method->controller instanceof \Drupal\manual_direct_debit\AccountDataController) {
      $md = $payment->method_data;
      $args['inhaber1'] = $md['holder'];
      $args['iban'] = $md['iban'];
      $args['bic'] = $md['bic'];
      $args['quelle'] = 'SEPA';
    }

    $args['sonstige_info'] = $this->sonstigeInfo($s);
    $this->call('BrainBUND_NeuerFoerderer2', $args);
  }

}
