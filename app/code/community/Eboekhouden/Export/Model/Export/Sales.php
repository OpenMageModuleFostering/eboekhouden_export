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
class Eboekhouden_Export_Model_Export_Sales
{
    /* @var $oTaxConfig Mage_Tax_Model_Config */
    protected $_oTaxConfig;
    /* @var $oWeeeHelper Mage_Weee_Helper_Data */
    protected $_oWeeeHelper;

    public function __construct()
    {
        $this->_oTaxConfig  = Mage::getSingleton('tax/config');
        $this->_oWeeeHelper = Mage::helper('weee');
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        switch ($errno)
        {
            case E_USER_ERROR:
                echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
                echo "  Fatal error on line $errline in file $errfile";
                echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                echo "Aborting...<br />\n";
                exit(1);
                break;

            case E_USER_WARNING:
                echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
                break;

            case E_USER_NOTICE:
                echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
                break;

            default:
                echo "Unknown error type: [$errno] $errstr<br />\n";
                break;
        }

        /* Don't execute PHP internal error handler */
        return true;
    }

    /**
     * Export invoices connected to given order ids to e-Boekhouden.nl.
     *
     * @param $aOrderIds List of order ids to export.
     * @return array with values ($iCountAdded,$iCountExist,$sErrorMsg,$sInfoMsg)
     */
    public function exportOrders($aOrderIds)
    {
        // To enable printing warnings to Apache errorlog set in .htaccess:  SetEnv EB_DEBUG 1
        if (!empty($_SERVER["EB_DEBUG"]))
        {
            $iErrorOld = error_reporting();
            $bErrorDisplayOld = ini_get('display_errors');
            error_reporting(E_ALL | E_STRICT & ~E_DEPRECATED);
            ini_set('display_errors', 0);
            restore_error_handler();
        }

        $sErrorMsg = '';
        $sInfoMsg = '';
        $iCountAdded = 0;
        $iCountExist = 0;

        sort($aOrderIds);
        foreach ($aOrderIds as $sOrderId)
        {
            $oOrder = Mage::getModel('sales/order')->load($sOrderId);
            /* @var $oOrder Mage_Sales_Model_Order */
            $oInvoiceColl = $oOrder->getInvoiceCollection();
            /* @var $oInvoiceColl Mage_Sales_Model_Mysql4_Order_Invoice_Collection */
            $aInvoiceIds = $oInvoiceColl->getAllIds();
            list($iThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->exportInvoices($aInvoiceIds);
            $iCountAdded += $iThisAdded;
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;
        }

        if (!empty($_SERVER["EB_DEBUG"]))
        {
            error_reporting($iErrorOld);
            set_error_handler('mageCoreErrorHandler');
            ini_set('display_errors', $bErrorDisplayOld);
        }
        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);
    }

    /**
     * Export given invoices ids to e-Boekhouden.nl.
     * The creditmemo's connected to the orders of the invoices will also be exported
     *
     * @param $aInvoiceIds List of invoice ids to export.
     * @return array with values ($iCountAdded,$iCountExist,$sErrorMsg,$sInfoMsg)
     */
    public function exportInvoices($aInvoiceIds)
    {
        $sErrorMsg = '';
        $sInfoMsg = '';
        $iCountAdded = 0;
        $iCountExist = 0;

        sort($aInvoiceIds);
        foreach ($aInvoiceIds as $sInvoiceId)
        {
            $oInvoice = Mage::getModel('sales/order_invoice')->load($sInvoiceId);
            /* @var $oInvoice Mage_Sales_Model_Order_Invoice */
            list($iThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->_exportObject($oInvoice);
            $iCountAdded += $iThisAdded;
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;
        }
        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);
    }


    /**
     * Export given creditmemo ids to e-Boekhouden.nl.
     *
     * @param $aCreditmemoIds List of creditmemo ids to export.
     * @return array with values ($iCountAdded,$iCountExist,$sErrorMsg,$sInfoMsg)
     */
    public function exportCreditmemos($aCreditmemoIds)
    {
        $sErrorMsg = '';
        $sInfoMsg = '';
        $iCountAdded = 0;
        $iCountExist = 0;

        sort($aCreditmemoIds);
        foreach ($aCreditmemoIds as $iCreditmemoId)
        {
            $oCreditMemo = Mage::getModel('sales/order_creditmemo')->load($iCreditmemoId);
            /* @var $oInvoice Mage_Sales_Model_Order_Creditmemo */
            list($iThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->_exportObject($oCreditMemo);
            $iCountAdded += $iThisAdded;
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;
        }
        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);
    }

