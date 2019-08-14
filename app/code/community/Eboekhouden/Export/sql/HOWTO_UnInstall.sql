###
### To undo the installation of the e-Boekhouden.nl extension for Magento:
###
### First uninstall the extension using the Magento Connect Manager.
###
### If that doesn't work, remove via SSH or FTP:
### - the file:               [magento_root]/app/etc/modules/Eboekhouden_Export.xml
### - the dir with contents:  [magento_root]/app/code/community/Eboekhouden
###
###
### These are the SQL statements to undo the installation of the e-Boekhouden.nl extension for Magento.
### The statements are based on the default table names.
### If you use a prefix, or if you upgraded from an old Magento version, you may need to change the table names.
###
### Magento 1.4.x and higher:
###
DELETE FROM `catalog_eav_attribute`   WHERE `attribute_id`         IN (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` LIKE 'eboekhouden_%');
DELETE FROM `eav_entity_attribute`    WHERE `attribute_id`         IN (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` LIKE 'eboekhouden_%');
DELETE FROM `eav_attribute`           WHERE `attribute_code`       LIKE 'eboekhouden_%';
DELETE FROM `eav_attribute_group`     WHERE `attribute_group_name` = 'e-Boekhouden.nl';
DELETE FROM `core_resource`           WHERE `code`                 LIKE 'eboekhouden_%';
DELETE FROM `core_config_data`        WHERE `path`                 LIKE 'eboekhouden/%';
ALTER TABLE `sales_flat_invoice`         DROP  `eboekhouden_mutatie`;
ALTER TABLE `sales_flat_invoice_grid`    DROP  `eboekhouden_mutatie`;
ALTER TABLE `sales_flat_creditmemo`      DROP  `eboekhouden_mutatie`;
ALTER TABLE `sales_flat_creditmemo_grid` DROP  `eboekhouden_mutatie`;
ALTER TABLE `tax_calculation_rate`       DROP  `tax_ebvatcode`;
###
### Magento 1.3.x:
###
DELETE FROM `sales_order_int`            WHERE `attribute_id`         IN (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` LIKE 'eboekhouden_%');
DELETE FROM `sales_order_entity_int`     WHERE `attribute_id`         IN (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` LIKE 'eboekhouden_%');
DELETE FROM `catalog_product_entity_int` WHERE `attribute_id`         IN (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` LIKE 'eboekhouden_%');
DELETE FROM `eav_entity_attribute`       WHERE `attribute_id`         IN (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` LIKE 'eboekhouden_%');
DELETE FROM `eav_attribute`              WHERE `attribute_code`       LIKE 'eboekhouden_%';
DELETE FROM `eav_attribute_group`        WHERE `attribute_group_name` = 'e-Boekhouden.nl';
DELETE FROM `core_resource`              WHERE `code`                 LIKE 'eboekhouden_%';
DELETE FROM `core_config_data`           WHERE `path`                 LIKE 'eboekhouden/%';
###
### Now you need to flush the Magento cache.
###