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
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use OuterEdge\StructuredData\Model\Cache\Type\StructuredDataCache;
use Magento\Framework\View\Element\Template;

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
        protected CacheInterface $cache,
        protected SerializerInterface $serializer,
        protected Template $template
	) {
	}

    public function getSchemaData(ProductModel $product)
    {
        if (!$this->_product) {
            $this->_product = $product;
        }

        $data = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "@id" => $this->escapeUrl(strtok($this->_product->getUrlInStore(), '?'))."#Product",
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

        if ($this->getGtin()) {
            $data['gtin'] = $this->escapeQuote((string)strip_tags($this->getGtin()));
        }

        if ($this->getMpn()) {
            $data['mpn'] = $this->escapeQuote((string)strip_tags($this->getMpn()));
        }

        if ($this->getIsbn()) {
            $data['isbn'] = $this->escapeQuote((string)strip_tags($this->getIsbn()));
        }

        if ($this->getSize()) {
            $data['size'] = $this->escapeQuote((string)strip_tags($this->getSize()));
        }

        if ($this->getMaterial()) {
            $data['material'] = $this->escapeQuote((string)strip_tags($this->getMaterial()));
        }

        if ($this->getColor()) {
            $data['color'] = $this->escapeQuote((string)strip_tags($this->getColor()));
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
                    $offers[$key]['url'] = $this->_product->getProductUrl();
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
        if ($result = $this->getCache($product->getId())) {
            return $result;
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

        $pricewithTax = $this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), $this->checkTaxIncluded());

        $data = [
            "@type" => "Offer",
            "url" => $this->escapeUrl(strtok($product->getUrlInStore(), '?')),
            "price" => $this->escapeQuote((string)$this->pricingHelper->currency($pricewithTax, false, false)),
            "priceCurrency" => $this->escapeQuote($this->getStore()->getCurrentCurrency()->getCode()),
            "availability" => "http://schema.org/$availability",
            "itemCondition" => "http://schema.org/NewCondition",
            "priceSpecification" => [
                "@type" => "UnitPriceSpecification",
                "price" => $this->escapeQuote((string)$this->pricingHelper->currency($pricewithTax, false, false)),
                "priceCurrency" => $this->escapeQuote($this->getStore()->getCurrentCurrency()->getCode()),
                "valueAddedTaxIncluded" => $this->escapeQuote($this->checkTaxIncluded() ? 'true' : 'false')
            ]
        ];

        if ($product->getFinalPrice() < $product->getPrice() && $product->getSpecialToDate()) {
            $priceToDate = date_create($product->getSpecialToDate());
            $data['priceValidUntil'] = $this->escapeQuote($priceToDate->format('Y-m-d'));
        }

        $this->saveCache($product->getId(), $data);
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
            $data['color'] = $this->escapeQuote((string)$this->getAttributeText('color'));
       	} elseif ($this->_product->getColour()) {
            $data['color'] = $this->escapeQuote((string)$this->getAttributeText('colour'));
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
        return $this->_escaper->escapeHtml($data, $allowedTags);
    }

    public function escapeQuote($data)
    {
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, null, false);
    }

    protected function saveCache($productId, $data)
    {
        $cacheId  = StructuredDataCache::TYPE_IDENTIFIER . '_' . $productId;
        $this->cache->save(
            $this->serializer->serialize($data),
            $cacheId,
            [StructuredDataCache::CACHE_TAG]
        );
    }

    protected function getCache($productId)
    {
        $cacheId  = StructuredDataCache::TYPE_IDENTIFIER . '_' . $productId;

        if ($result = $this->cache->load($cacheId)) {
            return $this->serializer->unserialize($result);
        }
        return false;
    }
}
