<?php
/**
 * NOTICE OF LICENSE
 *
 * The MIT License
 *
 * Copyright (c) 2012 e-Boekhouden.nl
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    Eboekhouden_Export
 * @copyright  Copyright (c) 2012 e-Boekhouden.nl
 * @license    http://opensource.org/licenses/mit-license.php  The MIT License
 * @author     e-Boekhouden.nl
 */

$oInstaller    = $this;
/* @var $oInstaller Mage_Core_Model_Resource_Setup */
$oCoreSetup    = new Mage_Eav_Model_Entity_Setup('core_setup');
$oSalesSetup   = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$oSession      = Mage::getSingleton('core/session');
/* @var $oSession Mage_Core_Model_Session */
$oCoreResource = Mage::getSingleton('core/resource');
/* @var $oCoreResource Mage_Core_Model_Resource */

$iStoreId = 0;

$oInstaller->startSetup();

$oConnection = $oInstaller->getConnection(); /* @var $oConnection Varien_Db_Adapter_Pdo_Mysql */

$aProductGbrekSet = array('group' => 'e-Boekhouden.nl'
                         , 'label' => 'Grootboekrek. e-Boekhouden.nl'
                         , 'position' => 9990
                         , 'sort_order' => 9990
                         , 'type' => 'int'
                         , 'input' => 'select'
                         , 'default' => 8000
                         , 'source' => 'Eboekhouden_Export/product_attribute_ledgeraccount'
                         , 'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE
                         , 'searchable' => true
                         , 'filterable' => true
                         );
$oCoreSetup->addAttribute('catalog_product', 'eboekhouden_grootboekrekening', $aProductGbrekSet);
$aProductCostCenterSet = array('group' => 'e-Boekhouden.nl'
                              , 'label' => 'Kostenplaats e-Boekhouden.nl'
                              , 'position' => 9995
                              , 'sort_order' => 9995
                              , 'type' => 'int'
                              , 'input' => 'select'
                              , 'default' => 0
                              , 'source' => 'Eboekhouden_Export/product_attribute_costcenter'
                              , 'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE
                              , 'searchable' => true
                              , 'filterable' => true
                              );
$oCoreSetup->addAttribute('catalog_product', 'eboekhouden_costcenter', $aProductCostCenterSet);

$aOrderInvoiceSet = array('label' => 'Mutatie e-Boekhouden.nl'
                         , 'position' => 9999
                         , 'sort_order' => 9999
                         , 'type' => 'int'
                         , 'input' => 'text'
                         , 'default' => null
                         , 'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE
                         , 'searchable' => true
                         , 'filterable' => true
                         , 'grid' => true
                         );
$oSalesSetup->addAttribute('invoice', 'eboekhouden_mutatie', $aOrderInvoiceSet);
$oSalesSetup->addAttribute('creditmemo', 'eboekhouden_mutatie', $aOrderInvoiceSet);

### Clean up order attribute 'eboekhouden_grootboekrekening' from previous version of this module to prevent errors
$oEntitySelect = $oConnection->select('entity_type_id')->from($oCoreResource->getTableName('eav_entity_type'))->where('entity_type_code = "order"');
$iOrderEntityTypeId = $oConnection->fetchOne($oEntitySelect);
$oConnection->query('DELETE FROM `'.$oCoreResource->getTableName('eav_attribute').'` WHERE `entity_type_id`=? AND `attribute_code` = "eboekhouden_mutatie"',
                    $iOrderEntityTypeId);
###

$sTaxCalcRateTable = $oInstaller->getTable('tax/tax_calculation_rate');
$sTaxCalcRateField = 'tax_ebvatcode';
if (!$oConnection->tableColumnExists($sTaxCalcRateTable, $sTaxCalcRateField))
{
    $oConnection->addColumn($sTaxCalcRateTable, $sTaxCalcRateField, 'VARCHAR(20) NULL');
    if ( method_exists($oConnection,'resetDdlCache') )
    {
        $oConnection->resetDdlCache($sTaxCalcRateTable);
    }
}

// Try to convert old vat code settings from configuration to new settings via calculation_rate
$bVatUpdated = false;
$oTaxSelect = $oConnection->select();
/* @var $oTaxSelect Zend_Db_Select */
$oTaxSelect->from($this->getTable('core/config_data'), array('path', 'value'));
$oTaxSelect->where('path LIKE ?', '%eboekhouden/vatcodes/vatcode_%');
$aVatConfig = $oConnection->fetchAll($oTaxSelect);
if (!empty($aVatConfig))
{
    foreach ($aVatConfig as $aConfigRow)
    {
        $sConfigKey = $aConfigRow['path'];
        $sMagCode = $aConfigRow['value'];
        $aMatch = array();
        if (preg_match('/vatcode_(\w+)$/', $sConfigKey, $aMatch))
        {
            if (!empty($sMagCode))
            {
                $sEBVatCode = $aMatch[1];
                $oRateModel = Mage::getModel('tax/calculation_rate')->load($sMagCode, 'code');
                /* @var $oRateModel Mage_Tax_Model_Calculation_Rate */
                if (!$oRateModel->isEmpty())
                {
                    $sCurEBVatCode = $oRateModel->getData("tax_ebvatcode");
                    if (empty($sCurEBVatCode))
                    {
                        // no current EB vatcode set
                        $oRateModel->setData("tax_ebvatcode", $sEBVatCode);
                        $oRateModel->save();
                        $bVatUpdated = true;
                        $oInstaller->deleteConfigData('eboekhouden/vatcodes/vatcode_' . $sEBVatCode);
                    }
                }
            }
        }
    }
}
if ($bVatUpdated)
{
    $oSession->addNotice(Mage::helper('Eboekhouden_Export')->__('e-Boekhouden.nl BTW-code instellingen zijn geconverteerd.'));
}


////// Model names update:   eboekhouden/xxx   ->  Eboekhouden_Export/xxx

$iUpdated = $oConnection->update( $oCoreResource->getTableName('eav_attribute')
                                , array( 'source_model' => new Zend_Db_Expr('REPLACE(`source_model`,"eboekhouden/","Eboekhouden_Export/")') )
                                , '`attribute_code` = "eboekhouden_%"'
                                , array()
                                );
if ($iUpdated)
{
    $oSession->addNotice(Mage::helper('Eboekhouden_Export')->__('e-Boekhouden.nl database upgrade uitgevoerd (model names).'));
}
///////

$oInstaller->endSetup();

Mage::app()->cleanCache();

$oSession->addNotice(Mage::helper('Eboekhouden_Export')->__('De installatie van de e-Boekhouden.nl extensie is voltooid.'
                                                    , Eboekhouden_Export_Model_Info::getExtensionVersion())
                                                    );
$oSession->addNotice(Mage::helper('Eboekhouden_Export')->__('Gelieve de Magento Cache te flushen en daarna opnieuw in te loggen.'));
