<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Theme\ViewModel\Block\Html\Header\LogoPathResolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\App\Request\Http;

class Jsonld extends Template
{
    public const PAGE_TYPE_WEBSITE        = "WebSite";
    public const PAGE_TYPE_WEBPAGE        = "WebPage";
    public const PAGE_TYPE_ABOUTPAGE      = "AboutPage";
    public const PAGE_TYPE_SEARCHPAGE     = "SearchResultsPage";
    public const PAGE_TYPE_COLLECTIONPAGE = "CollectionPage";
    public const PAGE_TYPE_ITEMPAGE       = "ItemPage";
    public const PAGE_TYPE_CONTACTPAGE    = "ContactPage";
    public const PAGE_TYPE_CHECKOUTPAGE   = "CheckoutPage";

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var Page
     */
    protected $_page;

    /**
     * @var Logo
     */
    protected $_logo;

    /**
     * @var string
     */
    protected $_pageType;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param Page $page
     * @param Logo $logo
     * @param LogoPathResolver $logoPathResolver
     * @param string $pageType
     * @param array $data
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
        array $data = []
    ) {
        $logo->setData('logoPathResolver', $logoPathResolver);

        $this->_logo = $logo;
        $this->_page = $page;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_pageType = null;

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

    public function getPage()
    {
        return $this->_page;
    }

    public function getPageType()
    {
        if ($this->_pageType == null) {
            $module     = $this->_request->getModuleName();
            $controller = $this->_request->getControllerName();

            switch ($module) {
                case 'catalog':
                    if ($controller == 'product') {
                        $this->_pageType = $this::PAGE_TYPE_ITEMPAGE;
                        break;
                    }
                    $this->_pageType = $this::PAGE_TYPE_COLLECTIONPAGE;
                    break;
                case 'catalogsearch':
                    $this->_pageType = $this::PAGE_TYPE_SEARCHPAGE;
                    break;
                case 'contact':
                    $this->_pageType = $this::PAGE_TYPE_CONTACTPAGE;
                    break;
                case 'checkout':
                    $this->_pageType = $this::PAGE_TYPE_CHECKOUTPAGE;
                    break;
                case 'cms':
                    if ($controller == 'index') {
                        $this->_pageType = $this::PAGE_TYPE_WEBSITE;
                        break;
                    }
                    $this->_pageType = $this::PAGE_TYPE_WEBPAGE;
                    break;
                default:
                    $this->_pageType = $this::PAGE_TYPE_WEBPAGE;
            }

            $this->_pageType = $this->isAboutPage() ? $this::PAGE_TYPE_ABOUTPAGE : $this->_pageType;

        }
        return $this->_pageType;
    }

    public function getStoreLogoUrl()
    {
        return $this->_logo->getLogoSrc();
    }

    public function isAboutPage()
    {
        $aboutPage = $this->getConfig('structureddata/cms/about_page');
        if (empty($aboutPage)) {
            return false;
        }
        
        $currentPage = $this->getPage()->getIdentifier();

        if ($this->getConfig('structureddata/cms/enable_about') && $currentPage == $aboutPage) {
            return true;
        }

        return false;
    }
}
