<?php

namespace Drupal\enterbrain;

use \Drupal\little_helpers\ArrayConfig;

/**
 * Admin configuration form.
 */
class ConfigForm {

  /**
   * Form callback.
   */
  public function form(array &$element, array &$form_state) {
    $element['#tree'] = TRUE;
    $config = variable_get('enterbrain_api_config', []);
    ArrayConfig::mergeDefaults($config, Api::$default_config);

    $element['endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('API endpoint'),
      '#description' => t('This is the URL used to contact the API. It must be a valid WSDL ressource.'),
      '#required' => TRUE,
      '#default_value' => $config['endpoint'],
    ];
    $element['endpoint']['#attributes']['placeholder'] = 'https://sec.enterbrain.de/BrainSoapAuto.aspx?WSDL';

    $element['appl_id'] = [
      '#type' => 'textfield',
      '#title' => t('Application ID'),
      '#description' => t('This is the secret key used to authenticate.'),
      '#required' => TRUE,
      '#default_value' => $config['appl_id'],
    ];
    $element['appl_id']['#attributes']['placeholder'] = '{XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX}';

    $element['field_map'] = array(
      '#type' => 'fieldset',
      '#title' => t('Personal data mapping'),
      '#description' => t('This setting allows you to map data from the payment context to enterbrain fields. If data is found for one of the mapped fields it will be transferred to enterbrain. Use a comma to separate multiple field keys.'),
    );

    $map = $config['field_map'];
    foreach (self::extraDataFields() as $name => $field) {
      $default = implode(', ', isset($map[$name]) ? $map[$name] : array());
      $element['field_map'][$name] = array(
        '#type' => 'textfield',
        '#title' => $field['#title'],
        '#default_value' => $default,
      );
    }

    $element['defaults'] = [
      '#type' => 'fieldset',
      '#title' => t('EnterBrain defaults'),
      '#description' => t('These defaults will be used whenever there is no more specific configuration to be found.'),
    ];
    $element['defaults']['project_id'] = [
      '#type' => 'textfield',
      '#title' => t('Project ID'),
      '#required' => TRUE,
      '#default_value' => $config['defaults']['project_id'],
    ];
    $element['defaults']['project_name'] = [
      '#type' => 'textfield',
      '#title' => t('Project name'),
      '#required' => TRUE,
      '#default_value' => $config['defaults']['project_name'],
    ];
    $element['defaults']['wc'] = [
      '#type' => 'textfield',
      '#title' => t('Werbecode'),
      '#required' => TRUE,
      '#default_value' => $config['defaults']['wc'],
    ];

    return $element;
  }

  /**
   * Validate callback.
   */
  public function validate(array &$element, array &$form_state) {
    $config = drupal_array_get_nested_value($form_state['values'], $element['#parents']);

    $valid_appl_id = FALSE;
    if ($config['appl_id']) {
      if (!preg_match('/^\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}$/', $config['appl_id'])) {
        form_error($element['appl_id'], t('Please enter a valid Application ID. It follows the pattern {XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX}.'));
      }
      else {
        $valid_appl_id = TRUE;
      }
    }

    $valid_url = FALSE;
    if ($config['endpoint']) {
      if (FALSE && substr($config['endpoint'], 0, 8) !== 'https://') {
        form_error($element['endpoint'], t('The endpoint must be a URL starting with https://… .'));
      }
      else {
        // Make a test-request if everything seems valid so far.
        try {
          // We need to suppress PHP-warnings here.
          /*$client = @new \SoapClient($config['endpoint'], [
            'soap_version' => SOAP_1_2,
            'cache_wsdl' => WSDL_CACHE_NONE,
          ]);*/
          $valid_url = TRUE;
        }
        catch (\SoapFault $e) {
          form_error($element['endpoint'], t('Unable to contact the API server: @message', ['@message' => $e->getMessage()]));
        }
      }
    }

    if ($valid_url && $valid_appl_id) {
      try {
        $api = Api::fromConfig($config);
        $api->BrainBank_TestIban('AT861420020010952116');
        drupal_set_message(t('Successfully tested the API connection.'));
      }
      catch (\SoapFault $e) {
        form_error($element['endpoint'], t('Error while testing the API connection: @message', ['@message' => $e->getMessage()]));
      }
    }

    foreach ($config['field_map'] as $k => &$v) {
      $v = array_filter(array_map('trim', explode(',', $v)));
    }

    form_set_value($element, $config, $form_state);
  }

  /**
   * Define form elements for billing data.
   */
  public static function extraDataFields() {
    $fields = array();
    $f = array(
      'titel' => t('Title'),
      'vorname' => t('First name'),
      'name' => t('Last name'),
      'strasse' => t('Street'),
      'hausnr' => t('House number'),
      'adrzus1' => t('Address line 2'),
      'plz' => t('Postal code'),
      'ort' => t('City'),
      'email' => t('Email'),
      'tel' => t('Phone number'),
      'gebdat' => t('Day of birth'),
      'anrede_id' => t('Salutation'),
    );
    foreach ($f as $name => $title) {
      $fields[$name] = array(
        '#type' => 'textfield',
        '#title' => $title,
      );
    }

    $fields['name']['#required'] = TRUE;
    $fields['email']['#required'] = TRUE;
    $fields['anrede_id']['#type'] = 'radios';
    $fields['anrede_id']['#options'] = [2890 => t('Ms/Mrs'), 2989 => t('Mr')];
    $fields['anrede_id']['#required'] = TRUE;

    return $fields;
  }

}