    /**
     * Export one Invoice or Creditmemo
     *
     * @param Mage_Sales_Model_Order_Invoice $oContainer
     * @return array  with values ($iOrdersAdded,$iOrdersExist,$sErrorMsg)
     */
    protected function _exportObject($oContainer)
    {
        $sXml = '';
        $iCountAdded = 0;
        $iCountExist = 0;
        $sErrorMsg = '';
        $sInfoMsg = '';

        $oHelper = Mage::helper('Eboekhouden_Export');
        /* @var $oHelper Eboekhouden_Export_Helper_Data */
        $oAccountNumberHelper = Mage::helper('Eboekhouden_Export/accountNumber');
        /* @var Eboekhouden_Export_Helper_AccountNumber $oAccountNumberHelper */

        $iStoreId = $oContainer->getStoreId();
        if (empty($iStoreId))
        {
            $iStoreId = 0;
        }

        $iOrderTime = strtotime($oContainer->getCreatedAt());

        $oOrder = $oContainer->getOrder();
        /* @var $oOrder Mage_Sales_Model_Order */
        $sOrderNr = $oOrder->getIncrementId();
        $sInvoiceNr = 'X' . $oContainer->getIncrementId();
        $sExportType = Mage::helper('Eboekhouden_Export')->__('object');
        if ($oContainer instanceof Mage_Sales_Model_Order_Invoice)
        {
            $sExportType = Mage::helper('Eboekhouden_Export')->__('factuur');
            $sInvoiceNr = $oContainer->getIncrementId();
        }
        elseif ($oContainer instanceof Mage_Sales_Model_Order_Creditmemo)
        {
            $sExportType = Mage::helper('Eboekhouden_Export')->__('creditering');
            $sInvoiceNr = 'C' . $oContainer->getIncrementId();
        }

        $aSettings = $oHelper->getConnectorSettings($oContainer->getStore());
        $oBillingAddress = $oContainer->getBillingAddress();

        if (empty($aSettings['bConOK']))
        {
            # Skip the rest
        }
        elseif (empty($oBillingAddress))
        {
            $sErrorMsg .= Mage::helper('Eboekhouden_Export')
                    ->__('Fout in %s %s: geen factuuradres gevonden. ' . "\n", $sExportType,
                         $oContainer->getIncrementId());
        }
        else
        {
            $sCompanyName = $oBillingAddress->getCompany();
            if (empty($sCompanyName))
            {
                $sCompanyName = $oBillingAddress->getName();
            }

            $iExistingMutatieNr = $oContainer->getEboekhoudenMutatie();

            $aTaxInfo = $oOrder->getFullTaxInfo();
            $aValidTaxPerc = array();
            foreach ( $aTaxInfo as $aTaxInfoItem )
            {
                foreach ( $aTaxInfoItem['rates'] as $aRateInfo )
                {
                    $aValidTaxPerc[ $aRateInfo['percent'] ] = 1;
                }
            }

            $sObjectDescription = Mage::helper('Eboekhouden_Export')->__('Magento %s %s, order %s'
                , $sExportType
                , $oContainer->getIncrementId()
                , $sOrderNr);

            $sXml .= '
  <MUTATIE>';
            if (!empty($iExistingMutatieNr))
            {
                $sXml .= '
    <MUTNR>' . $oHelper->xmlPrepare($iExistingMutatieNr) . '</MUTNR>';
            }
            $sStreetFull = $oBillingAddress->getStreetFull();
            $sStreetFull = str_replace("\n", ' ', $sStreetFull);
            $sEmail = $oOrder->getCustomerEmail();
            $sTaxvat = $oOrder->getCustomerTaxvat();
            $sTaxvat = strtoupper(preg_replace('|\W|', '', $sTaxvat));

            $iBalanceAccount = 1300;
            $iBalanceAccount = $oAccountNumberHelper->getBalanceAccountNumber($iBalanceAccount,$oContainer);
            if (empty($iBalanceAccount))
            {
                $iBalanceAccount = 1300;
            }
            $sXml .= '
    <NAW>
      <BEDRIJF>' . $oHelper->xmlPrepare($sCompanyName) . '</BEDRIJF>
      <ADRES>' . $oHelper->xmlPrepare($sStreetFull) . '</ADRES>
      <POSTCODE>' . $oHelper->xmlPrepare($oBillingAddress->getPostcode()) . '</POSTCODE>
      <PLAATS>' . $oHelper->xmlPrepare($oBillingAddress->getCity()) . '</PLAATS>
      <LAND>' . $oHelper->xmlPrepare($oBillingAddress->getCountryModel()->getName()) . '</LAND>
      <LANDCODE>' . $oHelper->xmlPrepare($oBillingAddress->getCountry()) . '</LANDCODE>
      <TELEFOON>' . $oHelper->xmlPrepare($oBillingAddress->getTelephone()) . '</TELEFOON>
      <EMAIL>' . $oHelper->xmlPrepare($sEmail) . '</EMAIL>
      <OBNUMMER>' . $oHelper->xmlPrepare($sTaxvat) . '</OBNUMMER>
    </NAW>
    <SOORT>' . $oHelper->xmlPrepare(2) . '</SOORT>
    <REKENING>' . $oHelper->xmlPrepare($iBalanceAccount) . '</REKENING>
    <OMSCHRIJVING>' . $oHelper->xmlPrepare($sObjectDescription) . '</OMSCHRIJVING>
    <FACTUUR>' . $oHelper->xmlPrepare($sInvoiceNr) . '</FACTUUR>
    <BETALINGSKENMERK>' . $oHelper->xmlPrepare($sOrderNr) . '</BETALINGSKENMERK>
    <BETALINGSTERMIJN>' . $oHelper->xmlPrepare(30) . '</BETALINGSTERMIJN>
    <DATUM>' . $oHelper->xmlPrepare(date('d-m-Y', $iOrderTime)) . '</DATUM>
    <INEX>' . $oHelper->xmlPrepare('EX') . '</INEX>
    <MUTATIEREGELS>';

            $aOrderItems = $oContainer->getItemsCollection();
            $fDiscountLeft = $oContainer->getBaseDiscountAmount();

            foreach ($aOrderItems as $oItem)
                /* @var $oItem Mage_Sales_Model_Order_Invoice_Item */
            {
                $fDbProductTaxAmount = $oItem->getBaseTaxAmount();

                $aWeeItems = $this->_oWeeeHelper->getApplied( $oItem );
                foreach ( $aWeeItems as $aWeeData )
                {
                    $oWeeeItem = new Mage_Sales_Model_Order_Item();
                    $oWeeeItem->setData( $aWeeData );
                    $oWeeeItem->setStoreId( $iStoreId );

                    /////  Bugfix for Magento not saving tax in serialezed weee data in order item
                    if (  0 < $fDbProductTaxAmount // Tax used + prevent devide by zero
                       && $this->_oWeeeHelper->isTaxable($iStoreId)
                       && $oWeeeItem->getBaseRowTotal() == $oWeeeItem->getBaseRowTotalInclTax() // No tax calculated for WEEE
                       )
                    {
                        $fRowAllTotalEx = $oItem->getBaseRowTotal() - $oItem->getBaseDiscountAmount() + $oItem->getWeeeTaxAppliedRowAmount();
                        $fAllTaxPerc = 100 * $fDbProductTaxAmount / $fRowAllTotalEx;
                        $fAllTaxPerc = $this->_vatPercRound( $fAllTaxPerc );
                        if ( empty($aValidTaxPerc[$fAllTaxPerc]) ) // It is not a valid tax percentage yet
                        {
                            $fAllTaxPerc = $this->_getItemVatPerc( $oContainer, $oItem );
                        }
                        if ( !empty($aValidTaxPerc[$fAllTaxPerc]) ) // It is a valid tax percentage
                        {
                            $fVatFactor = (100+$fAllTaxPerc) / 100;
                            $oItem->setTaxPercent( $fAllTaxPerc );
                            $oItem->setBaseTaxAmount( $oItem->getBaseRowTotal() * $fAllTaxPerc / 100 );
                            $oItem->setBaseRowTotalInclTax( $oItem->getBaseRowTotal() + $oItem->getBaseTaxAmount() );

                            $oWeeeItem->setTaxPercent( $fAllTaxPerc );
                            if ( $this->_oTaxConfig->priceIncludesTax($iStoreId) )
                            {
                                $oWeeeItem->setBaseAmount( $oWeeeItem->getBaseAmountInclTax() / $fVatFactor );
                                $oWeeeItem->setBaseRowAmount( $oWeeeItem->getBaseRowAmountInclTax() / $fVatFactor );
                            }
                            else
                            {
                                $oWeeeItem->setBaseAmountInclTax( $oWeeeItem->getBaseAmount() * $fVatFactor );
                                $oWeeeItem->setBaseRowAmountInclTax( $oWeeeItem->getBaseRowAmount() * $fVatFactor );
                            }
                            $oWeeeItem->setBaseTaxAmount( $oWeeeItem->getBaseRowAmountInclTax() - $oWeeeItem->getBaseRowAmount() );

                        }
                    }
                    /////  /Bugfix for Magento not saving tax in serialezed weee data in order item

                    $oWeeeItem->setProductId( 'weee_'.$oItem->getProductId() );
                    $oWeeeItem->setBaseRowTotal( $oWeeeItem->getBaseRowAmount() );
                    $oWeeeItem->setBaseRowTotalInclTax( $oWeeeItem->getBaseRowAmountInclTax() );

                    $sXml .= $this->_getItemXml($oContainer, $oWeeeItem);
                }

                $sXml .= $this->_getItemXml($oContainer, $oItem); // Add XML for normal item
                $fDiscountLeft -= $oItem->getBaseDiscountAmount();
            }
            // Add shipping
            if (0 < $oContainer->getBaseShippingAmount())
            {
                $fShipFactor = 1;
                if ($oContainer instanceof Mage_Sales_Model_Order_Creditmemo)
                {
                    // We need to find out which part of the shippingcost is refunded.
                    // Magento only updates the BaseShippingAmount for a CreditMemo.
                    $fBaseShippingAmount = $oOrder->getBaseShippingAmount();
                    if (  !empty($fBaseShippingAmount)
                       && is_numeric($fBaseShippingAmount)
                       && 0 != 1 * $fBaseShippingAmount
                       ) // prevent divivsion by zero
                    {
                        $fShipFactor = $oContainer->getBaseShippingAmount() / $fBaseShippingAmount;
                    }
                }
                // Shipping & Handling cost, create a virtual order_item
                $oShippingItem = new Mage_Sales_Model_Order_Item();
                $oShippingItem->setStoreId($iStoreId);
                $oShippingItem->setProductId('shipping');
                $oShippingItem->setBaseRowTotal($fShipFactor * $oContainer->getBaseShippingAmount());
                $oShippingItem->setBaseRowTotalInclTax($fShipFactor * $oContainer->getBaseShippingInclTax());
                $oShippingItem->setBaseTaxAmount($fShipFactor * $oContainer->getBaseShippingTaxAmount());
                $oShippingItem->setBaseDiscountAmount($fShipFactor * $oContainer->getBaseShippingDiscountAmount());
                if ( 0 == $oShippingItem->getBaseDiscountAmount()
                     && 0 < $oContainer->getBaseDiscountAmount()
                     && $fDiscountLeft >= 0.01 )
                {
                    // There is discount applied on the order but not on the shipping cost
                    // and there is discount left. That discount must be for the shipping, so let's add it.
                    $oShippingItem->setBaseDiscountAmount($fDiscountLeft);
                }
                $oShippingItem->setTaxClassId(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                                                                   $iStoreId));
                $sXml .= $this->_getItemXml($oContainer, $oShippingItem);
            }

            // Add adjustment in case it exists (for credit memos)
            if ((float)$oContainer->getAdjustment() !== 0) {
                $oAdjustmentItem = new Mage_Sales_Model_Order_Item();
                $oAdjustmentItem->setStoreId($iStoreId);
                $oAdjustmentItem->setProductId('adjustment_fee');
                $oAdjustmentItem->setBaseRowTotal($oContainer->getAdjustment());
                $oAdjustmentItem->setBaseRowTotalInclTax($oContainer->getAdjustment());
                $oAdjustmentItem->setBaseTaxAmount(0);

                $sXml .= $this->_getItemXml($oContainer, $oAdjustmentItem);
            }
            $sXml .= '
    </MUTATIEREGELS>
  </MUTATIE>';

            $sPostAction = (!empty($iExistingMutatieNr)) ? 'ALTER_MUTATIE' : 'ADD_MUTATIE';
            list($sThisMutatieNr, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->_postMutatieXml($sXml,
                                                                                                       $aSettings,
                                                                                                       $sPostAction);
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;
            if (!empty($sThisMutatieNr)) # can be boolean or string
            {
                $iCountAdded++;
                if (is_string($sThisMutatieNr))
                {
                    // Do NOT save whole $oContainer, because the fixes to all items will be written to database!
                    $oContainer->setEboekhoudenMutatie($sThisMutatieNr);
                    $oContainer->getResource()->saveAttribute( $oContainer, 'eboekhouden_mutatie' );
                }
            }

            if ($oContainer instanceof Mage_Sales_Model_Order_Invoice)
            {
                $oCreditMemoColl = $oOrder->getCreditmemosCollection();
                /* @var $oCreditMemoColl Mage_Sales_Model_Mysql4_Order_Creditmemo_Collection */
                if (!empty($oCreditMemoColl) && $oCreditMemoColl->count())
                {
                    foreach ($oCreditMemoColl as $oCreditMemo)
                        /* @var $oCreditMemo Mage_Sales_Model_Order_Creditmemo */
                    {
                        list($sThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->_exportObject($oCreditMemo,
                                                                                                             $aSettings);
                        $iCountAdded += $sThisAdded;
                        $iCountExist += $iThisExist;
                        $sErrorMsg .= $sThisErrorMsg;
                        $sInfoMsg .= $sThisInfoMsg;
                    }
                }
            }
        }
        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);
    }

