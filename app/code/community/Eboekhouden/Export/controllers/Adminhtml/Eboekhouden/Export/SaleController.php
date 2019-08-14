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
class Eboekhouden_Export_Adminhtml_Eboekhouden_Export_SaleController extends Mage_Adminhtml_Controller_Action
{

    public function orderExportAction()
    {
        $aOrderIds = $this->getRequest()->getPost('order_ids', array());
        $oExportModel = Mage::getModel('Eboekhouden_Export/export_sales');
        /* @var $oExportModel Eboekhouden_Export_Model_Export_Sales */
        list($iAdded, $iExist, $sErrorMsg, $sInfoMsg) = $oExportModel->exportOrders($aOrderIds);
        $this->_reportExportResult($iAdded, $iExist, $sErrorMsg, $sInfoMsg);
        if (0 == $iAdded)
        {
            $this->_getSession()->addSuccess(Mage::helper('Eboekhouden_Export')
                                                     ->__('Alleen bestellingen waar een factuur van gemaakt is worden doorgegeven aan e-Boekhouden.nl.<br />'));
        }
        $this->_redirectReferer();
    }

    public function invoiceExportAction()
    {
        $aInvoiceIds = $this->getRequest()->getPost('invoice_ids', array());
        $oExportModel = Mage::getModel('Eboekhouden_Export/export_sales');
        /* @var $oExportModel Eboekhouden_Export_Model_Export_Sales */
        list($iAdded, $iExist, $sErrorMsg, $sInfoMsg) = $oExportModel->exportInvoices($aInvoiceIds);
        $this->_reportExportResult($iAdded, $iExist, $sErrorMsg, $sInfoMsg);
        $this->_redirectReferer();
    }

    public function creditmemoExportAction()
    {
        $aCreditmemoIds = $this->getRequest()->getPost('creditmemo_ids', array());
        $oExportModel = Mage::getModel('Eboekhouden_Export/export_sales');
        /* @var $oExportModel Eboekhouden_Export_Model_Export_Sales */
        list($iAdded, $iExist, $sErrorMsg, $sInfoMsg) = $oExportModel->exportCreditmemos($aCreditmemoIds);
        $this->_reportExportResult($iAdded, $iExist, $sErrorMsg, $sInfoMsg);
        $this->_redirectReferer();
    }

    protected function _reportExportResult($iOrdersAdded, $iOrdersExist, $sErrorMsg, $sInfoMsg)
    {
        if (!empty($sInfoMsg))
        {
            $this->_getSession()->addNotice($sInfoMsg);
        }

        $iOrdersTransferred = $iOrdersAdded + $iOrdersExist;

        $sMessage = '<b>' . Mage::helper('Eboekhouden_Export')->__('Export naar e-Boekhouden') . '</b><br /><br />' . "\n";
        if (1 == $iOrdersTransferred)
        {
            $sMessage .= Mage::helper('Eboekhouden_Export')->__('1 mutatie doorgegeven');
        }
        else
        {
            $sMessage .= Mage::helper('Eboekhouden_Export')->__('%s mutaties doorgegeven', $iOrdersTransferred);
        }
        if (1 == $iOrdersExist)
        {
            $sMessage .= Mage::helper('Eboekhouden_Export')->__(', waarvan er 1 al bestond');
        }
        elseif (1 < $iOrdersExist)
        {
            $sMessage .= Mage::helper('Eboekhouden_Export')->__(', waarvan er %s al bestonden', $iOrdersExist);
        }
        $sMessage .= '.<br />' . "\n";

        if (empty($sErrorMsg))
        {
            $this->_getSession()->addSuccess($sMessage);
        }
        else
        {
            $sMessage .= '<br />' . "\n";
            $sMessage .= nl2br($sErrorMsg) . "\n";
            $this->_getSession()->addError($sMessage);
        }
        $this->_redirectReferer();
    }

}

?>
