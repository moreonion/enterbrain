<?php

namespace Drupal\enterbrain;

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
  ];

  /**
   * The application ID has to be added to each API call for authentication.
   */
  protected $appId;
  
  /**
   * Map enterbrain data ids to form_keys.
   */
  protected $fieldMap;

  public static function fromConfig($config = []) {
    if (!$config) {
      $config = variable_get('enterbrain_api_config', []);
    }
    $config += static::$default_config;
    $config['field_map'] += static::$default_config['field_map'];
    return new static($config['endpoint'], $config['appl_id'], $config['field_map']);
  }

  public function __construct($url, $appl_id, $field_map) {
    parent::__construct($url, [
      'soap_version' => SOAP_1_2,
    ]);
    $this->appId = $appl_id;
    $this->fieldMap = $field_map;
  }
  
  /**
   * Add appl_id as the first parameter.
   */
  public function __call($name, $arguments) {
    array_unshift($arguments, $this->appId);
    parent::__call($name, $arguments);
  }

  public function sendPayment(\Payment $payment) {
    if (!$payment->contextObj || !($payment->contextObj instanceof \Drupal\webform_paymethod_select\WebformPaymentContext)) {
      // We really need the node and it must have our special fields!
      throw new CronError('Can only send payments when they were made using webform_paymehod_select.');
    }
    $s = $payment->contextObj->getSubmission();

    // Pre-define the data array so we have them in the right order but can use
    // keys to assign values.
    $args = [
      'anrede_id' => NULL,
      'titel' => NULL,
      'name' => NULL,
      'vorname' => NULL,
      'strasse' => NULL,
      'hausnr' => NULL,
      'plz' => NULL,
      'ort' => NULL,
      'staat_id' => NULL,
      'adrzus1' => NULL,
      'adrzus2' => NULL,
      'email' => NULL,
      'tel' => NULL,
      'gebdat' => NULL,
      'betrag' => $payment->totalAmount(TRUE),
      'rythmus' => NULL,
      'starttermin' => NULL,
      'inhaber1' => NULL,
      'inhaber2' => NULL,
      'ktonr' => NULL,
      'blz' => NULL,
      'bic' => NULL,
      'iban' => NULL,
      'bankname' => NULL,
      'jzwb' => NULL,
      'bemerkung' => NULL,
      'sonstige_info' => NULL,
      'name_pate' => NULL,
      'geschenkjn' => NULL,
      'transnr' => NULL,
      'transcode' => NULL,
      'quelle' => 'SEPA',
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

    $optin = FALSE;
    foreach ($s->webform->componentsByType('newsletter') as $component) {
      $value = $s->valuesByCid($component['cid']);
      if (!empty($value['subscribed'])) {
        $optin = TRUE;
        break;
      }
    }

    $args['sonstige_info'] = strtr("WCintern=[wc], Verwendungszweck: [Projektname], Newsletter: [true|false], CRM-ID: [Projekt-Id]", [
      '[wc]' => '',
      '[Projektname]' => '',
      '[true|false]' => $optin ? 'true' : 'false',
      '[Projekt-Id]' => '',
    ]);
    dd($payment, 'payment');
    dd($args, 'args');

    //call_user_func_array([$this, 'BrainBUND_NeuerFoerderer2'], $args);
  }

}