    /**
     * Post the XML of one order or invoice to the e-Boekhouden API
     *
     * @param string  $sXml   the XML to post
     * @return array          with values ($iOrdersMutatie,$iOrdersExist,$sErrorMsg,$sInfoMsg)
     */
    protected function _postMutatieXml($sMutatieXml, $aSettings, $sAction = 'ADD_MUTATIE')
    {
        $sErrorMsg = '';
        $sInfoMsg = '';
        $sMutatieNr = false;
        $iOrdersExist = 0;
        $oHelper = Mage::helper('Eboekhouden_Export');
        /* @var $oHelper Eboekhouden_Export_Helper_Data */

        $sXml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $sXml .= '
<API>
  <ACTION>' . $oHelper->xmlPrepare($sAction) . '</ACTION>
  <VERSION>' . $oHelper->xmlPrepare('1.0') . '</VERSION>
  <SOURCE>' . $oHelper->xmlPrepare('Magento') . '</SOURCE>
  <AUTH>
    <GEBRUIKERSNAAM>' . $oHelper->xmlPrepare($aSettings['sConUser']) . '</GEBRUIKERSNAAM>
    <WACHTWOORD>' . $oHelper->xmlPrepare($aSettings['sConWord']) . '</WACHTWOORD>
    <GUID>' . $oHelper->xmlPrepare($aSettings['sConGuid']) . '</GUID>
  </AUTH>';
        $sXml .= $sMutatieXml;
        $sXml .= '
</API>';

        if (Mage::getStoreConfig('eboekhouden/settings/showxml'))
        {
            $sInfoMsg .= 'posted xml:<BR>' . "\n";
            $sInfoMsg .= '<xmp style="font-weight:normal">';
            $sInfoMsg .= $sXml . "\n";
            $sInfoMsg .= '</xmp><BR>' . "\n";
        }

        $oClient = new Zend_Http_Client();
        $oClient->setUri('https://secure.e-boekhouden.nl/bh/api.asp');
        $oClient->setParameterPost('xml', $sXml);
        $oResponse = $oClient->request('POST');

        if ($oResponse->isError())
        {
            $sErrorMsg .= Mage::helper('Eboekhouden_Export')->__('HTTP fout %s ontvangen van API: %s', $oResponse->getStatus(),
                                                          $oResponse->getMessage()) . "\n";
        }
        else
        {
            $sResponse = $oResponse->getBody();
            if (empty($sResponse))
            {
                $sErrorMsg .= Mage::helper('Eboekhouden_Export')->__('Fout: Leeg antwoord ontvangen van API') . "\n";
            }
            else
            {
                if (Mage::getStoreConfig('eboekhouden/settings/showxml'))
                {
                    $sInfoMsg .= 'response xml:<BR>' . "\n";
                    $sInfoMsg .= '<xmp style="font-weight:normal">';
                    $sInfoMsg .= $sResponse . "\n";
                    $sInfoMsg .= '</xmp><BR>' . "\n";
                }

                $oData = @simplexml_load_string($sResponse);
                if (empty($oData))
                {
                    $sShowResponse = htmlspecialchars(strip_tags($sResponse));
                    $sShowResponse = preg_replace('#\s*\n#', "\n", $sShowResponse);
                    $sErrorMsg .= Mage::helper('Eboekhouden_Export')
                                          ->__('Fout in van API ontvangen XML: parsen mislukt') . "\n" . $sShowResponse . "\n";
                }
                elseif (empty($oData->RESULT))
                {
                    $sErrorMsg .= Mage::helper('Eboekhouden_Export')
                                          ->__('Fout in van API ontvangen XML: "RESULT" veld is leeg') . "\n";
                }
                elseif ('ERROR' == strval($oData->RESULT))
                {
                    if ('M006' == strval($oData->ERROR->CODE))
                    {
                        $iOrdersExist++;
                    }
                    else
                    {
                        $sErrorMsg .= Mage::helper('Eboekhouden_Export')->__('Fout %s: %s', $oData->ERROR->CODE,
                                                                      $oData->ERROR->DESCRIPTION) . "\n";
                    }
                }
                elseif ('OK' == strval($oData->RESULT))
                {
                    # Inititiate sMutatieNr to true, for the situation that the mutatie exists in EB.nl, and the MutatieNr stays the same
                    $sMutatieNr = true;
                }
                else
                {
                    $sErrorMsg .= Mage::helper('Eboekhouden_Export')
                                          ->__('Onbekend resultaat van API ontvangen: %s' . $oData->RESULT) . "\n";
                }
                if (!empty($oData->MUTNR))
                {
                    $sMutatieNr = strval($oData->MUTNR);
                }
            }
        }
        return array($sMutatieNr, $iOrdersExist, $sErrorMsg, $sInfoMsg);
    }


