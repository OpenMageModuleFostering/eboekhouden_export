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

class Eboekhouden_Export_Model_Config_Taxcalculationrate
{

    public function toOptionArray()
    {
        $aResult = array();

        $aItem = array();
        $aItem['value'] = '=NOT-USED=';
        $aItem['label'] = '-- ' . Mage::helper('Eboekhouden_Export')->__('niet in gebruik') . ' --';
        $aResult[] = $aItem;

        $oTaxCollection = Mage::getModel('tax/calculation_rate')->getCollection()->load();
        /* @var $oTaxCollection Mage_Tax_Model_Mysql4_Calculation_Rate_Collection */

        $aTaxItems = $oTaxCollection->getItems();
        foreach ($aTaxItems as $oTaxItem)
        {
            /* @var $oTaxItem Mage_Tax_Model_Calculation_Rate */
            $aItem = array();
            $aItem['value'] = $oTaxItem->getData('code');
            $aItem['label'] = $oTaxItem->getData('code') . ' - ' . sprintf('%.02f', $oTaxItem->getData('rate')) . '%';
            $aResult[] = $aItem;
        }

        return $aResult;
    }

}
