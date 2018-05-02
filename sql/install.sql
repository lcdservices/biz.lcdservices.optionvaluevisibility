-- install sql for optionvaluevisibility extension, alter table civicrm_option_value to add column

ALTER TABLE `civicrm_option_value` ADD COLUMN `is_visible` tinyint DEFAULT 1 COMMENT 'Is this option visible to all?';