<?php

namespace OuterEdge\StructuredData\Model\Type;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Data as TaxHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Model\Stock;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as RatingOptionVoteFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class Product
{
    /**
     * @var ProductModel
     */
    public $_product = null;

    /**
     * @var Summary
     */
    protected $_reviewData = null;

    /**
     * @var int
     */
    protected $reviewsCount = null;

    /**
     * @var string
     */
    protected $brand = null;

    /**
     * @var float
     */
    protected $weight = null;

    /**
     * @var float
     */
    protected $minWeight = null;

    /**
     * @var float
     */
    protected $maxWeight = null;

    /**
     * @var float
     */
    protected $minPrice = null;

    /**
     * @var float
     */
    protected $maxPrice = null;

    /**
     * @param Escaper $escaper,
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     * @param SummaryFactory $reviewSummaryFactory
     * @param RatingOptionFactory $ratingOptionFactory
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param ModuleManager $moduleManager
     * @param ImageHelper $imageHelper
     * @param PricingHelper $pricingHelper
     */
	public function __construct(
        protected Escaper $_escaper,
        protected ScopeConfigInterface $_scopeConfig,
        protected StoreManagerInterface $_storeManager,
        protected ProductFactory $_productFactory,
        protected StockStateInterface $_stockState,
        protected SummaryFactory $_reviewSummaryFactory,
        protected RatingOptionVoteFactory $_ratingOptionVoteFactory,
        protected ReviewCollectionFactory $_reviewCollectionFactory,
        protected ModuleManager $_moduleManager,
        protected ImageHelper $imageHelper,
        protected PricingHelper $pricingHelper,
        protected TaxHelper $taxHelper,
        protected ProductRepositoryInterface $productRepository,
        protected Template $template,
        protected Registry $registry,
        protected CategoryRepositoryInterface $categoryRepository
	) {
	}

    public function getSchemaData(ProductModel $product)
    {
        $this->resetProductState();
        $this->_product = $product;

        $data = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "@id" => $this->getCanonicalProductUrl($this->_product)."#Product",
            "url" => $this->getCanonicalProductUrl($this->_product),
            "name" => $this->escapeQuote((string)strip_tags($this->_product->getName())),
            "sku" => $this->escapeQuote((string)strip_tags($this->_product->getSku())),
            "description" => $this->escapeHtml((string)$this->template->stripTags($this->getDescription())),
            "image" => $this->escapeUrl(strip_tags($this->getImageUrl($this->_product, 'product_page_image_medium')))
        ];

        if ($this->getBrand()) {
            $data['brand'] = [
                "@type" => "Brand",
                "name" => $this->escapeQuote((string)strip_tags($this->getBrand()))
            ];
        }

        if ($this->_moduleManager->isEnabled('Magento_Review') &&
            $this->getConfig('structureddata/product/include_reviews') &&
            !$this->getConfig('structureddata/product/include_reviewsio') &&
            $this->getReviewsCount()
        ){
            $data['aggregateRating'] = [
                "@type" => "AggregateRating",
                "bestRating" => "100",
                "worstRating" => "1",
                "ratingValue" => $this->escapeQuote((string)$this->getReviewsRating()),
                "reviewCount" => $this->escapeQuote((string)$this->getReviewsCount())
            ];

            $data['review'] = [];
            foreach ($this->getReviewCollection() as $review) {
                $votes = $this->getVoteCollection($review->getId());

                $averageRating = 0;
                $ratingCount = count($votes);
                foreach ($votes as $vote) {
                    $averageRating = $averageRating + $vote->getValue();
                }

                $reviewData = [
                    "@type" => "Review",
                    "author" => [
                    "@type" => "Person",
                    "name" => $this->escapeQuote((string)$review->getData('nickname'))
                    ],
                    "datePublished" => $this->escapeQuote($review->getCreatedAt()),
                    "name" => $this->escapeQuote((string)$review->getTitle()),
                    "reviewBody" => $this->escapeQuote((string)$review->getDetail())
                ];

                if ($ratingCount > 0) {
                    $finalRating = $averageRating / $ratingCount;

                    $reviewData["reviewRating"] = [
                        "@type" => "Rating",
                        "ratingValue" => $finalRating,
                        "bestRating" => "5",
                        "worstRating" => "1"
                    ];
                }

                $data['review'][] = $reviewData;
            }
        }

