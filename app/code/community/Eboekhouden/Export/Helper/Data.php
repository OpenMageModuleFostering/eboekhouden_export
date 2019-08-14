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
class Eboekhouden_Export_Helper_Data extends Mage_Core_Helper_Abstract
{
    //
    // TO USE:
    //
    //    $oHelper = Mage::helper('Eboekhouden_Export'); /* @var $oHelper Eboekhouden_Export_Helper_Data */
    //

    /**
     * @param mixed $mStore
     * @return array
     */
    public function getConnectorSettings($mStore = null)
    {
        $sErrorMsg = '';
        $aSettings = array();

        $aSettings['bConOK'] = 0;
        $aSettings['sConUser'] = trim(Mage::getStoreConfig('eboekhouden/connector/username', $mStore));
        $aSettings['sConWord'] = trim(Mage::getStoreConfig('eboekhouden/connector/securitycode1', $mStore));
        $aSettings['sConGuid'] = trim(Mage::getStoreConfig('eboekhouden/connector/securitycode2', $mStore));
        $aSettings['sShipLedgerAcc'] = intval( trim(Mage::getStoreConfig('eboekhouden/settings/shippingledgeraccount', $mStore)) );
        $aSettings['sShipCostcenter'] = intval( trim(Mage::getStoreConfig('eboekhouden/settings/shippingcostcenter', $mStore)) );
        if ( empty($aSettings['sShipLedgerAcc']) )
        {
            $aSettings['sShipLedgerAcc'] = 8000;
        }
        if ( empty($aSettings['sShipCostcenter']) )
        {
            $aSettings['sShipCostcenter'] = 0;
        }

        if (empty($aSettings['sConUser']) || empty($aSettings['sConWord']) || empty($aSettings['sConGuid']))
        {
            $sCurrentUrl = Mage::helper('core/url')->getCurrentUrl();
            if (    !Mage::app()->getRequest()->getParam('eboekhouden_config_error', 0)
                 && !preg_match('|/system_config/|i',$sCurrentUrl)
               )
            {
                $aSettings['bConOK'] = 0;
                $sErrorMsg .= Mage::helper('Eboekhouden_Export')
                        ->__('Configuratie is niet volledig ingevuld, ga naar het menu "%s","%s" en kies "e-Boekhouden.nl" uit de zijbalk. Vul de gegevens in onder "Connector Login Gegevens"',
                             Mage::helper('adminhtml')->__('System'), Mage::helper('adminhtml')->__('Configuration'));
                Mage::getSingleton('core/session')->addError($sErrorMsg);
                Mage::app()->getRequest()->setParam('eboekhouden_config_error', 1);
            }
        }
        else
        {
            $aSettings['bConOK'] = 1;
        }

        return $aSettings;
    }

    /**
     * Prepare an output string for use in XML for e-Boekhouden.nl
     *
     * @param string $sValue
     * @return string
     */
    public function xmlPrepare($sValue)
    {
        $sResult = $sValue;
        $sResult = html_entity_decode($sResult); // remove previous HTML encoding
        // No utf8_encode() needed, all data in Magento is UTF-8.
        $sResult = htmlspecialchars($sResult, ENT_QUOTES, 'UTF-8'); // encode < > & ' "
        return $sResult;
    }

}

?>