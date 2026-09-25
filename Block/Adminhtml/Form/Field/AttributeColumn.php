<?php

namespace OuterEdge\StructuredData\Block\Adminhtml\Form\Field;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class AttributeColumn extends Select
{
    /**
     * @var CollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $attributeCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $attributeCollectionFactory,
        array $data = []
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Set the input name for the field array cell
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render the attribute select
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getAttributeOptions());
        }
        $this->setExtraParams('class="admin__control-select required-entry"');
        return parent::_toHtml();
    }

    /**
     * Build options from all product attributes
     *
     * @return array
     */
    protected function getAttributeOptions()
    {
        $collection = $this->attributeCollectionFactory->create();
        $collection->setOrder('frontend_label', 'ASC');
        $collection->setOrder('attribute_code', 'ASC');

        $options = [];
        foreach ($collection as $attribute) {
            $label = $attribute->getFrontendLabel() ?: $attribute->getAttributeCode();
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $label . ' (' . $attribute->getAttributeCode() . ')'
            ];
        }

        return $options;
    }
}
