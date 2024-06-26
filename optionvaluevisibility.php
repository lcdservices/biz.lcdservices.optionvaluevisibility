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
 * Implementation of hook_civicrm_install
 */
function optionvaluevisibility_civicrm_install() {
  return _optionvaluevisibility_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 */
function optionvaluevisibility_civicrm_enable() {
  return _optionvaluevisibility_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_entityTypes().
 */
function optionvaluevisibility_civicrm_entityTypes(&$entityTypes) {
  $entityTypes['CRM_Core_DAO_OptionValue']['fields_callback'][]
    = function ($class, &$fields) {
      $fields['is_visible'] = [
        'name' => 'is_visible',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('Option Is Visible'),
        'description' => 'Is this option visible?',
        'default' => '1',
        'table_name' => 'civicrm_option_value',
        'entity' => 'OptionValue',
        'bao' => 'CRM_Core_BAO_OptionValue',
        'localizable' => 0,
      ];
    };
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function optionvaluevisibility_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Custom_Form_Field' &&
    ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE)
  ) {
    $numoption = CRM_Custom_Form_Field::NUM_OPTION;
    $status = ['Admin', 'Public'];
    for ($i = 1; $i <= $numoption; $i++) {
      $form->add('select', "option_visible[$i]", ts('Visible'), $status);
      $defaults['option_visible[' . $i . ']'] = 1;
    }

    if ($form->getAction() == CRM_Core_Action::ADD){
      $form->setDefaults($defaults);
    }

    CRM_Core_Region::instance('page-body')->add([
      'template' => "CRM/LCD/customoptionvalue.tpl"
    ]);
  }

  if ($formName == 'CRM_Custom_Form_Option' &&
    ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE)
  ) {
    $status = ['Admin', 'Public'];
    $form->add('select', 'is_visible', ts('Visible'), $status);
    $defaults['is_visible'] = 1;

    if ($form->getAction() == CRM_Core_Action::ADD){
      $form->setDefaults($defaults);
    }

    CRM_Core_Region::instance('page-body')->add([
      'template' => "CRM/LCD/customoption.tpl"
    ]);
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function optionvaluevisibility_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Custom_Form_Field' &&
    ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE)
  ) {
    $params = $form->getVar('_submitValues');
    $id = $form->getVar('_id');
    $custom_field = civicrm_api3('CustomField', 'get', ['id' => $id]);
    $params['option_group_id'] = $custom_field['values'][$id]['option_group_id'];

    if ($params['option_type'] == 1 &&
      !empty($params['option_value']) &&
      is_array($params['option_value'])
    ) {
      $getOptionValue = civicrm_api3('OptionValue', 'get', ['option_group_id' => $params['option_group_id']]);

      if (isset($getOptionValue['values']) ){
        foreach ($getOptionValue['values'] as $k => $value) {
          $optionValue = new CRM_Core_DAO_OptionValue();
          $optionValue->id = $value['id'];
          $optionValue->is_visible = CRM_Utils_Array::value($value['value'], $params['option_visible'], FALSE);
          $optionValue->save();
        }
      }
    }
  }

  if ($formName == 'CRM_Custom_Form_Option' &&
    ($form->getAction() == CRM_Core_Action::ADD || $form->getAction() == CRM_Core_Action::UPDATE)
  ) {
    $params = $form->getVar('_submitValues');
    $option_group_id = $form->getVar('_optionGroupID');

    $getOptionValue = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => $option_group_id,
      'value' => $params['value'],
    ]);

    if (isset($getOptionValue['values']) ){
      foreach ($getOptionValue['values'] as $k => $value) {
        $optionValue = new CRM_Core_DAO_OptionValue();
        $optionValue->id = $value['id'];
        $optionValue->is_visible = $params['is_visible'];
        $optionValue->save();
      }
    }
  }
}

/**
 * Implements hook_civicrm_fieldOptions().
 *
 */
function optionvaluevisibility_civicrm_fieldOptions($entity, $field, &$options, $params) {
  //Check if it is custom field
  if (strpos($field, 'custom_') === 0) {
    $urlPath = CRM_Utils_System::currentPath();
    $menu = CRM_Core_Menu::get(trim($urlPath, '/'));

    if (!empty($menu['is_public'])) {
      $explode = explode('_', $field);
      $field_id = $explode[1];
      if (is_numeric($field_id) ) {
        try {
          $field_params = ['id' => $field_id];
          $custom_field = civicrm_api3('CustomField', 'get', $field_params);
          $option_group_id = $custom_field['values'][$field_id]['option_group_id'];

          if (!empty($option_group_id)) {
            $getOptionValue = civicrm_api3('OptionValue', 'get', [
              'option_group_id' => $option_group_id,
              'options' => ['limit' => 0]
            ]);

            foreach ($getOptionValue['values'] as $key => $value) {
              if ($value['is_visible'] == 0) {
                $val = $value['value'];
                unset($options[$val]);
              }
            }
          }
        }
        catch (CiviCRM_API3_Exception $e) {}
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_alterTemplateFile
 */
function optionvaluevisibility_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {
  if ($formName == 'CRM_Custom_Page_Option' && $context == 'page'){
    $possibleTpl = 'CRM/LCD/Custom/Page/Option.tpl';
    $template = CRM_Core_Smarty::singleton();

    if ($template->template_exists($possibleTpl)) {
      $tplName = $possibleTpl;
    }
  }
}
/**
 * Implementation of hook_civicrm_alterMenu
 */
function optionvaluevisibility_civicrm_alterMenu(&$items) {
  $items['civicrm/ajax/optionlist'] = [
    'page_callback' => 'CRM_optionvaluevisibility_Custom_Page_AJAX::getOptionList',
  ];
}
