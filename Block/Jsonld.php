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
        return $this->getPageType() === self::PAGE_TYPE_COLLECTIONPAGE;
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
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);
            $product = $registry->registry('current_product');
            if ($product && is_object($product) && method_exists($product, 'getName')) {
                $name = trim((string) $product->getName());
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (\Throwable $e) {
            // Ignore.
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
     * description, or product description depending on page type. Long values
     * are truncated to 300 chars to keep the WebPage.description field a true
     * page summary (the Product node carries the full description).
     */
    public function getPageDescription(): string
    {
        $candidates = [];

        // CMS pages use the dedicated block's meta description.
        if ($this instanceof \OuterEdge\StructuredData\Block\Cms
            && method_exists($this, 'getMetaDescription')
        ) {
            $candidates[] = (string) $this->getMetaDescription();
        }

        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);

            $cmsPage = $registry->registry('cms_page');
            if ($cmsPage && is_object($cmsPage) && method_exists($cmsPage, 'getMetaDescription')) {
                $candidates[] = (string) $cmsPage->getMetaDescription();
            }

            $head = $this->getLayout() ? $this->getLayout()->getBlock('head') : false;
            if ($head && method_exists($head, 'getMetaDescription')) {
                $candidates[] = (string) $head->getMetaDescription();
            }

            $category = $registry->registry('current_category');
            if ($category && is_object($category)) {
                $candidates[] = (string) $category->getDescription();
            }

            $product = $registry->registry('current_product');
            if ($product && is_object($product)) {
                if (method_exists($product, 'getMetaDescription')) {
                    $candidates[] = (string) $product->getMetaDescription();
                }
                $candidates[] = (string) $product->getShortDescription();
                // Long description is only used if no shorter candidate exists.
            }
        } catch (\Throwable $e) {
            // ignore
        }

        foreach ($candidates as $raw) {
            $clean = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $raw)));
            if ($clean === '') {
                continue;
            }
            if (mb_strlen($clean) > 300) {
                $clean = rtrim(mb_substr($clean, 0, 300)) . '…';
            }
            return $clean;
        }

        // Final fallback: long product description, truncated.
        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);
            $product = $registry->registry('current_product');
            if ($product && is_object($product)) {
                $clean = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $product->getDescription())));
                if ($clean !== '') {
                    if (mb_strlen($clean) > 300) {
                        $clean = rtrim(mb_substr($clean, 0, 300)) . '…';
                    }
                    return $clean;
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

            // On product pages (no current_category), use the product's
            // main image so ItemPage.primaryImageOfPage is populated.
            $product = $registry->registry('current_product');
            if ($product && is_object($product) && method_exists($product, 'getImage')) {
                $url = (string) $product->getImage();
                if ($url !== '' && strncmp($url, 'no-selection', 12) !== 0) {
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
     * Build a brand description for the Organization node so AI search
     * engines have an explicit grounding for the brand entity. Prefers
     * an admin-set "general/store_information/description" config; falls
     * back to the head default description.
     *
     * Returns an empty string if no real description is configured. A
     * synthesised phrase like "{name} — specialist retailer." is
     * deliberately not used: it would be weighted by AI engines as a
     * real description but adds no real information, which dilutes
     * the Organization entity's GEO signal.
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

        return '';
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
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function getSameAsUrls(): array
    {
        $urls = [];
        $raw = (string) ($this->getConfig('structureddata/organization/sameas') ?? '');
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line !== '' && filter_var($line, FILTER_VALIDATE_URL)) {
                $urls[] = $line;
            }
        }

        // Preserve the module's existing Related Pages setting while also
        // supporting the newer one-URL-per-line setting.
        try {
            $relatedPages = $this->getConfig('structureddata/contact/related_pages');
            if ($relatedPages) {
                $serializer = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Framework\Serialize\SerializerInterface::class);
                foreach ((array) $serializer->unserialize($relatedPages) as $page) {
                    $url = is_array($page) ? trim((string) ($page['url'] ?? '')) : '';
                    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                        $urls[] = $url;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore malformed legacy configuration.
        }

        return array_values(array_unique($urls));
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
     * Augment the breadcrumb chain when on a Collection page so the JSON-LD
     * always includes the current page as the final ListItem.
     */
    private function shouldAugmentBreadcrumb(): bool
    {
        return $this->isCollectionPage();
    }

    /**
     * Resolve the human-readable name for the current breadcrumb tail.
     * Prefers an explicit "page_title" data attr on this block, then the
     * head/title.
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

        return trim((string) $this->getPageTitle());
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
        if (!$this->isCollectionPage()
            || !(int) $this->getConfig('structureddata/product/enable_category')
        ) {
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
        // On product pages, the rendered Magento Breadcrumbs block in
        // Hyvä is populated by the layout with whatever category the
        // registry's current_category happens to be set to (often a
        // brand category used for /brands/ filter pages). Prefer the
        // registry-based chain built from the product's own category
        // assignments so the JSON-LD always reflects user-facing
        // catalog taxonomy.
        if ($this->isProductPage()) {
            return $this->resolveFromRegistry();
        }

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

    private function isProductPage(): bool
    {
        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);
            return (bool) $registry->registry('current_product');
        } catch (\Throwable $e) {
            return false;
        }
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
     * On product pages, current_category is unreliable (it may be set to
     * a brand-filter category from prior navigation), so we always use
     * the product's own category assignments instead.
     *
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveFromRegistry(): array
    {
        $items = [];

        try {
            $registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Registry::class);

            $product = $registry->registry('current_product');
            if ($product && is_object($product)) {
                return $this->buildProductBreadcrumbItems($product);
            }

            $category = $registry->registry('current_category');
            if ($category && is_object($category)) {
                return $this->buildCategoryBreadcrumbItems($category);
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
            $categoryIds = array_values(array_filter(
                array_map('intval', $categoryIds),
                fn ($id) => $id > 2
            ));
            if (!empty($categoryIds)) {
                $categoryRepository = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);

                // Pick the deepest active assigned category.
                $bestId = 0;
                $bestDepth = -1;
                foreach ($categoryIds as $id) {
                    try {
                        $cat = $categoryRepository->get($id);
                    } catch (\Throwable $e) {
                        continue;
                    }
                    if (!$cat->getId() || (method_exists($cat, 'getIsActive') && !$cat->getIsActive())) {
                        continue;
                    }
                    $depth = substr_count((string) $cat->getPath(), '/');
                    if ($depth > $bestDepth) {
                        $bestDepth = $depth;
                        $bestId = (int) $cat->getId();
                    }
                }

                if ($bestId > 0) {
                    try {
                        $cat = $categoryRepository->get($bestId);
                        $path = (string) $cat->getPath();
                        if ($path !== '') {
                            $ids = array_filter(array_map('intval', explode('/', $path)));
                            foreach ($ids as $id) {
                                if ($id <= 2) {
                                    continue;
                                }
                                try {
                                    $c = $categoryRepository->get($id);
                                    if (!$c->getId() || (method_exists($c, 'getIsActive') && !$c->getIsActive())) {
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
