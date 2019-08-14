<?php


Class Eboekhouden_Export_Helper_Export {

	public function createXml($oContainer){
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

            $totalBaseAmountItems = 0;
            $totalBaseAmountInclTaxItems = 0;
            $totalBaseTaxItems = 0;

            foreach ($aOrderItems as $oItem) {
                $fDbProductTaxAmount = $oItem->getBaseTaxAmount();
                //add to order totals
                $totalBaseTaxItems += $fDbProductTaxAmount;
                $totalBaseAmountItems += $oItem->getBaseRowTotal();
                $totalBaseAmountInclTaxItems += $oItem->getBaseRowTotalInclTax();

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
                //add to order totals
                $totalBaseTaxItems += $fShipFactor * $oContainer->getBaseShippingTaxAmount();
                $totalBaseAmountItems += $fShipFactor * $oContainer->getBaseShippingAmount();
                $totalBaseAmountInclTaxItems += $fShipFactor * $oContainer->getBaseShippingInclTax();

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

            //add additional fee price (in case invoice price is higher then itemprice + shipping)
            $orderGrandTotal = round(floatval($oOrder->getGrandTotal()), 4) - $oOrder->getBaseDiscountAmount();
            if (0.0001 < abs($orderGrandTotal - round($totalBaseAmountInclTaxItems, 4))) {

               # debug($oOrder->debug());

                $orderSubtotal = round(floatval($oOrder->getSubtotal()) + floatval($oOrder->getBaseShippingAmount()), 4);
                $orderTaxAmount =  round(floatval($oOrder->getTaxAmount()), 4);

                $feeRowTotal = $orderSubtotal - round($totalBaseAmountItems, 4);
                $feeRowTotalInclTax =  $orderGrandTotal - round($totalBaseAmountInclTaxItems, 4);
                $feeTaxAmount = $orderTaxAmount - round($totalBaseTaxItems, 4);

                $feeItem = new Mage_Sales_Model_Order_Item();
                $feeItem->setStoreId($iStoreId);
                $feeItem->setProductId('payment_fee');
                $feeItem->setBaseRowTotal($feeRowTotal);
                $feeItem->setBaseRowTotalInclTax($feeRowTotalInclTax);
                $feeItem->setBaseTaxAmount($feeTaxAmount);
                $feeItem->setBaseDiscountAmount(0);

                $sXml .= $this->_getItemXml($oContainer, $feeItem);
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
}
