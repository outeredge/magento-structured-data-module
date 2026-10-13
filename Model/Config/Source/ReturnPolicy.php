<?php

namespace OuterEdge\StructuredData\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ReturnPolicy implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('Disabled')],
            ['value' => 'MerchantReturnFiniteReturnWindow', 'label' => __('Finite return window')],
            ['value' => 'MerchantReturnUnlimitedWindow', 'label' => __('Unlimited return window')],
            ['value' => 'MerchantReturnNotPermitted', 'label' => __('Returns not permitted')],
        ];
    }
}
