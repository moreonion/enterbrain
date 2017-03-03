<?php
/**
 * @file
 * enterbrain_fields.features.field_instance.inc
 */

/**
 * Implements hook_field_default_field_instances().
 */
function enterbrain_fields_field_default_field_instances() {
  $field_instances = array();

  // Exported field_instance:
  // 'field_collection_item-field_enterbrain-field_enterbrain_project_id'.
  $field_instances['field_collection_item-field_enterbrain-field_enterbrain_project_id'] = array(
    'bundle' => 'field_enterbrain',
    'default_value' => NULL,
    'deleted' => 0,
    'description' => '',
    'display' => array(
      'default' => array(
        'label' => 'hidden',
        'settings' => array(),
        'type' => 'hidden',
        'weight' => 0,
      ),
    ),
    'entity_type' => 'field_collection_item',
    'field_name' => 'field_enterbrain_project_id',
    'label' => 'Project ID',
    'required' => 1,
    'settings' => array(
      'text_processing' => 0,
      'user_register_form' => FALSE,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'text',
      'settings' => array(
        'size' => 60,
      ),
      'type' => 'text_textfield',
      'weight' => 1,
    ),
  );

  // Exported field_instance:
  // 'field_collection_item-field_enterbrain-field_enterbrain_project_name'.
  $field_instances['field_collection_item-field_enterbrain-field_enterbrain_project_name'] = array(
    'bundle' => 'field_enterbrain',
    'default_value' => NULL,
    'deleted' => 0,
    'description' => '',
    'display' => array(
      'default' => array(
        'label' => 'hidden',
        'settings' => array(),
        'type' => 'hidden',
        'weight' => 1,
      ),
    ),
    'entity_type' => 'field_collection_item',
    'field_name' => 'field_enterbrain_project_name',
    'label' => 'Project Name',
    'required' => 1,
    'settings' => array(
      'text_processing' => 0,
      'user_register_form' => FALSE,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'text',
      'settings' => array(
        'size' => 60,
      ),
      'type' => 'text_textfield',
      'weight' => 2,
    ),
  );

  // Exported field_instance:
  // 'field_collection_item-field_enterbrain-field_enterbrain_wc'.
  $field_instances['field_collection_item-field_enterbrain-field_enterbrain_wc'] = array(
    'bundle' => 'field_enterbrain',
    'default_value' => NULL,
    'deleted' => 0,
    'description' => '',
    'display' => array(
      'default' => array(
        'label' => 'hidden',
        'settings' => array(),
        'type' => 'hidden',
        'weight' => 2,
      ),
    ),
    'entity_type' => 'field_collection_item',
    'field_name' => 'field_enterbrain_wc',
    'label' => 'Werbecode',
    'required' => 1,
    'settings' => array(
      'text_processing' => 0,
      'user_register_form' => FALSE,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'text',
      'settings' => array(
        'size' => 60,
      ),
      'type' => 'text_textfield',
      'weight' => 3,
    ),
  );

  // Translatables
  // Included for use with string extractors like potx.
  t('Enterbrain Project ID');
  t('Enterbrain Project Name');
  t('Enterbrain Werbecode');

  return $field_instances;
}
