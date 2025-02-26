<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Template\Context;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Theme\ViewModel\Block\Html\Header\LogoPathResolver;
use OuterEdge\StructuredData\Block\Jsonld;
use DOMDocument;

class Cms extends Jsonld
{
    /**
     * @var FilterProvider
     */
    protected $_filterProvider;

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
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Http $request,
        Page $page,
        Logo $logo,
        LogoPathResolver $logoPathResolver,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->_filterProvider = $filterProvider;

        parent::__construct(
            $context,
            $storeManager,
            $request,
            $page,
            $logo,
            $logoPathResolver,
            $data
        );
    }

    public function getTitle()
    {
        $title = $this->getPageType() == Jsonld::PAGE_TYPE_WEBSITE ? $this->getConfig('general/store_information/name') . ' | ' : null;

        if ($this->getPage()->getContentHeading()) {
            return $title . nl2br($this->getPage()->getContentHeading());
        }
        return $title . nl2br((string) $this->getPage()->getTitle());
    }

    public function getContent()
    {
        if (!$this->_content) {
            $content = $this->_filterProvider->getPageFilter()->filter($this->getPage()->getContent());
            $content = nl2br((string) $content);
            if ($content) {
                $content = preg_replace('/([\r\n\t])/', ' ', $content);
            }
            $this->_content = $content;
        }
        return $this->_content;
    }

    public function getMetaDescription()
    {
        if (!$this->_metaDescription) {
            $this->_metaDescription = nl2br((string) $this->getPage()->getMetaDescription());
        }
        return $this->_metaDescription;
    }

    public function getImage()
    {
        if ($this->_image === null) {
            $this->_image = false;

            $imageTypeArray = [
                'image',
                'small_image',
                'thumbnail',
                'primary_image',
                'secondary_image',
                'tertiary_image'
            ];

            foreach ($imageTypeArray as $image) {
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

    /**
     * Finds first image within CMS page content
     */
    protected function setImageFromContent()
    {
        $content = $this->getContent();

        if($content) {
            $doc = new DOMDocument();

            libxml_use_internal_errors(true);
            $doc->loadHtml($content);

            $tags = $doc->getElementsByTagName('img');
            if ($tags->length > 0) {
                foreach ($tags as $tag) {
                    $this->_image = $tag->getAttribute('src');
                    break;
                }
            }
        }
    }
}
