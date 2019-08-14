<?php

class Eboekhouden_Export_Model_Tax_Attribute_Ebtaxcode extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getOptionArray()
    {
        $aOptions = array();
        $aOptions['HOOG_VERK_21'] = 'BTW Hoog 21%';
        $aOptions['HOOG_VERK'] = 'BTW Hoog 19%';
        $aOptions['LAAG_VERK'] = 'BTW Laag 6%';
        $aOptions['VERL_VERK'] = 'BTW Verlegd';
        $aOptions['BU_EU_VERK'] = 'Levering naar buiten de EU 0%';
        $aOptions['BI_EU_VERK'] = 'Goederen naar binnen de EU 0%';
        //$aOptions['BI_EU_VERK_D'] = 'Diensten naar binnen de EU 0%';
        $aOptions['AFST_VERK'] = 'Afstandsverkopen binnen EU 0%';
        $aOptions['GEEN'] = 'Geen BTW van toepassing';
        return $aOptions;
    }

    /**
     * Retrieve All options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options)
        {
            $aOptions = $this->getOptionArray();

            $this->_options = array();
            foreach ($aOptions as $sKey => $sValue)
            {
                $aOption = array();
                $aOption['value'] = $sKey;
                $aOption['label'] = $sValue; # . ' ---- '.$sKey;
                $this->_options[] = $aOption;
            }
        }
        return $this->_options;
    }

}

?>