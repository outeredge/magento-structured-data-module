<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Store\Model\ScopeInterface;

class Organization extends Template
{
    /**
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        protected Logo $logo,
        protected SerializerInterface $serializer,
        array $data = []
    ) {
        $this->logo = $logo;
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
        return $this->logo->getLogoSrc();
    }

    public function isLocalBusiness()
    {
        return $this->getConfig('structureddata/contact/type') == 'LocalBusiness';
    }

    public function getStreetAddress()
    {
        return implode(', ', array_map('trim', [
            (string) $this->getConfig('general/store_information/street_line1'),
            (string) $this->getConfig('general/store_information/street_line2')
        ]));
    }

    public function getRelatedPages()
    {
        $relatedPages = $this->getConfig('structureddata/contact/related_pages');
        $pages        = $relatedPages ? $this->serializer->unserialize($relatedPages) : null;
        $result       = null;

        if ($pages) {
            $result = [];
            foreach ($pages as $page) {
                $result[] = $this->_escaper->escapeUrl($page['url']);
            }
            $result = json_encode($result);
        }

        return $result;
    }
}
