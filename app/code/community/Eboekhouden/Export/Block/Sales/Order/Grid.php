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

class Eboekhouden_Export_Block_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{

    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();

        $this->getMassactionBlock()->addItem(
            'eboekhouden',
            array('label' => Mage::helper('Eboekhouden_Export')->__('Export naar e-Boekhouden.nl'),
                 'url' => $this->getUrl('eboekhouden/export_sale/orderexport'))
        );

        $sExtensionFile = BP . DS . 'app' . DS . 'code' . DS . 'community' . DS . 'BoutikCircus' . DS . 'DeleteOrders' . DS . 'Block' . DS . 'Grid.php';
        if (file_exists($sExtensionFile))
        {
            require_once $sExtensionFile;
            $iPrevErrRep = error_reporting();
            error_reporting($iPrevErrRep & ~E_STRICT); // prevent 'should not be called statically' error
            BoutikCircus_DeleteOrders_Block_Grid::_prepareMassaction();
            error_reporting($iPrevErrRep);
        }
    }

}

?>