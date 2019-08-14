<?php

/*
 * The purpose of this class is to be overridden by specific shop implementations.
 */

//  Internal use in the Eboekhouden_Export extension:
//  $oAccountNumberHelper = Mage::helper('Eboekhouden_Export/accountNumber'); /* @var Eboekhouden_Export_Helper_AccountNumber $oAccountNumberHelper */

class Eboekhouden_Export_Helper_AccountNumber extends Mage_Core_Helper_Abstract
{

    /**
     * @param int  $iCurrentValue
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo  $oContainer
     * @return int
     */
    function getBalanceAccountNumber( $iCurrentValue, $oContainer )
    {
        return $iCurrentValue;
    }

    /**
     * @param int $iCurrentValue
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $oContainer
     * @param Mage_Sales_Model_Order_Invoice_Item|Mage_Sales_Model_Order_Creditmemo_Item $oItem
     * @return int
     */
    function getLedgerAccountNumber( $iCurrentValue, $oContainer, $oItem )
    {
        return $iCurrentValue;
    }


    /**
     * @param int $iCurrentValue
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $oContainer
     * @param Mage_Sales_Model_Order_Invoice_Item|Mage_Sales_Model_Order_Creditmemo_Item $oItem
     * @return int
     */
    function getCostCenterNumber( $iCurrentValue, $oContainer, $oItem )
    {
        return $iCurrentValue;
    }
}