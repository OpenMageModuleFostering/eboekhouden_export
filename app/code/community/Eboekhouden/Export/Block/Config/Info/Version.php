<?php

class Eboekhouden_Export_Block_Config_Info_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $oHelper = Mage::helper('Eboekhouden_Export'); /* @var $oHelper Eboekhouden_Export_Helper_Data */
        ob_start();
?>
    <tr>
        <td class="label"><label><?php echo $oHelper->__('E-Boekhouden Extensie Versie'); ?></label></td>
        <td class="value"><?php echo Eboekhouden_Export_Model_Info::getExtensionVersion(); ?></td>
        <td class="scope-label">&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
<?php
        return ob_get_clean();
    }
}
