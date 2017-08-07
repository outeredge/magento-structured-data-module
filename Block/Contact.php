<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Store\Model\ScopeInterface;

class Contact extends Template
{
    /**
     * @var Logo
     */
    protected $_logo;

    /**
     * @param Context $context
     * @param Logo $logo
     * @param array $data
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Logo $logo,
        array $data = []
    ) {
        $this->_logo = $logo;
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
        return $this->_logo->getLogoSrc();
    }

    public function isLocalBusiness()
    {
        return $this->getConfig('structureddata/contact/type') == 'LocalBusiness';
    }

    public function getStreetAddress()
    {
        return implode(', ', array_map('trim', [
            $this->getConfig('general/store_information/street_line1'),
            $this->getConfig('general/store_information/street_line2')
        ]));
    }
}