<?php

/**
 * @file
 *
 * This file defines the module configuration form.
 */

use Drupal\enterbrain\ConfigForm;

/**
 * Form callback: System settings form.
 */
function enterbrain_config_form() {
  $form_obj = new ConfigForm();
  $form['enterbrain_api_config'] = [];
  $form_state = [];
  $form['enterbrain_api_config'] = $form_obj->form($form['enterbrain_api_config'], $form_state);
  $form['#form'] = $form_obj;
  return system_settings_form($form);
}

/**
 * Form validate callback: System settings form.
 */
function enterbrain_config_form_validate($form, &$form_state) {
  $form['#form']->validate($form['enterbrain_api_config'], $form_state);
}
