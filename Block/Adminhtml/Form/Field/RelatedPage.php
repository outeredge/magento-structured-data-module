<?php

namespace OuterEdge\StructuredData\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class RelatedPage extends AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn('url', ['label' => __('Full URL'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Related Web Page');
    }
}
