<?php

namespace OuterEdge\StructuredData\Model\Type;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Escaper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;

class Product
{
    /**
     * @var Escaper
     */
    protected $_escaper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var SummaryFactory
     */
    protected $_reviewSummaryFactory;

    /**
     * @var ReviewCollectionFactory
     */
    protected $_reviewCollectionFactory;

    /**
     * @var ModuleManager
     */
    protected $_moduleManager;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var PricingHelper
     */
    protected $pricingHelper;

    /**
     * @var ProductModel
     */
    protected $_product = null;

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
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param ModuleManager $moduleManager
     * @param ImageHelper $imageHelper
     * @param PricingHelper $pricingHelper
     */
	public function __construct(
        Escaper $escaper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        SummaryFactory $reviewSummaryFactory,
        ReviewCollectionFactory $reviewCollectionFactory,
        ModuleManager $moduleManager,
        ImageHelper $imageHelper,
        PricingHelper $pricingHelper
	)
	{
        $this->_escaper = $escaper;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_productFactory = $productFactory;
        $this->_reviewSummaryFactory = $reviewSummaryFactory;
        $this->_reviewCollectionFactory = $reviewCollectionFactory;
        $this->_moduleManager = $moduleManager;
        $this->imageHelper = $imageHelper;
        $this->pricingHelper = $pricingHelper;
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
            "description" => $this->escapeHtml((string)strip_tags($this->getDescription())),
            "image" => $this->escapeUrl(strip_tags($this->getImageUrl($this->_product, 'product_base_image')))
        ];

        if ($this->getBrand()) {
            $data['brand'] = [
                "@type" => "Brand",
                "name" => $this->escapeQuote((string)strip_tags($this->getBrand()))
            ];
        }

        if ($this->getReviewsCount()) {
            $data['aggregateRating'] = [
                "@type" => "AggregateRating",
                "bestRating" => "100",
                "worstRating" => "1",
                "ratingValue" => $this->escapeQuote((string)$this->getReviewsRating()),
                "reviewCount" => $this->escapeQuote((string)$this->getReviewsCount())
            ];
        }

        if ($this->_product->getMpn()) {
            $data['mpn'] = $this->escapeQuote((string)strip_tags($this->_product->getMpn()));
        }

        if ($this->_product->getMaterial()) {
            $data['material'] = $this->escapeQuote((string)$this->getAttributeText('material'));
        }

        if ($this->_product->getMpn()) {
            $data['color'] = $this->escapeQuote((string)$this->getAttributeText('color'));
        }

        if ($this->getGtin()) {
            $data['gtin'] = $this->escapeQuote((string)strip_tags($this->getGtin()));
        }

        $children = $this->getChildren();
        if (empty($children)) {
            $this->weight = $this->_product->getWeight();
            $data['offers'] = $this->getOffer($this->_product);
        } else {
            $offers  = [];
            $lastKey = key(array_slice($children, -1, 1, true));

            foreach ($children as $key => $_product) {
                $productFinalPrice = $_product->getFinalPrice();
                $productWeight     = $_product->getWeight();

                $this->minPrice  = $productFinalPrice < $this->minPrice || $this->minPrice === null ? $productFinalPrice : $this->minPrice;
                $this->maxPrice  = $productFinalPrice > $this->maxPrice || $this->maxPrice === null ? $productFinalPrice : $this->maxPrice;

                $this->minWeight = $productWeight < $this->minWeight || $this->minWeight === null ? $productWeight : $this->minWeight;
                $this->maxWeight = $productWeight > $this->maxWeight || $this->maxWeight === null ? $productWeight : $this->maxWeight;

                $offers[] = $this->getOffer($_product);
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
                $rangeBundle = $this->getBundlePriceRange($product->getId());
                $this->minPrice = $rangeBundle['minPrice'];
                $this->maxPrice = $rangeBundle['maxPrice'];
            } else {
                $this->minPrice = $this->pricingHelper->currency($this->minPrice, false, false);
                $this->maxPrice = $this->pricingHelper->currency($this->maxPrice, false, false);
            }

            $data['offers']['lowPrice'] = $this->escapeQuote((string)$this->minPrice);
            $data['offers']['highPrice'] = $this->escapeQuote((string)$this->maxPrice);
        }

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

    protected function getOffer(ProductModel $product) {
        $availability = $product->isAvailable() ? 'InStock' : 'OutOfStock';

        $data = [
            "@type" => "Offer",
            "url" => $this->escapeUrl(strtok($product->getUrlInStore(), '?')),
            "price" => $this->escapeQuote((string)$this->pricingHelper->currency($product->getFinalPrice(), false, false)),
            "priceCurrency" => $this->escapeQuote($this->getStore()->getCurrentCurrency()->getCode()),
            "availability" => "http://schema.org/$availability",
            "itemCondition" => "http://schema.org/NewCondition",
            "priceSpecification" => [
                "@type" => "UnitPriceSpecification",
                "price" => $this->escapeQuote((string)$this->pricingHelper->currency($product->getFinalPrice(), false, false)),
                "priceCurrency" => $this->escapeQuote($this->getStore()->getCurrentCurrency()->getCode()),
                "valueAddedTaxIncluded" => $this->escapeQuote($this->checkTaxIncluded())
            ]
        ];

        if ($product->getFinalPrice() < $product->getPrice() && $product->getSpecialToDate()) {
            $priceToDate = date_create($product->getSpecialToDate());
            $data['priceValidUntil'] = $this->escapeQuote($priceToDate->format('Y-m-d'));
        }

        return $data;
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

    public function getGtin()
    {
        if ($field = $this->getConfig('structureddata/product/product_gtin_field')) {
            if ($value = $this->_product->getData($field)) {
                return $value;
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

    public function getChildren()
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
        if ($taxDisplayType == 2) {
            return 'true';
        } else {
            return 'false';
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
}
