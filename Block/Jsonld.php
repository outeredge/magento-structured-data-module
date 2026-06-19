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

    /**
     * Structured-data base URL helper (per GEO fixes plan, Step 2).
     * Same as getBaseUrl() but exposes an explicit, plan-named accessor for
     * any callers that prefer the structured-data-specific name.
     */
    public function getStructuredDataBaseUrl(): string
    {
        return $this->getBaseUrl();
    }

    public function getOrganizationId(): string
    {
        return $this->getBaseUrl() . '/#org';
    }

    public function isCollectionPage(): bool
    {
        $type = $this->getPageType();
        if ($type === self::PAGE_TYPE_COLLECTIONPAGE) {
            return true;
        }

        // Hub pages: CMS pages whose identifier starts with "hub/" (per
        // GEO plan: confirmed CollectionPage recommendation for /hub/*).
        try {
            $page = $this->getPage();
            if ($page && method_exists($page, 'getIdentifier')) {
                $identifier = (string) $page->getIdentifier();
                if ($identifier !== '' && strncmp($identifier, 'hub/', 4) === 0) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return false;
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

    public function getStoreLocale(): string
    {
        try {
            $locale = (string) $this->_scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE
            );
            if ($locale !== '') {
                return str_replace('_', '-', $locale);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 'en-GB';
    }

    /**
     * Page description for the WebPage entity. Pulls from CMS meta, category
     * description, or product description depending on page type.
     */
    public function getPageDescription(): string
    {
        // CMS pages use the dedicated block's meta description.
        if ($this instanceof \OuterEdge\StructuredData\Block\Cms
            && method_exists($this, 'getMetaDescription')
        ) {
            $meta = (string) $this->getMetaDescription();
            if (trim($meta) !== '') {
                return trim((string) preg_replace('/\s+/', ' ', strip_tags($meta)));
            }
        }

        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);

            // Fall back to the cms_page registry entry for CMS pages whose
            // dedicated Cms subclass isn't being used as the main block.
            $cmsPage = $registry->registry('cms_page');
            if ($cmsPage && is_object($cmsPage) && method_exists($cmsPage, 'getMetaDescription')) {
                $meta = (string) $cmsPage->getMetaDescription();
                if (trim($meta) !== '') {
                    return trim((string) preg_replace('/\s+/', ' ', strip_tags($meta)));
                }
            }

            $category = $registry->registry('current_category');
            if ($category && is_object($category)) {
                $desc = (string) $category->getDescription();
                if (trim($desc) !== '') {
                    return trim((string) preg_replace('/\s+/', ' ', strip_tags($desc)));
                }
            }

            $product = $registry->registry('current_product');
            if ($product && is_object($product)) {
                $desc = (string) $product->getShortDescription();
                if (trim($desc) === '') {
                    $desc = (string) $product->getDescription();
                }
                if (trim($desc) !== '') {
                    return trim((string) preg_replace('/\s+/', ' ', strip_tags($desc)));
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return '';
    }

    /**
     * Page last-modified timestamp (ISO-8601) for dateModified.
     */
    public function getPageDateModified(): string
    {
        $raw = '';

        if ($this instanceof \OuterEdge\StructuredData\Block\Cms) {
            try {
                $page = $this->getPage();
                if ($page) {
                    $raw = (string) ($page->getUpdateTime() ?: $page->getCreationTime());
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if ($raw === '') {
            try {
                $registry = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Framework\Registry::class);

                // Fall back to the cms_page registry entry.
                $cmsPage = $registry->registry('cms_page');
                if ($cmsPage && is_object($cmsPage)) {
                    $raw = (string) (method_exists($cmsPage, 'getUpdateTime')
                        ? ($cmsPage->getUpdateTime() ?: '')
                        : '');
                    if ($raw === '' && method_exists($cmsPage, 'getCreationTime')) {
                        $raw = (string) ($cmsPage->getCreationTime() ?: '');
                    }
                }

                if ($raw === '') {
                    $category = $registry->registry('current_category');
                    if ($category && is_object($category)) {
                        $raw = (string) ($category->getUpdatedAt() ?: '');
                    }
                }
                if ($raw === '') {
                    $product = $registry->registry('current_product');
                    if ($product && is_object($product)) {
                        $raw = (string) ($product->getUpdatedAt() ?: '');
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        try {
            $dt = new \DateTimeImmutable($raw);
            return $dt->format(\DateTimeInterface::ATOM);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * primaryImageOfPage ImageObject (or empty array if not configured).
     */
    public function getPagePrimaryImage(): array
    {
        if ($this instanceof \OuterEdge\StructuredData\Block\Cms
            && method_exists($this, 'getImage')
        ) {
            $url = (string) $this->getImage();
            if ($url !== '') {
                return [
                    '@type' => 'ImageObject',
                    'url' => $url,
                ];
            }
        }

        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);
            $category = $registry->registry('current_category');
            if ($category && is_object($category) && method_exists($category, 'getImageUrl')) {
                $url = (string) $category->getImageUrl();
                if ($url !== '') {
                    return [
                        '@type' => 'ImageObject',
                        'url' => $url,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
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
            'inLanguage' => $this->getStoreLocale(),
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

        $description = $this->getOrganizationDescription();
        if ($description !== '') {
            $schema['description'] = $description;
        }

        return $schema;
    }

    /**
     * Build a brand description for the Organization node so AI search engines
     * have an explicit grounding for the brand entity. Prefers an admin-set
     * "general/store_information/description" config; falls back to the head
     * default description; otherwise synthesises a short statement from the
     * store name.
     */
    public function getOrganizationDescription(): string
    {
        $candidates = [
            'general/store_information/description',
            'design/head/default_description',
        ];
        foreach ($candidates as $path) {
            $value = trim((string) ($this->getConfig($path) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $name = trim((string) ($this->getConfig('general/store_information/name') ?? ''));
        return $name !== '' ? sprintf('%s — specialist retailer.', $name) : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function getWebsiteSchema(): array
    {
        $schema = [
            '@type' => 'WebSite',
            '@id' => $this->getWebsiteId(),
            'url' => $this->getBaseUrl() . '/',
            'name' => (string) ($this->getConfig('general/store_information/name') ?? ''),
            'inLanguage' => $this->getStoreLocale(),
            'publisher' => [
                '@id' => $this->getOrganizationId(),
            ],
        ];

        $search = $this->getSearchActionSchema();
        if ($search) {
            $schema['potentialAction'] = $search;
        }

        return $schema;
    }

    /**
     * Build a SearchAction for WebSite.potentialAction (sitelinks searchbox).
     * Returns [] when disabled or the search route is empty.
     *
     * @return array<string, mixed>
     */
    public function getSearchActionSchema(): array
    {
        $enabled = (int) $this->getConfig('structureddata/website/enable_search_action');
        if ($enabled === 0) {
            return [];
        }

        return [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $this->getBaseUrl() . '/catalogsearch/result/?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
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

        $list = $this->augmentBreadcrumbForCurrentPage($list);

        return $list;
    }

    /**
     * Ensure the BreadcrumbList JSON-LD always includes the current page as
     * the final ListItem on Hub/Collection pages, even when the rendered
     * Magento Breadcrumbs block only contains "Home". Without this, AI
     * search engines cannot infer the relationship between the homepage and
     * the deep category hub (e.g. /hub/surfboards).
     *
     * @param array<int, array<string, mixed>> $list
     * @return array<int, array<string, mixed>>
     */
    private function augmentBreadcrumbForCurrentPage(array $list): array
    {
        if (!$this->shouldAugmentBreadcrumb()) {
            return $list;
        }

        $currentUrl = $this->getCurrentUrl();
        $currentName = $this->resolveCurrentBreadcrumbName();
        if ($currentUrl === '' || $currentName === '') {
            return $list;
        }

        $normalised = rtrim($currentUrl, '/');
        foreach ($list as $entry) {
            $entryUrl = (string) ($entry['item'] ?? '');
            if ($entryUrl !== '' && rtrim($entryUrl, '/') === $normalised) {
                return $list;
            }
        }

        $nextPosition = 1;
        foreach ($list as $entry) {
            if (isset($entry['position']) && is_int($entry['position']) && $entry['position'] >= $nextPosition) {
                $nextPosition = $entry['position'] + 1;
            }
        }

        $list[] = [
            '@type' => 'ListItem',
            'position' => $nextPosition,
            'name' => $currentName,
            'item' => $currentUrl,
        ];

        return $list;
    }

    /**
     * Augment when on a Collection page (per the existing hub detection) or
     * on any request whose path starts with "hub/" — the latter covers
     * Storyblok-routed hub CMS pages which aren't picked up as CMS pages.
     */
    private function shouldAugmentBreadcrumb(): bool
    {
        if ($this->isCollectionPage()) {
            return true;
        }

        try {
            $path = (string) $this->getRequest()->getPathInfo();
        } catch (\Throwable $e) {
            return false;
        }

        return strncmp(ltrim($path, '/'), 'hub/', 4) === 0;
    }

    /**
     * Resolve the human-readable name for the current breadcrumb tail.
     * Prefers an explicit "page_title" data attr on this block, then the
     * head/title (skipping the generic "Boardshop" default), then a slug-
     * derived humanised label for hub/* paths.
     */
    private function resolveCurrentBreadcrumbName(): string
    {
        try {
            $explicit = trim((string) $this->getData('page_title'));
            if ($explicit !== '') {
                return $explicit;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $title = trim((string) $this->getPageTitle());
        if ($title !== '' && strcasecmp($title, 'Boardshop') !== 0) {
            return $title;
        }

        try {
            $path = trim((string) $this->getRequest()->getPathInfo(), '/');
        } catch (\Throwable $e) {
            return $title;
        }

        if ($path === '' || strncmp($path, 'hub/', 4) !== 0) {
            return $title;
        }

        $slug = substr($path, 4);
        $leaf = strrchr($slug, '/');
        $slug = $leaf !== false ? substr($leaf, 1) : $slug;
        $slug = str_replace(['-', '_'], ' ', $slug);

        return $slug !== '' ? ucwords($slug) : $title;
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
     * Build a `hasPart` ItemList for CollectionPage. Returns [] when the page
     * is not a collection page or there are no items to list. Caller is
     * responsible for omitting the field when this returns [].
     *
     * @return array<int, array<string, mixed>>|array<string, mixed>
     */
    public function getHasPartItemList(): array
    {
        if (!$this->isCollectionPage()) {
            return [];
        }

        // 1. If a Category block child has already been rendered, prefer its
        //    ItemList payload so we don't double-load the product collection.
        $additionalJson = '';
        try {
            $additionalJson = (string) $this->getChildHtml('main.entity');
        } catch (\Throwable $e) {
            $additionalJson = '';
        }
        if (trim($additionalJson) !== '') {
            $decoded = json_decode($additionalJson, true);
            if (is_array($decoded) && isset($decoded['hasPart']) && is_array($decoded['hasPart'])) {
                return $decoded['hasPart'];
            }
        }

        // 2. Walk current_category's product collection directly.
        $items = [];
        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);
            $category = $registry->registry('current_category');
            if ($category && is_object($category)) {
                $items = $this->buildHasPartFromCategory($category);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if (empty($items)) {
            return [];
        }

        return [
            '@type' => 'ItemList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return array<int, array<string, mixed>>
     */
    private function buildHasPartFromCategory($category): array
    {
        try {
            $limit = (int) $this->getConfig('structureddata/website/haspart_limit');
            if ($limit <= 0) {
                $limit = 20;
            }

            $productCollection = $category->getProductCollection();
            $productCollection->setPageSize($limit);
            $productCollection->setCurPage(1);
            $productCollection->addUrlRewrite();

            $position = 1;
            $items = [];
            foreach ($productCollection as $product) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'url' => (string) $product->getProductUrl(),
                    'name' => (string) $product->getName(),
                ];
            }
            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveBreadcrumbItems(): array
    {
        // 1. Use the rendered Magento Breadcrumbs block when available.
        //    This is the most reliable source for CMS pages (Hub pages).
        $items = $this->resolveFromBreadcrumbsBlock();
        if (!empty($items)) {
            return $items;
        }

        // 2. Walk the registry's category/product (the canonical chain).
        $items = $this->resolveFromRegistry();
        if (!empty($items)) {
            return $items;
        }

        // 3. Fallback: Home only.
        return [
            [
                'name' => 'Home',
                'url' => $this->getBaseUrl() . '/',
            ],
        ];
    }

    /**
     * Read the rendered Magento\Theme\Block\Html\Breadcrumbs block's _crumbs
     * array via reflection. Returns an empty array when the block hasn't been
     * populated yet (e.g. on pages with no breadcrumbs).
     *
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveFromBreadcrumbsBlock(): array
    {
        try {
            $layout = $this->getLayout();
            if (!$layout) {
                return [];
            }
            $block = $layout->getBlock('breadcrumbs');
            if (!$block) {
                return [];
            }
            $reflection = new \ReflectionClass($block);
            if (!$reflection->hasProperty('_crumbs')) {
                return [];
            }
            $prop = $reflection->getProperty('_crumbs');
            $prop->setAccessible(true);
            $crumbs = $prop->getValue($block);
            if (!is_array($crumbs) || empty($crumbs)) {
                return [];
            }

            $items = [];
            foreach ($crumbs as $crumb) {
                if (!is_array($crumb)) {
                    continue;
                }
                $label = isset($crumb['label']) ? strip_tags((string) $crumb['label']) : '';
                if ($label === '') {
                    continue;
                }
                $link = isset($crumb['link']) ? (string) $crumb['link'] : '';
                if ($link === '') {
                    // Last crumb has no link — use current URL.
                    $link = $this->getCurrentUrl();
                }
                $items[] = [
                    'name' => $label,
                    'url' => $link,
                ];
            }
            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Walk current_category / current_product in the registry to build a
     * breadcrumb chain (Home + ancestors + current entity).
     *
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveFromRegistry(): array
    {
        $items = [];

        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);

            $category = $registry->registry('current_category');
            if ($category && is_object($category)) {
                return $this->buildCategoryBreadcrumbItems($category);
            }

            $product = $registry->registry('current_product');
            if ($product && is_object($product)) {
                return $this->buildProductBreadcrumbItems($product);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $items;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return array<int, array{name: string, url: string}>
     */
    private function buildCategoryBreadcrumbItems($category): array
    {
        $items = [
            [
                'name' => 'Home',
                'url' => $this->getBaseUrl() . '/',
            ],
        ];

        try {
            $categoryRepository = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);

            $path = (string) $category->getPath();
            if ($path !== '') {
                $ids = array_filter(array_map('intval', explode('/', $path)));
                foreach ($ids as $id) {
                    if ($id <= 2) {
                        continue; // skip Roots
                    }
                    try {
                        $cat = $categoryRepository->get($id);
                        if (!$cat->getId()) {
                            continue;
                        }
                        $items[] = [
                            'name' => (string) $cat->getName(),
                            'url' => (string) $cat->getUrl(),
                        ];
                    } catch (\Throwable $e) {
                        // skip missing ancestor
                    }
                }
                return $items;
            }

            // No path attribute — emit Home + current category.
            $items[] = [
                'name' => (string) $category->getName(),
                'url' => (string) $category->getUrl(),
            ];
        } catch (\Throwable $e) {
            // ignore
        }

        return $items;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array<int, array{name: string, url: string}>
     */
    private function buildProductBreadcrumbItems($product): array
    {
        $items = [
            [
                'name' => 'Home',
                'url' => $this->getBaseUrl() . '/',
            ],
        ];

        try {
            $categoryIds = (array) $product->getCategoryIds();
            if (!empty($categoryIds)) {
                $categoryRepository = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);

                // Pick the deepest assigned category (max id) to mirror the
                // typical breadcrumb path.
                $deepestId = max(array_map('intval', $categoryIds));
                if ($deepestId > 0) {
                    try {
                        $cat = $categoryRepository->get($deepestId);
                        $path = (string) $cat->getPath();
                        if ($path !== '') {
                            $ids = array_filter(array_map('intval', explode('/', $path)));
                            foreach ($ids as $id) {
                                if ($id <= 2) {
                                    continue;
                                }
                                try {
                                    $c = $categoryRepository->get($id);
                                    if (!$c->getId()) {
                                        continue;
                                    }
                                    $items[] = [
                                        'name' => (string) $c->getName(),
                                        'url' => (string) $c->getUrl(),
                                    ];
                                } catch (\Throwable $e) {
                                    // skip
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $items[] = [
            'name' => (string) $product->getName(),
            'url' => (string) $product->getProductUrl(),
        ];

        return $items;
    }

    private function stripQueryString(string $url): string
    {
        $pos = strpos($url, '?');
        return $pos === false ? $url : substr($url, 0, $pos);
    }
}

