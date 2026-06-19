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
use Magento\Framework\UrlInterface;
use OuterEdge\StructuredData\Block\Jsonld\FaqCollector;

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
     * @param FaqCollector $faqCollector
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
        protected FaqCollector $faqCollector,
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

    public function getFaqCollector(): FaqCollector
    {
        return $this->faqCollector;
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

    public function getBaseUrl(): string
    {
        return rtrim($this->_storeManager->getStore()->getBaseUrl(), '/');
    }

    public function getOrganizationId(): string
    {
        return $this->getBaseUrl() . '/#org';
    }

    public function getWebsiteId(): string
    {
        return $this->getBaseUrl() . '/#website';
    }

    public function getCurrentUrl(): string
    {
        $url = $this->_request->getUriString();
        if ($url === '') {
            $url = $this->_storeManager->getStore()->getCurrentUrl(false);
        }
        return $this->stripQueryString((string) $url);
    }

    public function getBreadcrumbId(): string
    {
        return $this->getCurrentUrl() . '#breadcrumb';
    }

    public function getPageId(): string
    {
        return $this->getCurrentUrl() . '#webpage';
    }

    public function getPageTitle(): string
    {
        try {
            $head = $this->getLayout() ? $this->getLayout()->getBlock('head') : false;
            if ($head && method_exists($head, 'getTitle')) {
                $title = (string) $head->getTitle();
                if ($title !== '') {
                    return $title;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to other sources.
        }

        try {
            $config = $this->_scopeConfig;
            $title = (string) $config->getValue('design/head/default_title');
            if ($title !== '') {
                return $title;
            }
        } catch (\Throwable $e) {
            // Ignore.
        }

        return '';
    }

    public function getFaqId(): string
    {
        return $this->getCurrentUrl() . '#faq';
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrganizationSchema(): array
    {
        $schema = [
            '@type' => 'Organization',
            '@id' => $this->getOrganizationId(),
            'name' => (string) ($this->getConfig('general/store_information/name') ?? ''),
            'url' => $this->getBaseUrl() . '/',
        ];

        $logo = $this->getStoreLogoUrl();
        if ($logo) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo,
            ];
        }

        $sameAs = $this->getSameAsUrls();
        if ($sameAs) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function getWebsiteSchema(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $this->getWebsiteId(),
            'url' => $this->getBaseUrl() . '/',
            'name' => (string) ($this->getConfig('general/store_information/name') ?? ''),
            'publisher' => [
                '@id' => $this->getOrganizationId(),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getSameAsUrls(): array
    {
        $raw = (string) ($this->getConfig('structureddata/organization/sameas') ?? '');
        if (trim($raw) === '') {
            return [];
        }

        $urls = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $urls[] = $line;
            }
        }

        return $urls;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBreadcrumbListSchema(): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $this->getBreadcrumbId(),
            'itemListElement' => $this->getBreadcrumbItems(),
        ];
    }

    /**
     * Returns the visible breadcrumb items as ListItem entries. Reads the
     * rendered breadcrumbs block where possible so the schema mirrors what
     * the user sees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBreadcrumbItems(): array
    {
        $items = $this->resolveBreadcrumbItems();

        $list = [];
        $position = 1;
        foreach ($items as $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFaqPageSchema(): array
    {
        $items = $this->faqCollector->getItems();

        $mainEntity = [];
        foreach ($items as $item) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $this->getFaqId(),
            'mainEntity' => $mainEntity,
        ];
    }

    public function hasFaqSchema(): bool
    {
        return $this->faqCollector->hasItems();
    }

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveBreadcrumbItems(): array
    {
        $items = [];

        $items[] = [
            'name' => 'Home',
            'url' => $this->getBaseUrl() . '/',
        ];

        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);
            $category = $registry->registry('current_category');
            if ($category && is_object($category)) {
                $items[] = [
                    'name' => (string) $category->getName(),
                    'url' => (string) $category->getUrl(),
                ];
            }
            $product = $registry->registry('current_product');
            if ($product && is_object($product)) {
                $items[] = [
                    'name' => (string) $product->getName(),
                    'url' => (string) $product->getProductUrl(),
                ];
            }
        } catch (\Throwable $e) {
            // Registry not available; return Home only.
        }

        return $items;
    }

    private function stripQueryString(string $url): string
    {
        $pos = strpos($url, '?');
        return $pos === false ? $url : substr($url, 0, $pos);
    }
}

