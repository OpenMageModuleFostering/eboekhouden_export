<?php

class Eboekhouden_Export_Model_Product_Attribute_Costcenter extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Retrieve All options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $aResult = array();
        if ( Mage::app()->getStore()->isAdmin() ) // Protection against unnecessary loading in frontend
        {
            if (!$this->_options)
            {
                $oImportModel = Mage::getModel('Eboekhouden_Export/import_costcenter');
                /* @var $oImportModel Eboekhouden_Export_Model_Import_Costcenter */
                $this->_options = $oImportModel->importCodesForDropdown();
            }
            $aResult = $this->_options;
        }
        return $aResult;
    }

}
