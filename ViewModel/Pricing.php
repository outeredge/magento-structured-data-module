<?php

namespace OuterEdge\StructuredData\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Pricing\Helper\Data as DataHelper;

class Pricing implements ArgumentInterface
{
    private DataHelper $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(DataHelper $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    public function currency($value, $format, $includeContainer)
    {
        return $this->dataHelper->currency($value, $format, $includeContainer);
    }
}
