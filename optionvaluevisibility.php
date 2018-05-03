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
                'type' => CRM_Utils_Type::T_BOOLEAN,
                'title' => ts('Option Is Visible'),
                'description' => 'Is this option visible?',
                'default' => '1',
                'table_name' => 'civicrm_option_value',
                'entity' => 'OptionValue',
                'bao' => 'CRM_Core_BAO_OptionValue',
                'localizable' => 0,
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
  if( $formName == 'CRM_Custom_Form_Field' && ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE) ) {
    $numoption = CRM_Custom_Form_Field::NUM_OPTION;
    $status = array('Admin', 'Public');
    for ($i = 1; $i <= $numoption; $i++) {     
      $form->add('select', "option_visible[$i]", ts('Visible'), $status);
      $defaults['option_visible[' . $i . ']'] = 1;
    }
    if($form->getAction() == CRM_Core_Action::ADD){
      $form->setDefaults($defaults);
    }
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => "CRM/LCD/customoptionvalue.tpl"
    ));   
  }
  if( $formName == 'CRM_Custom_Form_Option' && ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE)) {
    $status = array('Admin', 'Public');
    $form->add('select', 'is_visible', ts('Visible'), $status);
    $defaults['is_visible'] = 1;
    if($form->getAction() == CRM_Core_Action::ADD){
      $form->setDefaults($defaults);
    }
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => "CRM/LCD/customoption.tpl"
    ));
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function optionvaluevisibility_civicrm_postProcess($formName, &$form) {
  if( $formName == 'CRM_Custom_Form_Field' && ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE)) {
    $params = $form->getVar('_submitValues');
    $id = $form->getVar('_id');
    $field_params = array(
      'id' => $id,
    );
    $custom_field = civicrm_api3('CustomField', 'get', $field_params);
    $params['option_group_id'] = $custom_field['values'][$id]['option_group_id'];
    if ($params['option_type'] == 1 && !empty($params['option_value']) && is_array($params['option_value'])) {
      $optionvalueParams = array(
        'option_group_id' => $params['option_group_id'],
      );
      $getOptionValue = civicrm_api3('OptionValue', 'get', $optionvalueParams);
      if(isset($getOptionValue['values']) ){
        foreach ($getOptionValue['values'] as $k => $value) {
          $optionValue = new CRM_Core_DAO_OptionValue();
          $optionValue->id = $value['id'];
          $optionValue->is_visible = CRM_Utils_Array::value($value['value'], $params['option_visible'], FALSE);
          $optionValue->save();
        }
      }
    }
  }
  
  if( $formName == 'CRM_Custom_Form_Option' && ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE)) {
    $params = $form->controller->exportValues('Option');
    $id = $form->getVar('_id');
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->id = $id;
    $optionValue->is_visible = CRM_Utils_Array::value('is_visible', $params, FALSE);
    $optionValue->save();
  }
}

/**
 * Implements hook_civicrm_fieldOptions().
 *
 */
function optionvaluevisibility_civicrm_fieldOptions($entity, $field, &$options, $params) {
  if (strpos($field, 'custom_') === 0) { //Check if it is custom field
    if (!CRM_Core_Permission::check('administer CiviCRM')) {
      $explode = explode('_', $field);
      $field_id = $explode[1];
      if(is_numeric($field_id) ) {
        $field_params = array( 'id' => $field_id);
        $custom_field = civicrm_api3('CustomField', 'get', $field_params);
        
        $option_group_id = $custom_field['values'][$field_id]['option_group_id'];
        
        $optionvalueParams = array('option_group_id' => $option_group_id);
        $getOptionValue = civicrm_api3('OptionValue', 'get', $optionvalueParams);
        foreach($getOptionValue['values'] as $key=>$value){
          $optio_value_id = $value['id'];
          if($value['is_visible'] == 0){
            $val = $value['value'];
            unset($options[$val]);
          }
        }  
      }
    }
  }
}