    /**
     * Get the XML for one order item
     *
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo  $oContainer
     * @param Mage_Sales_Model_Order_Invoice_Item|Mage_Sales_Model_Order_Creditmemo_Item  $oItem
     * @return string
     */
    protected function _getItemXml(&$oContainer, &$oItem)
    {
        $oHelper = Mage::helper('Eboekhouden_Export');
        /* @var $oHelper Eboekhouden_Export_Helper_Data */
        $oAccountNumberHelper = Mage::helper('Eboekhouden_Export/accountNumber');
        /* @var Eboekhouden_Export_Helper_AccountNumber $oAccountNumberHelper */

        $aSettings = $oHelper->getConnectorSettings($oContainer->getStore());

        $sXml = '';
        $sComment = '';

        $sType = $this->_getItemType( $oItem );

        if ( 'dummy' != $sType && 'bundle' != $sType )
        {
            $iStoreId = $oContainer->getStoreId();
            if (empty($iStoreId))
            {
                $iStoreId = Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $oOrder = $oContainer->getOrder();
            /* @var $oOrder Mage_Sales_Model_Order */

            $fVatPercent = $this->_getItemVatPerc( $oContainer, $oItem );
            $sComment .= ' type:'.$sType.' ';
            $sComment .= ' [BTW '.$fVatPercent.'%] ';
            $fVatFactor = (100 + $fVatPercent) / 100;
            if (empty($fVatFactor)
                || !is_numeric($fVatFactor)
                || 0 == 1 * $fVatFactor
            ) // prevent division by zero
            {
                $fVatFactor = 1;
            }

            // Due to bugs in Magento the row total incl. tax is the most reliable
            $fPriceIn = $oItem->getBaseRowTotalInclTax();
            if (empty($fPriceIn)) // Can be 0 and invalid in Magento 1.3.x
            {
                if ($oItem->getBaseRowTotal() !== null) {
                    // Receiving TotalPriceInclTax failed, probably Magento 1.3.x or complicated one
                    // Use fallback calculation method.
                    $fPriceIn = $oItem->getBaseRowTotal() * $fVatFactor;
                    $sComment .= ' ['.$fPriceIn.' = '.$oItem->getBaseRowTotal().' * '.$fVatFactor.'] ';
                }
            }

            $fDiscountAmount = $oItem->getBaseDiscountAmount();
            if (!empty($fDiscountAmount) && 0 < $fDiscountAmount)
            {
                if ( $this->_oTaxConfig->applyTaxAfterDiscount() )
                {
                    // Apply Tax after Discount
                    if ( $this->_oTaxConfig->discountTax() )
                    {
                        // Apply Discount On Prices: Including Tax
                        $fDiscountAmountEx = $fDiscountAmount / $fVatFactor;
                    }
                    else
                    {
                        // Apply Discount On Prices: Excluding Tax
                        $fDiscountAmountEx = $fDiscountAmount;
                    }
                    $fPriceIn = ( $oItem->getBaseRowTotal() - $fDiscountAmountEx ) * $fVatFactor;
                    $sComment .= ' ['.$fPriceIn.' = ('.$oItem->getBaseRowTotal().' - '.$fDiscountAmountEx.') * '.$fVatFactor.'] ';
                }
                else
                {
                    // Apply Tax before Discount
                    $fPriceInOld = $fPriceIn;
                    $fPriceIn = $fPriceInOld - $fDiscountAmount;
                    $sComment .= ' ['.$fPriceIn.' = '.$fPriceInOld.' - '.$fDiscountAmount.'] ';
                }
            }

            $fPriceEx = $fPriceIn / $fVatFactor;

            // When the base_row_total is null, use base prices
            // This occurs when bundled products are used
            if (empty($fPriceIn) && $oItem->getBaseRowTotal() === null) {
                $sComment .= ' ['.$fPriceIn.' = Base price: '.$oItem->getBasePriceInclTax().'] ';
                $fPriceIn = $oItem->getBasePriceInclTax();
                $fPriceEx = $oItem->getBasePrice();
            }

            $fTaxAmount = $fPriceIn - $fPriceEx;
            if (empty($fTaxAmount))
            {
                $fTaxAmount = 0;
            }

            $iProductTaxClassId = false;
            $iGbRekening = false;
            $iCostcenter = false;
            $sProductId = $oItem->getProductId();

            if (!empty($sProductId))
            {
                if ( preg_match( '|^weee_(.+)$|', $sProductId, $aMatch ) )
                {
                    $sProductId = $aMatch[1];
                    $sComment .= 'WEEE/FPT: ';
                }
                if ( $oItem->getTitle() )
                {
                    $sComment .= $oItem->getTitle().': ';
                }

                if ('shipping' == $sProductId)
                {
                    $iGbRekening = $aSettings['sShipLedgerAcc'];
                    $iCostcenter = $aSettings['sShipCostcenter'];
                    $iProductTaxClassId = $oItem->getTaxClassId();
                    $sComment .= 'Shipping';
                }
                else if ('adjustment_fee' == $sProductId) {
                    $iGbRekening = $aSettings['sAdjustmentLedgerAcc'];
                    $sComment .= 'Adjustment';
                }
                else
                {
                    $sProductCode = 'ID: ' . $sProductId;
                    $oProduct = Mage::getModel('catalog/product');
                    /* @var $oProduct Mage_Catalog_Model_Product */
                    $oProduct->setStoreId($iStoreId);
                    $oProduct->load($sProductId);
                    if (!empty($oProduct) && $oProduct->hasData('sku'))
                    {
                        $sProductCode = $oProduct->getSku();
                        $iGbRekening = $oProduct->getEboekhoudenGrootboekrekening();
                        $iCostcenter = $oProduct->getEboekhoudenCostcenter();
                        $iProductTaxClassId = $oProduct->getTaxClassId();
                    }
                    $sComment .= 'Product ' . $sProductCode;
                }
            }

            $iGbRekening = $oAccountNumberHelper->getLedgerAccountNumber($iGbRekening,$oContainer,$oItem);
            if (empty($iGbRekening))
            {
                $iGbRekening = 8000;
            }
            $iCostcenter = $oAccountNumberHelper->getCostCenterNumber($iCostcenter,$oContainer,$oItem);
            if (empty($iCostcenter))
            {
                $iCostcenter = 0;
            }

            $sVatCode = $this->_Find_Ebvatcode($fVatPercent, $oOrder, $iProductTaxClassId);

            if ($oContainer instanceof Mage_Sales_Model_Order_Creditmemo)
            {
                $sComment = 'Refund: ' . $sComment;
                $fPriceIn = -1 * $fPriceIn;
                $fPriceEx = -1 * $fPriceEx;
                $fTaxAmount = -1 * $fTaxAmount;
            }
            // Leading numbers for e-Boekhouden: BEDRAGEXCL and BTWPERC
            $sXml .= '
      <MUTATIEREGEL>
        <!-- ' . $oHelper->xmlPrepare($sComment) . ' -->
        <BEDRAGINCL>' . $this->_xmlAmountPrepare($fPriceIn) . '</BEDRAGINCL>
        <BEDRAGEXCL>' . $this->_xmlAmountPrepare($fPriceEx) . '</BEDRAGEXCL>
        <BTWBEDRAG>' . $this->_xmlAmountPrepare($fTaxAmount) . '</BTWBEDRAG>
        <BTWPERC>' . $oHelper->xmlPrepare($sVatCode) . '</BTWPERC>
        <TEGENREKENING>' . $oHelper->xmlPrepare($iGbRekening) . '</TEGENREKENING>
        <KOSTENPLAATS>' . $oHelper->xmlPrepare($iCostcenter) . '</KOSTENPLAATS>
      </MUTATIEREGEL>';
        }
        return $sXml;
    }

    private function _xmlAmountPrepare($fValue)
    {
        $oHelper = Mage::helper('Eboekhouden_Export');
        /* @var $oHelper Eboekhouden_Export_Helper_Data */
        $fRoundVal = round(floatval($fValue), 2);
        return $oHelper->xmlPrepare($fRoundVal);
    }

    private function _Find_CustomerTaxClassId($oOrder)
    {
        $iResult = false;
        $iStoreId = $oOrder->getStoreId();
        $iCustomerId = $oOrder->getCustomerId();

        if ( !empty($iCustomerId) )
        {
            $oCustomer = Mage::getModel('customer/customer')->load($iCustomerId);
            $iResult = $oCustomer->getTaxClassId();
        }

        if ( empty($iResult) )
        {
            // We need to get the tax class id based on the customer group.
            // Set default group id first
            $iCustomerGroupId = Mage::getStoreConfig('customer/create_account/default_group', $iStoreId);
            if (!empty($iCustomerId))
            {
                $oCustomer = Mage::getModel('customer/customer')->load($iCustomerId);
                /* @var $oCustomer Mage_Customer_Model_Customer */
                $iCustomerGroupId = $oCustomer->getGroupId();
            }
            $oCustomerGroup = Mage::getModel('customer/group')->load($iCustomerGroupId);
            /* @var $oCustomerGroup Mage_Customer_Model_Group */
            $iResult = $oCustomerGroup->getTaxClassId();
        }

        return $iResult;
    }

    /**
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo  $oContainer
     * @param Mage_Sales_Model_Order_Invoice_Item|Mage_Sales_Model_Order_Creditmemo_Item  $oItem
     * @return float
     */
    private function _getItemVatPerc($oContainer,$oItem,$iAttempt=0)
    {
        $fVatPercent = $oItem->getTaxPercent();
        if (!isset($fVatPercent))
        {
            // Getting VAT perc failed, we need to calculate
            $fTempTaxAmount = $oItem->getBaseTaxAmount();
            $fTempPriceEx = $oItem->getBaseRowTotalInclTax() - $fTempTaxAmount;
            if ( 1 == $iAttempt )
            {
                // Fix for wrong VAT calculation due to bad Magento configuration
                $fTempPriceEx = $oItem->getBaseRowTotal();
            }

            $fDiscountAmount = $oItem->getBaseDiscountAmount();
            if ( !empty($fDiscountAmount) && $this->_oTaxConfig->applyTaxAfterDiscount() )
            {
                $fTempPriceEx = $fTempPriceEx - $fDiscountAmount;
            }

            if ('shipping' == $oItem->getProductId() && empty($fTempTaxAmount))
            {
                $oOrder = $oContainer->getOrder();
                // Magento 1.3.x is missing some shipping fields in the invoice,
                // using shipping data from the order to calculate the vat perc.
                $fTempTaxAmount = $oOrder->getBaseShippingTaxAmount();
                $fTempPriceEx = $oOrder->getBaseShippingAmount();
            }
            $fVatPercent = 0;
            if (  !empty($fTempPriceEx)
               && is_numeric($fTempPriceEx)
               && 0 != 1 * $fTempPriceEx  // prevent division by zero
               )
            {
                $fVatPercent = 100 * $fTempTaxAmount / $fTempPriceEx;
            }

            if (  0 == $fVatPercent                                       // No VAT precentage found yet
               && $oItem->getBasePriceInclTax() != $oItem->getBasePrice() // Non-discount In and Ex price is not the same
               && 0 < $oItem->getBaseHiddenTaxAmount()                    // - prevent division by zero
               )
            {
                // In this case we calculate VAT based on the price before discount.
                // It is important to find the right VAT percentage because it is used for several calculations
                // even if the total row amount is 0.
                $fOriginalVatAmount = $oItem->getBasePriceInclTax() - $oItem->getBasePrice();
                $fVatPercent = 100 * $fOriginalVatAmount / $oItem->getBasePrice();
            }
        }
        $fVatPercent = floatval($fVatPercent);
        $fVatPercent = $this->_vatPercRound($fVatPercent);
        $aKnownRates = $this->_getKnownVatRates();
        if ( $iAttempt <= 1 && false === array_search($fVatPercent,$aKnownRates) )
        {
            $fVatPercent = $this->_getItemVatPerc( $oContainer,$oItem,$iAttempt+1 );
        }
        return $fVatPercent;
    }

    /**
     * Look up the EB vat code
     *
     * @param string $sMagVatCode
     * @param array $aVatPercToMagCode
     * @param Mage_Sales_Model_Order $oOrder
     * @return string
     */
    private function _Find_Ebvatcode($fVatPercent, $oOrder, $iProductTaxClassId)
    {
        $fVatPercent = $this->_vatPercRound($fVatPercent);
        $sVatCode = false;
        $sMagCode = false;

        $iStoreId = $oOrder->getStoreId();
        if (empty($iStoreId))
        {
            $iStoreId = 0;
        }

        if (empty($sVatCode) && empty($sMagCode))
        {
            // Try finding by percentage in the Order's Full Tax Info
            $aVatPercToMagCode = array();
            $aTaxInfo = $oOrder->getFullTaxInfo();
            if (is_array($aTaxInfo))
            {
                foreach ($aTaxInfo as $aTaxRow)
                {
                    foreach ($aTaxRow['rates'] as $aTaxRate)
                    {
                        if (!empty($aTaxRate['code']) && isset($aTaxRate['percent']))
                        {
                            $aVatPercToMagCode[$aTaxRate['percent']] = $aTaxRate['code'];
                        }
                    }
                }
            }
            if (!empty($aVatPercToMagCode[$fVatPercent]))
            {
                $sMagCode = $aVatPercToMagCode[$fVatPercent];
            }
        }

        if (empty($sVatCode) && empty($sMagCode))
        {
            // Try complete recalculation of tax
            if (!empty($iProductTaxClassId))
            {
                $oStore = Mage::app()->getStore($iStoreId);
                /* @var $oStore Mage_Core_Model_Store */
                $iCustomerTaxClassId = $this->_Find_CustomerTaxClassId($oOrder);
                $oTaxCalc = Mage::getSingleton('tax/calculation');
                /* @var $oTaxCalc Mage_Tax_Model_Calculation */
                $iCustomerId = $oOrder->getCustomerId();
                if (!empty($iCustomerId))
                {
                    $oCustomer = Mage::getModel('customer/customer')->load($iCustomerId);
                    /* @var $oCustomer Mage_Customer_Model_Customer */
                    if (!empty($oCustomer))
                    {
                        $oTaxCalc->setCustomer($oCustomer);
                    }
                }
                $oRequest = $oTaxCalc->getRateRequest($oOrder->getShippingAddress(), $oOrder->getBillingAddress(),
                                                      $iCustomerTaxClassId, $oStore);
                /* @var $oRequest Varien_Object */
                $oRequest->setProductClassId($iProductTaxClassId);
                $oResourceTaxCalc = Mage::getResourceSingleton('tax/calculation');
                /* @var $oResourceTaxCalc Mage_Tax_Model_Mysql4_Calculation */
                if (!empty($oResourceTaxCalc) && method_exists($oResourceTaxCalc, 'getRateInfo'))
                { // Mag 1.3 doesn't have getRateInfo
                    $rateInfo = $oResourceTaxCalc->getRateInfo($oRequest);
                    if (!empty($rateInfo['process'][0]['id']))
                    {
                        $sMagCode = $rateInfo['process'][0]['id'];
                    }
                }
            }
        }

        if (!empty($sMagCode))
        {
            $oRateModel = Mage::getModel('tax/calculation_rate');
            /* @var $oRateModel Mage_Tax_Model_Calculation_Rate */
            $oRateModel->setStoreId($iStoreId);
            $oRateModel->load($sMagCode, 'code');
            $sRateEbvatcode = $oRateModel->getTaxEbvatcode();
            if (!empty($sRateEbvatcode))
            {
                $sVatCode = $sRateEbvatcode;
            }
        }

        if (empty($sVatCode))
        {
            // Receiving vatcode failed, use fallback vat code choosing method
            if (0 == $fVatPercent)
            {
                $sVatCode = 'GEEN';
            }
            elseif (6 == $fVatPercent)
            {
                $sVatCode = 'LAAG_VERK';
            }
            elseif (19 == $fVatPercent)
            {
                $sVatCode = 'HOOG_VERK';
            }
            else
            {
                $sVatCode = 'HOOG_VERK_21';
            }
        }

        return $sVatCode;
    }

    /**
     * @param $fVatPercent float
     * @return float
     */
    private function _vatPercRound($fVatPercent)
    {
        $aKnownRates = $this->_getKnownVatRates();
        foreach ($aKnownRates as $fKnownRate)
        {
            if (0.1 > abs($fVatPercent - $fKnownRate))
            {
                return $fKnownRate;
            }
        }
        // http://en.wikipedia.org/wiki/Tax_rates_around_the_world
        // Some countries have a vat perc with 3 digits
        return round($fVatPercent, 3);
    }

    private function _getKnownVatRates()
    {
        $aResult = array();

        $oCollection = Mage::getModel('tax/calculation_rate')->getCollection(); /* @var $oCollection Mage_Tax_Model_Resource_Calculation_Rate_Collection */
        foreach($oCollection as $oItem)
        {
            $aResult[ $oItem->getCode() ] = floatval( $oItem->getRate() );
        }

        return  $aResult;
    }

    /**
     * @param $oItem Mage_Sales_Model_Invoice_Item|Mage_Sales_Model_Order_Item
     * @return string
     */
    private function _getItemType( &$oItem )
    {
        $oOrderItem = null; /* @var $oOrderItem Mage_Sales_Model_Order_Item */
        if ( $oItem instanceof Mage_Sales_Model_Order_Item )
        {
            $oOrderItem = $oItem;
        }
        else
        {
            $oOrderItem = $oItem->getOrderItem();
        }

        $sProductId = $oItem->getProductId();
        $sType = 'unknown';
        if ( method_exists($oItem, 'isDummy') && $oItem->isDummy() )
        {
            $sType = 'dummy';
        }
        elseif ( preg_match('|^weee_|', $sProductId) )
        {
            $sType = 'weee';
        }
        elseif ('shipping' == $sProductId)
        {
            $sType = 'shipping';
        }
        elseif ('adjustment_fee' == $sProductId)
        {
            $sType = 'adjustment';
        }
        elseif ( !empty($oOrderItem) )
        {
            $sType = $oOrderItem->getProductType();
            if ( $oOrderItem->getParentItemId() )
            {
                $sType = 'child';
            }
        }
        else
        {
            $sType = 'no_orderitem';
        }
        return $sType;
   }

}