        if ($gtin = trim((string) strip_tags((string) $this->getGtin()))) {
            $normalizedGtin = preg_replace('/\D/', '', $gtin) ?: '';
            $len = strlen($normalizedGtin);
            $key = match (true) {
                $len === 8 => 'gtin8',
                $len === 12 => 'gtin12',
                $len === 13 => 'gtin13',
                $len === 14 => 'gtin14',
                default => 'gtin',
            };
            $data['gtin'] = $this->escapeQuote($gtin);
            if ($key !== 'gtin' && preg_match('/^[\d\s-]+$/', $gtin)) {
                $data[$key] = $normalizedGtin;
            }
        }

        if ($this->getMpn()) {
            $data['mpn'] = $this->escapeQuote((string)strip_tags($this->getMpn()));
        }

        if ($this->getIsbn()) {
            $data['isbn'] = $this->escapeQuote((string)strip_tags($this->getIsbn()));
        }

        if ($size = $this->getSize()) {
            $data['size'] = $this->escapeQuote((string) strip_tags($size));
        }
        if ($color = $this->getColor()) {
            $data['color'] = $this->escapeQuote((string) strip_tags($color));
        }
        if ($material = $this->getMaterial()) {
            $data['material'] = $this->escapeQuote((string) strip_tags($material));
        }

        if ($category = $this->getProductCategory()) {
            $data['category'] = $category;
        }

        if ($this->getKeywords()) {
            $data['keywords'] = $this->escapeQuote((string)strip_tags($this->getKeywords()));
        }

        if ($this->_product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            || !$this->getConfig('structureddata/product/include_children')
        ) {
            $this->weight = $this->_product->getWeight();
            $data = $this->includeWeight($data);
            $data['offers'] = $this->getOffer($this->_product);
        }

