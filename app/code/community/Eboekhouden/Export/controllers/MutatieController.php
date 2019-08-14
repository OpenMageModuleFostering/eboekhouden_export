<?php

// index.php/eboekhouden/mutatie/reset_all/key/XXXXXXXXXX/
class Eboekhouden_Export_MutatieController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $oSession = Mage::getSingleton('core/session');
        /* @var $oSession Mage_Core_Model_Session */
        $oSession->addNotice(Mage::helper('Eboekhouden_Export')->__('Ongeldige actie.'));
        $this->_redirect('adminhtml/index');
    }

    public function reset_allAction()
    {
        $oSession = Mage::getSingleton('core/session');
        /* @var $oSession Mage_Core_Model_Session */

        $oCoreResource = Mage::getSingleton('core/resource');
        /* @var $oCoreResource Mage_Core_Model_Resource */
        $oConnection = $oCoreResource->getConnection('core_write');
        /* @var $oConnection Varien_Db_Adapter_Pdo_Mysql */

        $iUpdated = 0;

        $aDbTables = $oConnection->listTables();
        $aQueries = array();

        # Magento 1.4.x + 1.5.x
        $aQueries['sales_flat_invoice']['eboekhouden_mutatie']         = 'UPDATE `'.$oCoreResource->getTableName('sales_flat_invoice').'`          SET `eboekhouden_mutatie`=NULL';
        $aQueries['sales_flat_invoice_grid']['eboekhouden_mutatie']    = 'UPDATE `'.$oCoreResource->getTableName('sales_flat_invoice_grid').'`     SET `eboekhouden_mutatie`=NULL';
        $aQueries['sales_flat_creditmemo']['eboekhouden_mutatie']      = 'UPDATE `'.$oCoreResource->getTableName('sales_flat_creditmemo').'`       SET `eboekhouden_mutatie`=NULL';
        $aQueries['sales_flat_creditmemo_grid']['eboekhouden_mutatie'] = 'UPDATE `'.$oCoreResource->getTableName('sales_flat_creditmemo_grid').'`  SET `eboekhouden_mutatie`=NULL';
        # Magento 1.3.x
        $aQueries['sales_order_entity_int']['attribute_id'] = 'DELETE FROM `'.$oCoreResource->getTableName('sales_order_entity_int').'`  WHERE `attribute_id` IN (SELECT `attribute_id` FROM `'.$oCoreResource->getTableName('eav_attribute').'` WHERE `attribute_code` = "eboekhouden_mutatie")';

        foreach ($aQueries as $sTable => $aFields)
        {
            if (in_array($sTable, $aDbTables))
            {
                foreach ($aFields as $sField => $sSQL)
                {
                    if ($oConnection->tableColumnExists($sTable, $sField))
                    {
                        $oResult = $oConnection->query($sSQL);
                        /* @var $oResult Zend_Db_Statement_Pdo */
                        $iUpdated += $oResult->rowCount();
                    }
                }
            }
        }

        $oSession->addNotice(Mage::helper('Eboekhouden_Export')
                                     ->__('Alle mutatie nummers zijn gereset, %d rijen aangepast.', $iUpdated));

        $this->_redirect('adminhtml/sales_invoice/index');
    }
}
