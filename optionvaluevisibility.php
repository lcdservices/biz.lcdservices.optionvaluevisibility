<?php

/**
 * @file
 * Add a table of notes from related contacts.
 *
 * Copyright (C) 2013-15, AGH Strategies, LLC <info@aghstrategies.com>
 * Licensed under the GNU Affero Public License 3.0 (see LICENSE.txt)
 */

require_once 'optionvaluevisibility.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function optionvaluevisibility_civicrm_config(&$config) {
  _optionvaluevisibility_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function optionvaluevisibility_civicrm_xmlMenu(&$files) {
  _optionvaluevisibility_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function optionvaluevisibility_civicrm_install() {
  return _optionvaluevisibility_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function optionvaluevisibility_civicrm_uninstall() {
  return _optionvaluevisibility_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function optionvaluevisibility_civicrm_enable() {
  return _optionvaluevisibility_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function optionvaluevisibility_civicrm_disable() {
  return _optionvaluevisibility_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function optionvaluevisibility_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _optionvaluevisibility_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function optionvaluevisibility_civicrm_managed(&$entities) {
  return _optionvaluevisibility_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_entityTypes().
 */
function optionvaluevisibility_civicrm_entityTypes(&$entityTypes) {
  $entityTypes['CRM_Core_DAO_OptionValue']['fields_callback'][]
    = function ($class, &$fields) {
      $fields['is_visible'] = array('name' => 'is_visible',
        'type' => 16,
        'title' => 'Option Is Visible',
        'description' => 'Is this option visible?',
        'default' => 1,
        'table_name' => 'civicrm_option_value',
        'entity' => 'OptionValue',
        'bao' => 'CRM_Core_BAO_OptionValue',
        'localizable' => 0
      );
    };
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function optionvaluevisibility_civicrm_buildForm($formName, &$form) {
  if( $formName == 'CRM_Custom_Form_Field' ) {
    $numoption = CRM_Custom_Form_Field::NUM_OPTION;
    $status = array('Admin', 'Public');
    for ($i = 1; $i <= $numoption; $i++) {     
      $form->add('select', "option_visible[$i]", ts('Visible'), $status);
      $defaults['option_visible[' . $i . ']'] = 1;
    }
    $form->setDefaults($defaults);
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => "CRM/LCD/customoptionvalue.tpl"
    ));
  }
}