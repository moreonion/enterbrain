<?php

/**
 * @file
 * Hook implementations on behalf of the payone_payment module.
 */

use \Drupal\payone_payment\ControllerBase;
use \Drupal\payone_payment\CreditCardController;
use \Drupal\payone_payment\PaypalECController;
use \Drupal\payone_payment\Transaction;

/**
 * Implements hook_enterbrain_payment_data_alter().
 */
function payone_payment_enterbrain_payment_data_alter(array &$data, \Payment $payment, $submission) {
  if ($payment->method->controller instanceof ControllerBase) {
    if ($payment->method->controller instanceof CreditCardController) {
      $data['quelle'] = 'Credit';
    }
    if ($payment->method->controller instanceof PaypalECController) {
      $data['quelle'] = 'PayPal';
    }
    if ($t = Transaction::loadLastByPid($payment->pid)) {
      $data['transnr'] = $t->txid;
    }
  }
}
