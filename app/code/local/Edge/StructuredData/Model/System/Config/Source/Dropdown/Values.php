<?php
class Edge_StructuredData_Model_System_Config_Source_Dropdown_Values
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'Organization',
                'label' => Mage::helper('core')->__('Organization')
            ),
            array(
                'value' => 'LocalBusiness',
                'label' => Mage::helper('core')->__('LocalBusiness')
            )
        );
    }

    /**
     * @return array
     */
    public function toOptions()
    {
        $options = $this->toOptionArray();
        $modes = array();
        for($i = 0; $i < count($options); $i++) {
            $modes[$options[$i]['value']] = $options[$i]['label'];
        }
        return $modes;
    }
}