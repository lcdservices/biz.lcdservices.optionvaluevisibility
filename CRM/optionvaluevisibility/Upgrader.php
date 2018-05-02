<?php

/**
 * Collection of upgrade steps
 */
class CRM_optionvaluevisibility_Upgrader extends CRM_optionvaluevisibility_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  public function getCurrentRevision() {
    // reset the saved extension version as well
    try {
      $xmlfile = CRM_Core_Resources::singleton()->getPath('com.optionvaluevisibility.civicrm','info.xml');
      $myxml = simplexml_load_file($xmlfile);
      $version = (string)$myxml->version;
      CRM_Core_BAO_Setting::setItem($version, 'optionvaluevisibility Extension', 'optionvaluevisibility_extension_version');
    }
    catch (Exception $e) {
      // ignore
    }
    return parent::getCurrentRevision();
  }
  /**
   * Standard: run an install sql script
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
  }

  /**
   * Standard: run an uninstall script
   */
  public function uninstall() {
   $this->executeSqlFile('sql/uninstall.sql');
  }

  public function upgrade_1_2_010() {
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
    return TRUE;
  }

  /**
   * Example: Run a simple query when a module is enabled
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }
  */

  /**
   * Example: Run a simple query when a module is disabled
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }
  */

}
