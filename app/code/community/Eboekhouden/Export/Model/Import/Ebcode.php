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

class Eboekhouden_Export_Model_Import_Ebcode
{
    protected $iDefault = 0; // can be overridden in sub classes

    public function importCodesForDropdown($bShowConfigIncompleteMsg = true)
    {
        $aResult = array();
        $aCodes = $this->importCodes($bShowConfigIncompleteMsg);
        if (!is_array($aCodes))
        {
            $aCodes = array();
        }
        $aDefaultOption = array();
        $aDefaultOption['value'] = $this->iDefault;
        if (!empty($aCodes[$aDefaultOption['value']]))
        {
            $aDefaultOption['label'] = $aCodes[$aDefaultOption['value']];
        }
        else
        {
            $aDefaultOption['label'] = $aDefaultOption['value'] . ' -';
        }
        $aDefaultOption['label'] .= ' ' . Mage::helper('Eboekhouden_Export')->__('(standaard)');
        $aResult[] = $aDefaultOption;

        if (is_array($aCodes))
        {
            foreach ($aCodes as $sKey => $sValue)
            {
                $aOption = array();
                $aOption['value'] = trim($sKey);
                $aOption['label'] = $sValue;
                $aResult[] = $aOption;
            }
        }
        return $aResult;
    }

    function getCodes($sAction)
    {
        $oResult = false;
        if ( Mage::app()->getStore()->isAdmin() ) // Protection against unnecessary loading in frontend
        {
            $sErrorMsg = '';
            $sInfoMsg = '';

            $oClient = new Zend_Http_Client();
            $oClient->setUri('https://secure.e-boekhouden.nl/bh/api.asp');

            $oHelper = Mage::helper('Eboekhouden_Export');
            /* @var $oHelper Eboekhouden_Export_Helper_Data */
            $aSettings = $oHelper->getConnectorSettings();

            if (!empty($aSettings['bConOK']))
            {
                $sXml = '<?xml version="1.0"?>' . "\n";
                $sXml .= '
        <API>
          <ACTION>' . $oHelper->xmlPrepare($sAction) . '</ACTION>
          <VERSION>1.0</VERSION>
          <SOURCE>Magento</SOURCE>
          <AUTH>
            <GEBRUIKERSNAAM>' . $oHelper->xmlPrepare($aSettings['sConUser']) . '</GEBRUIKERSNAAM>
            <WACHTWOORD>' . $oHelper->xmlPrepare($aSettings['sConWord']) . '</WACHTWOORD>
            <GUID>' . $oHelper->xmlPrepare($aSettings['sConGuid']) . '</GUID>
          </AUTH>
        </API>';

                if (Mage::getStoreConfig('eboekhouden/settings/showxml'))
                {
                    $sInfoMsg .= 'posted xml:<BR>' . "\n";
                    $sInfoMsg .= '<xmp style="font-weight:normal">';
                    $sInfoMsg .= $sXml . "\n";
                    $sInfoMsg .= '</xmp><BR>' . "\n";
                }

                $oClient->setParameterPost('xml', $sXml);
                $oResponse = $oClient->request('POST');

                if ($oResponse->isError())
                {
                    $sErrorMsg .= Mage::helper('Eboekhouden_Export')
                                          ->__('HTTP fout %s ontvangen van API: %s', $oResponse->getStatus(),
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

                        $oResult = @simplexml_load_string($sResponse);
                        if (empty($oResult))
                        {
                            $oResult = false;
                            $sShowResponse = htmlspecialchars(strip_tags($sResponse));
                            $sShowResponse = preg_replace('#\s*\n#', "\n", $sShowResponse);
                            $sErrorMsg .= Mage::helper('Eboekhouden_Export')
                                                  ->__('Fout in van API ontvangen XML: parsen mislukt') . "\n" . $sShowResponse . "\n";
                        }
                    }
                }

                if ($sInfoMsg)
                {
                    Mage::getSingleton('core/session')->addNotice($sInfoMsg);
                }
                if ($sErrorMsg)
                {
                    Mage::getSingleton('core/session')->addError(nl2br($sErrorMsg));
                }
            } // if connection ok
        } // if isAdmin
        return $oResult;
    }

}
