<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Contact extends Template
{
    /**
     * @var \Magento\Theme\Block\Html\Header\Logo
     */
    protected $_logoBlock;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Theme\Block\Html\Header\Logo $logoBlock
     * @param array $data
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Theme\Block\Html\Header\Logo $logoBlock,
        array $data = []
    ) {
        $this->_logoBlock = $logoBlock;
        parent::__construct($context, $data);
    }
    
    public function getConfig($config)
    {
        return $this->_scopeConfig->getValue($config, ScopeInterface::SCOPE_STORE);
    }
    
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }
    
    public function getStoreLogoUrl()
    {
        return $this->_logoBlock->getLogoSrc();
    }
    
    public function isLocalBusiness()
    {
        return $this->getConfig('structureddata/contact/type') == 'LocalBusiness';
    }
}