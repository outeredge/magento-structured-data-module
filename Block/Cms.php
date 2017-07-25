<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\UrlInterface;
use DOMDocument;

class Cms extends Template
{
    /**
     * @var \Magento\Cms\Model\Page
     */
    protected $_page;
    
    /**
     * @var string
     */
    protected $_content;
    
    /**
     * @var string
     */
    protected $_metaDescription;
    
    /**
     * @var string
     */
    protected $_image = null;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Cms\Model\Page $page
     * @param array $data
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Cms\Model\Page $page,
        array $data = []
    ) {
        $this->_page = $page;
        parent::__construct($context, $data);
    }
    
    public function getPage()
    {
        return $this->_page;
    }
    
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }
    
    public function getTitle()
    {
        if ($this->getPage()->getContentHeading()) {
            return nl2br($this->getPage()->getContentHeading());
        }
        return nl2br($this->getPage()->getTitle());
    }
    
    public function getContent()
    {
        if (!$this->_content) {
            $content = nl2br($this->getPage()->getContent());
            $this->_content = preg_replace('/([\r\n\t])/',' ', $content);
        }
        return $this->_content;
    }
    
    public function getMetaDescription()
    {
        if (!$this->_metaDescription) {
            $this->_metaDescription = nl2br($this->getPage()->getMetaDescription());
        }
        return $this->_metaDescription;
    }
    
    public function getImage()
    {
        if ($this->_image === null) {
            $this->_image = false;
            
            foreach(['image', 'small_image', 'thumbnail', 'primary_image', 'secondary_image', 'tertiary_image'] as $image) {
                if ($this->getPage()->getData($image)) {
                    $this->_image = $this->getPage()->getData($image);
                    break;
                }  
            }
            
            if (!$this->_image) {
                $this->setImageFromContent();
            }
            
            if ($this->_image) {
                if (!preg_match('/^http/', $this->_image)) {
                    if (preg_match('/^\/?media/', $this->_image)) {
                        $this->_image = $this->getStore()->getBaseUrl() . $this->_image;
                    } else {
                        $this->_image = $this->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $this->_image;
                    }
                }
            }
        }
        return $this->_image;
    }
    
    protected function setImageFromContent()
    {
        $doc = new DOMDocument();
        @$doc->loadHtml($this->getContent());
        $tags = $doc->getElementsByTagName('img');
        if (!empty($tags)) {
            $this->_image = $tags[0]->getAttribute('src');
        }
    }
}