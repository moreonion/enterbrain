<?php

/**
 * @file
 * Documents hooks invoked by this module.
 *
 * Code in this file servers only documentation purposes and is never executed.
 */

use \Drupal\little_helpers\Submission;

/**
 * Let payment providers add their data to the data sent to entebrain.
 *
 * @param array $data
 *   Reference to the data array.
 * @param \Payment $payment
 *   The payment whichs data is sent to enterbrain.
 * @param \Drupal\little_helpers\Submission $submission
 *   The webform submission this donation was made on.
 */
function hook_enterbrain_payment_data_alter(array &$data, \Payment $payment, Submission $submission) {
  $data['quelle'] = 'MyPaymentProvider';
}
