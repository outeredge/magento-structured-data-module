<?php

namespace OuterEdge\StructuredData\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ContactType implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'Organization', 'label' => __('Organization')], ['value' => 'LocalBusiness', 'label' => __('LocalBusiness')]];
    }
}