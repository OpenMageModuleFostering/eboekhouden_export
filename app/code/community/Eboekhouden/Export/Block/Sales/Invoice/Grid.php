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
class Eboekhouden_Export_Block_Sales_Invoice_Grid extends Mage_Adminhtml_Block_Sales_Invoice_Grid
{
    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();
        $this->getMassactionBlock()->addItem(
            'eboekhouden',
            array('label' => Mage::helper('Eboekhouden_Export')->__('Export naar e-Boekhouden.nl'),
                 'url' => $this->getUrl('eboekhouden/export_sale/invoiceexport'))
        );
    }

    protected function _prepareColumns()
    {
        $oResult = parent::_prepareColumns();
        $this->addColumn('eboekhouden_mutatie', array(
                                                     'header' => Mage::helper('Eboekhouden_Export')->__('Mutatienummer'),
                                                     'width' => '108px',
                                                     'type' => 'number',
                                                     'index' => 'eboekhouden_mutatie',
                                                ));
        return $oResult;
    }

    protected function _prepareCollection()
    {
        $mResult = false;
        if (method_exists($this, '_getCollectionClass'))
        {
            // Magento 1.4.1.0 and higher
            $oCoreResource = Mage::getSingleton('core/resource');
            /* @var $oCoreResource Mage_Core_Model_Resource */
            $oConnection = $oCoreResource->getConnection('core_read');
            /* @var $oConnection Varien_Db_Adapter_Pdo_Mysql */
            $sGridTable = $oCoreResource->getTableName('sales/invoice_grid');
            if (!$oConnection->tableColumnExists($sGridTable, 'eboekhouden_mutatie'))
            {
                // "eboekhouden_mutatie" column is not in grid table, we need to join the invoice table
                $oCollection = Mage::getResourceModel($this->_getCollectionClass());
                /* @var $oCollection Mage_Sales_Model_Mysql4_Order_Invoice_Grid_Collection  */
                $oCollection->join('invoice', 'main_table.entity_id = invoice.entity_id', 'eboekhouden_mutatie');
                parent::setCollection($oCollection);
                $mResult = Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
            }
        }
        if (empty($mResult))
        {
            $mResult = parent::_prepareCollection();
        }
        return $mResult;
    }

    public function setCollection($oCollection)
    {
        if (!method_exists($this, '_getCollectionClass'))
        {
            # Magento 1.4.0.0 and older
            $oCollection->addAttributeToSelect('eboekhouden_mutatie');
        }
        return parent::setCollection($oCollection);
    }

}

?>