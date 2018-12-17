<?php

class Edge_StructuredData_Block_Contacts extends Mage_Core_Block_Template
{
    /**
     *
     * @return bool
     */
    public function isLocalBusiness(){
        return Mage::getStoreConfig('structureddata_product/contacts/type') == 'LocalBusiness';
    }
}