        return $data;
    }

    protected function includeWeight($data)
    {
        if ($this->getConfig('structureddata/product/include_weight')) {
            $data['weight'] = [
                '@type' => "QuantitativeValue",
                'unitText' => $this->escapeQuote($this->getConfig('general/locale/weight_unit'))
            ];

            if ($this->weight !== null) {
                $data['weight']['value'] = $this->weight;
            }
            if ($this->minWeight !== null) {
                $data['weight']['minValue'] = $this->minWeight;
            }
            if ($this->maxWeight !== null) {
                $data['weight']['maxValue'] = $this->maxWeight;
            }
        }

        return $data;
    }

    public function getChildOffers($product)
    {
        if ($this->getConfig('structureddata/product/hide_price')) {
            return null;
        }

        $this->resetProductState();
        $this->_product = $product;

        $children = $this->getChildren();
        if ($children) {
            $offers = $data = [];
            $lastKey = key(array_slice($children, -1, 1, true));

            foreach ($children as $key => $_childProduct) {
                $productFinalPrice = $_childProduct->getFinalPrice();
                $productWeight     = $_childProduct->getWeight();

                $this->minPrice  = $productFinalPrice < $this->minPrice || $this->minPrice === null ? $productFinalPrice : $this->minPrice;
                $this->maxPrice  = $productFinalPrice > $this->maxPrice || $this->maxPrice === null ? $productFinalPrice : $this->maxPrice;

                $this->minWeight = $productWeight < $this->minWeight || $this->minWeight === null ? $productWeight : $this->minWeight;
                $this->maxWeight = $productWeight > $this->maxWeight || $this->maxWeight === null ? $productWeight : $this->maxWeight;

                $offers[] = $this->getOffer($_childProduct);
                $offers[$key]['sku'] = $_childProduct->getSku();

                if ($_childProduct->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
                    $offers[$key]['url'] = $this->getCanonicalProductUrl($this->_product);
                }
                $key == $lastKey ? '' : ',';
            }

            $data['offers'] = [
                '@type' => "AggregateOffer",
                'priceCurrency' => $this->escapeQuote($this->getStore()->getCurrentCurrency()->getCode()),
                'offerCount' => is_countable($children) ? count($children) : 0,
                'offers' => $offers
            ];

            if ($this->minWeight == $this->maxWeight) {
                $this->weight = $this->minWeight;
                $this->minWeight = null;
                $this->maxWeight = null;
            }

            if ($product->getTypeId() == 'bundle') {
                $rangeBundle = $this->getBundlePriceRange($this->_product->getId());
                $this->minPrice = $rangeBundle['minPrice']->getValue();
                $this->maxPrice = $rangeBundle['maxPrice']->getValue();
            }

            $minPricewithTax = $this->taxHelper->getTaxPrice($this->_product, $this->minPrice, $this->checkTaxIncluded());
            $maxPricewithTax = $this->taxHelper->getTaxPrice($this->_product, $this->maxPrice, $this->checkTaxIncluded());

            $data['offers']['lowPrice'] = $this->escapeQuote((string)$this->pricingHelper->currency($minPricewithTax, false, false));
            $data['offers']['highPrice'] = $this->escapeQuote((string)$this->pricingHelper->currency($maxPricewithTax, false, false));
            $data = $this->includeWeight($data);
        }

        return $data;
    }

    public function getOffer(ProductModel $product)
    {
        if ($this->getConfig('structureddata/product/hide_price')) {
            return null;
        }

        $availability      = 'OutOfStock';
        $product           = $this->productRepository->getById($product->getId());
        $quantityAvailable = $this->_stockState->getStockQty($product->getId());
        $backorderStatus   = null;

        if ($stockItem = $product->getExtensionAttributes()->getStockItem()) {
            $backorderStatus = $stockItem->getBackorders();
        }

        if ($product->isAvailable()) {
            $availability = 'InStock';

            if ($quantityAvailable <= 0 && $backorderStatus == Stock::BACKORDERS_YES_NOTIFY) {
                $availability = 'BackOrder';
            }
        }

        $priceWithTax = $this->taxHelper->getTaxPrice($product, $product->getPrice(), $this->checkTaxIncluded());
        $finalPriceWithTax = $this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), $this->checkTaxIncluded());

        $data = [
            "@type" => "Offer",
            "url" => $this->getCanonicalProductUrl($product),
            "price" => $this->escapeQuote((string)$this->pricingHelper->currency($finalPriceWithTax, false, false)),
            "priceCurrency" => $this->escapeQuote($this->getStore()->getCurrentCurrency()->getCode()),
            "availability" => "http://schema.org/$availability",
            "itemCondition" => "http://schema.org/NewCondition"
        ];

        $returnPolicy = $this->getMerchantReturnPolicy();
        if ($returnPolicy !== []) {
            $data['hasMerchantReturnPolicy'] = $returnPolicy;
        }

        if ($product->getFinalPrice() < $product->getPrice()) {
            if ($product->getSpecialToDate()) {
                $priceToDate = date_create($product->getSpecialToDate());
                $data['priceValidUntil'] = $this->escapeQuote($priceToDate->format('Y-m-d'));
            }

            $data['priceSpecification'] = [
                "@type" => "UnitPriceSpecification",
                "priceType" => "https://schema.org/StrikethroughPrice",
                "price" => $this->escapeQuote((string)$this->pricingHelper->currency($priceWithTax, false, false)),
                "priceCurrency" => $this->escapeQuote($this->getStore()->getCurrentCurrency()->getCode()),
                "valueAddedTaxIncluded" => $this->escapeQuote($this->checkTaxIncluded() ? 'true' : 'false')
            ];
        }

        return $data;
    }

    public function getDescription()
    {
        if ($this->getConfig('structureddata/product/use_short_description')
            && $this->_product->getShortDescription()) {
            $description = nl2br($this->_product->getShortDescription());
        } else {
            $description = nl2br((string) $this->_product->getDescription());
        }

        if ($description) {
            $description = preg_replace('/([\r\n\t])/', ' ', $description);
        }

        return substr($description, 0, 5000);
    }

    public function getBrand()
    {
        if ($this->brand === null) {
            if ($value = $this->getBrandFieldFromConfig()) {
                if ($this->_product->getData($value)) {
                    $this->brand = $this->getAttributeText($value);
                }
            }

            if ($this->brand === null) {
                if ($this->_product->getBrand()) {
                    $this->brand = $this->getAttributeText('brand');
                } elseif ($this->_product->getManufacturer()) {
                    $this->brand = $this->getAttributeText('manufacturer');
                } else {
                    $this->brand = false;
                }
            }
        }

        return $this->brand;
    }

    public function getGtin()
    {
        if ($field = $this->getConfig('structureddata/product/product_gtin_field')) {
            if (!empty($this->_product->getData($field))) {
                return $this->getAttributeText($field);
            }
        }
        return false;
    }

    public function getMpn()
    {
        if ($field = $this->getConfig('structureddata/product/field_mpn')) {
            if (!empty($this->_product->getData($field))) {
                return $this->getAttributeText($field);
            }
        }
        return false;
    }

    public function getIsbn()
    {
        if ($field = $this->getConfig('structureddata/product/field_isbn')) {
            if (!empty($this->_product->getData($field))) {
                return $this->getAttributeText($field);
            }
        }
        return false;
    }

    public function getColor()
    {
        if ($field = $this->getConfig('structureddata/product/field_color')) {
            if (!empty($this->_product->getData($field))) {
                return $this->getAttributeText($field);
            }
        } elseif ($this->_product->getColor()) { // removing these lines will require a major version bump
            return $this->getAttributeText('color');
       	} elseif ($this->_product->getColour()) {
            return $this->getAttributeText('colour');
        }

        return false;
    }

    public function getSize()
    {
        if ($field = $this->getConfig('structureddata/product/field_size')) {
            if (!empty($this->_product->getData($field))) {
                return $this->getAttributeText($field);
            }
        }
        return false;
    }

    public function getMaterial()
    {
        if ($field = $this->getConfig('structureddata/product/field_material')) {
            if (!empty($this->_product->getData($field))) {
                return $this->getAttributeText($field);
            }
        }
        return false;
    }

    public function getKeywords()
    {
        if ($field = $this->getConfig('structureddata/product/field_keywords')) {
            if (!empty($this->_product->getData($field))) {
                return $this->getAttributeText($field);
            }
        }
        return false;
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return string
     */
    public function getImageUrl($product, $imageId)
    {
        return $this->imageHelper->init($product, $imageId)->getUrl();
    }

    public function getAttributeText($attribute)
    {
        $attributeText = $this->_product->getAttributeText($attribute);
        if ($attributeText === false) {
            $attributeText = $this->_product->getData($attribute);
        }

        if (is_array($attributeText)) {
            $attributeText = implode(', ', $attributeText);
        }
        return $attributeText;
    }

    public function getReviewsRating()
    {
        if ($data = $this->getYotpoProductSnippet()) {
            $ratingSummary = $data['average_score'];
        } else {
            $ratingSummary = !empty($this->getReviewData()) ? $this->getReviewData()->getRatingSummary() : 1;
        }

        return $ratingSummary;
    }

    public function getReviewsCount()
    {
        if ($this->reviewsCount === null) {
            if ($data = $this->getYotpoProductSnippet()) {
                $reviewCount = $data['reviews_count'] ?? null;
            } else {
                $reviewCount = !empty($this->getReviewData()) ? $this->getReviewData()->getReviewsCount() : null;
            }

            $this->reviewsCount = $reviewCount;
        }

        return $this->reviewsCount;
    }

    public function getReviewData()
    {
        if ($this->_reviewData === null) {
            $this->_reviewData = $this->_reviewSummaryFactory->create()->load($this->_product->getId());
        }
        return $this->_reviewData;
    }

    public function getReviewCollection()
    {
        $collection = $this->_reviewCollectionFactory->create()
            ->addStatusFilter(
                \Magento\Review\Model\Review::STATUS_APPROVED
            )->addEntityFilter(
                'product',
                $this->_product->getId()
            )->setDateOrder();

        return $collection;
    }

    public function getVoteCollection($reviewId)
    {
        $collection = $this->_ratingOptionVoteFactory->create()
            ->addFilter('review_id', $reviewId);

        return $collection;
    }

    public function getYotpoProductSnippet()
    {
        if ($this->_moduleManager->isOutputEnabled('Yotpo_Yotpo') &&
            $this->_moduleManager->isEnabled('Yotpo_Yotpo') &&
            $this->getConfig('yotpo/settings/active') == true
        ) {
            return ObjectManager::getInstance()->create('Yotpo\Yotpo\Model\Api\Products')->getRichSnippet();
        }

        return false;
    }

    protected function getChildren()
    {
        if (!$this->getConfig('structureddata/product/include_children')) {
            return [];
        }

        if ($this->_product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            $children = [];
            $productsIds = $this->_product->getTypeInstance()->getChildrenIds($this->_product->getId(), true);
            foreach ($productsIds as $product) {
                if ($child = $this->loadProduct(reset($product))) {
                    $children[] = $child;
                }
            }
            return $children;
        }

        if ($this->_product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            $children = [];
            $products = $this->_product->getTypeInstance()->getAssociatedProducts($this->_product);
            foreach ($products as $product) {
                $children[] = $product;
            }
            return $children;
        }

        if ($this->_product->getTypeId()
            != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return [];
        }

        return $this->_product->getTypeInstance()->getUsedProducts($this->_product);
    }

    public function getBrandFieldFromConfig()
    {
        if ($value = $this->getConfig('structureddata/product/product_brand_field')) {
            return $value;
        }
        return false;
    }

    public function getBundlePriceRange($productId)
    {
        $bundleObj = $this->loadProduct($productId)
            ->getPriceInfo()
            ->getPrice('final_price');

        return [
            'minPrice' => $bundleObj->getMinimalPrice(),
            'maxPrice' => $bundleObj->getMaximalPrice()
        ];
    }

    public function checkTaxIncluded()
    {
        $taxDisplayType = $this->getConfig('tax/display/type');
        if ($taxDisplayType == 2 || $taxDisplayType == 3) {
            return true;
        } else {
            return false;
        }
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    public function getConfig($config)
    {
        return $this->_scopeConfig->getValue($config, ScopeInterface::SCOPE_STORE);
    }

    public function loadProduct($id)
    {
        return $this->_productFactory->create()->load($id);
    }

    /**
     * Escape URL
     *
     * @param string $string
     * @return string
     */
    public function escapeUrl($string)
    {
        return $this->_escaper->escapeUrl((string)$string);
    }

    /**
     * Escape HTML entities
     *
     * @param string|array $data
     * @param array|null $allowedTags
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        return trim(strip_tags((string) $data));
    }

    public function escapeQuote($data)
    {
        return (string) $data;
    }
    
    /**
     * Returns the configured MerchantReturnPolicy, or an empty array when no
     * return window has been configured. The module must not invent a policy.
     *
     * @return array<string, mixed>
     */
    public function getMerchantReturnPolicy(): array
    {
        $days = (int) $this->getConfig('structureddata/shipping_return/merchant_return_days');
        if ($days <= 0) {
            return [];
        }

        $policy = [
            '@type' => 'MerchantReturnPolicy',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => $days,
        ];

        $method = trim((string) $this->getConfig('structureddata/shipping_return/return_method'));
        if (in_array($method, ['ReturnByMail', 'ReturnInStore', 'ReturnAtKiosk'], true)) {
            $policy['returnMethod'] = 'https://schema.org/' . $method;
        }

        return $policy;
    }

    /**
     * Returns the deepest user-facing category assigned to the product as
     * a schema.org Thing entity (with @id, @type, name, and url) so AI
     * search engines can both label and link to the category page.
     *
     * Uses the product's own category assignments and skips inactive
     * categories. The current category is deliberately not preferred because
     * it can represent a navigation/filter context rather than taxonomy.
     *
     * @return array<string, mixed>
     */
    public function getProductCategory(): string
    {
        $currentCategory = $this->registry->registry('current_category');
        $assignedIds = array_map('intval', (array) $this->_product->getCategoryIds());
        if ($currentCategory && in_array((int) $currentCategory->getId(), $assignedIds, true)) {
            return $this->escapeQuote((string) $currentCategory->getName());
        }

        $ids = array_values(array_filter($assignedIds, fn ($id) => $id > 2));
        if (empty($ids)) {
            return '';
        }

        try {
            $bestCategory = null;
            $bestDepth = -1;
            foreach ($ids as $id) {
                try {
                    $cat = $this->categoryRepository->get($id, $this->getStore()->getId());
                } catch (\Throwable $e) {
                    continue;
                }
                if (!$cat->getId()
                    || (method_exists($cat, 'getIsActive') && !$cat->getIsActive())
                ) {
                    continue;
                }
                $depth = substr_count((string) $cat->getPath(), '/');
                if ($depth > $bestDepth) {
                    $bestDepth = $depth;
                    $bestCategory = $cat;
                }
            }
            return $bestCategory ? $this->escapeQuote((string) $bestCategory->getName()) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function getCanonicalProductUrl(ProductModel $product): string
    {
        $url = $this->resolveCategoryIndependentProductUrl($product);
        $url = preg_replace('/[?#].*$/', '', $url) ?: $url;
        return $this->escapeUrl(strip_tags($url));
    }

    private function resolveCategoryIndependentProductUrl(ProductModel $product): string
    {
        try {
            $urlModel = $product->getUrlModel();
            if ($urlModel && method_exists($urlModel, 'getUrl')) {
                $url = (string) $urlModel->getUrl($product, [
                    '_ignore_category' => true,
                    '_scope_to_url' => true,
                ]);
                if ($url !== '') {
                    return $url;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to the product URL API below.
        }
        return (string) $product->getProductUrl(false);
    }

    private function resetProductState(): void
    {
        $this->brand = null;
        $this->weight = null;
        $this->minWeight = null;
        $this->maxWeight = null;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->reviewsCount = null;
        $this->_reviewData = null;
    }

}
