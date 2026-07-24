<?php

namespace OuterEdge\StructuredData\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ReturnMethod implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('Unspecified')],
            ['value' => 'ReturnByMail', 'label' => __('Return by mail')],
            ['value' => 'ReturnInStore', 'label' => __('Return in store')],
            ['value' => 'ReturnAtKiosk', 'label' => __('Return at kiosk')],
        ];
    }
}
