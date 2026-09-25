<?php

namespace OuterEdge\StructuredData\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\AbstractBlock;

class CustomAttribute extends AbstractFieldArray
{
    /**
     * @var AbstractBlock|false
     */
    protected $_attributeRenderer = false;

    /**
     * Add the product attribute and JSON property name columns
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('attribute_code', [
            'label' => __('Product Attribute'),
            'renderer' => $this->getAttributeRenderer(),
        ]);
        $this->addColumn('json_key', [
            'label' => __('JSON Property Name'),
            'class' => 'required-entry validate-code',
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Custom Attribute');
    }

    /**
     * Mark the selected attribute option as selected for saved rows
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        $attributeCode = $row->getData('attribute_code');

        if ($attributeCode && $this->_attributeRenderer) {
            $options['option_' . $this->_attributeRenderer->calcOptionHash($attributeCode)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Create or return the cached attribute select renderer
     *
     * @return AbstractBlock
     */
    protected function getAttributeRenderer()
    {
        if ($this->_attributeRenderer === false) {
            $this->_attributeRenderer = $this->_layout->createBlock(AttributeColumn::class);
        }
        return $this->_attributeRenderer;
    }
}
