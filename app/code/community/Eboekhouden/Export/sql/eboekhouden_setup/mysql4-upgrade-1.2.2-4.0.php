<?php
/**
 * NOTICE OF LICENSE
 *
 * The MIT License
 *
 * Copyright (c) 2010 e-Boekhouden.nl
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
 * @copyright  Copyright (c) 2010 e-Boekhouden.nl
 * @license    http://opensource.org/licenses/mit-license.php  The MIT License
 * @author     e-Boekhouden.nl
 */

$oInstaller = $this;
/* @var $oInstaller Mage_Core_Model_Resource_Setup */

$oConnection = $oInstaller->getConnection();
/* @var $oConnection Varien_Db_Adapter_Pdo_Mysql */

// Fix for error in 1.2.2 breaking product name in overviews
$oSession = Mage::getSingleton('core/session');
/* @var $oSession Mage_Adminhtml_Model_Session */
$oCoreSetup = new Mage_Eav_Model_Entity_Setup('core_setup');

if ($oConnection->tableColumnExists('eav_entity_type', 'entity_attribute_collection'))
{
    $oCoreSetup->updateEntityType('catalog_product', 'entity_attribute_collection',
                                  'catalog/product_attribute_collection');
    $oSession->addNotice(Mage::helper('Eboekhouden_Export')->__('Aanpasing voor 1.2.2 uitgevoerd.'));
}

require_once dirname(__FILE__) . '/includes/install-current.php';
