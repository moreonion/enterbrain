<?php

/**
 * @file
 * Implement hooks on behalf of the manual_direct_debit module.
 */

use \Drupal\manual_direct_debit\AccountDataController;

/**
 * Implements hook_enterbrain_payment_data_alter().
 */
function manual_direct_debit_enterbrain_payment_data_alter(&$data, \Payment $payment, $submission) {
  if ($payment->method->controller instanceof AccountDataController) {
    $md = $payment->method_data;
    $data['inhaber1'] = $md['holder'];
    $data['iban'] = $md['iban'];
    $data['bic'] = $md['bic'];
    $data['quelle'] = 'SEPA';
  }
}
