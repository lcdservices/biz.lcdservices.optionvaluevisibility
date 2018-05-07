<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 */

/**
 * This class contains the functions that are called using AJAX (jQuery)
 */
class CRM_optionvaluevisibility_Custom_Page_AJAX {

  /**
   * This function uses the deprecated v1 datatable api and needs updating. See CRM-16353.
   * @deprecated
   */
  public static function getOptionList() {
    $params = $_REQUEST;

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $options = self::getOptionListSelector($params);

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'label',
      'value',
      'is_default',
      'is_visible',
      'is_active',
      'links',
      'class',
    );

    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($options, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Fix Ordering of options
   *
   */
  public static function fixOrdering() {
    $params = $_REQUEST;

    $queryParams = array(
      1 => array($params['start'], 'Integer'),
      2 => array($params['end'], 'Integer'),
      3 => array($params['gid'], 'Integer'),
    );
    $dao = "SELECT id FROM civicrm_option_value WHERE weight = %1 AND option_group_id = %3";
    $startid = CRM_Core_DAO::singleValueQuery($dao, $queryParams);

    $dao2 = "SELECT id FROM civicrm_option_value WHERE weight = %2 AND option_group_id = %3";
    $endid = CRM_Core_DAO::singleValueQuery($dao2, $queryParams);

    $query = "UPDATE civicrm_option_value SET weight = %2 WHERE id = $startid";
    CRM_Core_DAO::executeQuery($query, $queryParams);

    // increment or decrement the rest by one
    if ($params['start'] < $params['end']) {
      $updateRows = "UPDATE civicrm_option_value
                  SET weight = weight - 1
                  WHERE weight > %1 AND weight < %2 AND option_group_id = %3
                  OR id = $endid";
    }
    else {
      $updateRows = "UPDATE civicrm_option_value
                  SET weight = weight + 1
                  WHERE weight < %1 AND weight > %2 AND option_group_id = %3
                  OR id = $endid";
    }
    CRM_Core_DAO::executeQuery($updateRows, $queryParams);
    CRM_Utils_JSON::output(TRUE);
  }

  /**
   * Get list of Multi Record Fields.
   *
   */
  public static function getMultiRecordFieldList() {

    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams(0, 10);
    $params['cid'] = CRM_Utils_Type::escape($_GET['cid'], 'Integer');
    $params['cgid'] = CRM_Utils_Type::escape($_GET['cgid'], 'Integer');

    $contactType = CRM_Contact_BAO_Contact::getContactType($params['cid']);

    $obj = new CRM_Profile_Page_MultipleRecordFieldsListing();
    $obj->_pageViewType = 'customDataView';
    $obj->_contactId = $params['cid'];
    $obj->_customGroupId = $params['cgid'];
    $obj->_contactType = $contactType;
    $obj->_DTparams['offset'] = ($params['page'] - 1) * $params['rp'];
    $obj->_DTparams['rowCount'] = $params['rp'];
    if (!empty($params['sortBy'])) {
      $obj->_DTparams['sort'] = $params['sortBy'];
    }

    list($fields, $attributes) = $obj->browse();

    // format params and add class attributes
    $fieldList = array();
    foreach ($fields as $id => $value) {
      $field = array();
      foreach ($value as $fieldId => &$fieldName) {
        if (!empty($attributes[$fieldId][$id]['class'])) {
          $fieldName = array('data' => $fieldName, 'cellClass' => $attributes[$fieldId][$id]['class']);
        }
        if (is_numeric($fieldId)) {
          $fName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $fieldId, 'column_name');
          CRM_Utils_Array::crmReplaceKey($value, $fieldId, $fName);
        }
      }
      $field = $value;
      array_push($fieldList, $field);
    }
    $totalRecords = !empty($obj->_total) ? $obj->_total : 0;

    $multiRecordFields = array();
    $multiRecordFields['data'] = $fieldList;
    $multiRecordFields['recordsTotal'] = $totalRecords;
    $multiRecordFields['recordsFiltered'] = $totalRecords;

    if (!empty($_GET['is_unit_test'])) {
      return $multiRecordFields;
    }

    CRM_Utils_JSON::output($multiRecordFields);
  }
  
  /**
   * Wrapper for ajax option selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of option list
   *   -rp = rowcount
   *   -page= offset
   */
  static public function getOptionListSelector(&$params) {
    $options = array();

    $field = CRM_Core_BAO_CustomField::getFieldObject($params['fid']);
    $defVal = CRM_Utils_Array::explodePadded($field->default_value);

    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];

    if (!$field->option_group_id) {
      return $options;
    }
    $queryParams = array(1 => array($field->option_group_id, 'Integer'));
    $total = "SELECT COUNT(*) FROM civicrm_option_value WHERE option_group_id = %1";
    $params['total'] = CRM_Core_DAO::singleValueQuery($total, $queryParams);

    $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    $orderBy = ' ORDER BY options.weight asc';

    $query = "SELECT * FROM civicrm_option_value as options WHERE option_group_id = %1 {$orderBy} {$limit}";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $links = CRM_Custom_Page_Option::actionLinks();

    $fields = array('id', 'label', 'value');
    $config = CRM_Core_Config::singleton();
    while ($dao->fetch()) {
      $options[$dao->id] = array();
      foreach ($fields as $k) {
        $options[$dao->id][$k] = $dao->$k;
      }
      $action = array_sum(array_keys($links));
      $class = 'crm-entity';
      // update enable/disable links depending on custom_field properties.
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $class .= ' disabled';
        $action -= CRM_Core_Action::DISABLE;
      }
      if (in_array($field->html_type, array('CheckBox', 'AdvMulti-Select', 'Multi-Select'))) {
        if (isset($defVal) && in_array($dao->value, $defVal)) {
          $options[$dao->id]['is_default'] = '<img src="' . $config->resourceBase . 'i/check.gif" />';
        }
        else {
          $options[$dao->id]['is_default'] = '';
        }
      }
      else {
        if ($field->default_value == $dao->value) {
          $options[$dao->id]['is_default'] = '<img src="' . $config->resourceBase . 'i/check.gif" />';
        }
        else {
          $options[$dao->id]['is_default'] = '';
        }
      }

      $options[$dao->id]['class'] = $dao->id . ',' . $class;
      $is_visible = ts('Public');
      if($dao->is_visible ==1 ){
        $is_visible = ts('Public');
      }
      else{
        $is_visible = ts('Admin');
      }
      $options[$dao->id]['is_visible'] = $is_visible;
      $options[$dao->id]['is_active'] = empty($dao->is_active) ? ts('No') : ts('Yes');
      $options[$dao->id]['links'] = CRM_Core_Action::formLink($links,
          $action,
          array(
            'id' => $dao->id,
            'fid' => $params['fid'],
            'gid' => $params['gid'],
          ),
          ts('more'),
          FALSE,
          'customOption.row.actions',
          'customOption',
          $dao->id
        );
    }

    return $options;
  }

}
