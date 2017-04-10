<?php

/**
 * @file
 * Hook implementations on behalf of the payone_payment module.
 */

use \Drupal\payone_payment\ControllerBase;
use \Drupal\payone_payment\Transaction;

/**
 * Implements hook_enterbrain_payment_data_alter().
 */
function payone_enterbrain_payment_data_alter(array &$data, \Payment $payment, $submission) {
  $data['quelle'] = 'PayOne';
  if ($payment->method->controller instanceof ControllerBase) {
    if ($t = Transaction::loadByPid($payment->pid)) {
      $data['transnr'] = $t->txid;
    }
  }
}
