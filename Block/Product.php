<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Catalog\Block\Product\View;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Url\EncoderInterface as UrlEncoderInterface;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Customer\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Module\Manager as ModuleManager;

class Product extends View
{
    /**
     * @var Product Loader
     */
    protected $_productFactory;

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
     * @var string
     */
    protected $_brand = null;

    /**
     * @var Summary
     */
    protected $_reviewData = null;

    /**
     * @var int
     */
    protected $_reviewsCount = null;

    /**
     * @param Context $context
     * @param UrlEncoderInterface $urlEncoder
     * @param JsonEncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param ProductHelper $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param SummaryFactory $reviewSummaryFactory
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param ModuleManager $moduleManager
     * @param array $data
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        UrlEncoderInterface $urlEncoder,
        JsonEncoderInterface $jsonEncoder,
        StringUtils $string,
        ProductHelper $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        SummaryFactory $reviewSummaryFactory,
        ReviewCollectionFactory $reviewCollectionFactory,
        ProductFactory $productFactory,
        ModuleManager $moduleManager,
        array $data = []
    ) {
        $this->_reviewSummaryFactory = $reviewSummaryFactory;
        $this->_reviewCollectionFactory = $reviewCollectionFactory;
        $this->_productFactory = $productFactory;
        $this->_moduleManager = $moduleManager;
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
    }

    public function loadProduct($id)
    {
        return $this->_productFactory->create()->load($id);
    }

    public function getConfig($config)
    {
        return $this->_scopeConfig->getValue($config, ScopeInterface::SCOPE_STORE);
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
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

        if ($this->getProduct()->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            $children = [];
            $productsIds = $this->getProduct()->getTypeInstance()->getChildrenIds($this->getProduct()->getId(), true);
            foreach ($productsIds as $product) {
                if ($child = $this->loadProduct(reset($product))) {
                    $children[] = $child;
                }
            }
            return $children;
        }

        if ($this->getProduct()->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            $children = [];
            $products = $this->getProduct()->getTypeInstance()->getAssociatedProducts($this->getProduct());
            foreach ($products as $product) {
                $children[] = $product;
            }
            return $children;
        }

        if ($this->getProduct()->getTypeId()
            != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return [];
        }

        return $this->getProduct()->getTypeInstance()->getUsedProducts($this->getProduct());
    }

    public function getDescription()
    {
        if ($this->getConfig('structureddata/product/use_short_description')
            && $this->getProduct()->getShortDescription()) {
            $description = nl2br($this->getProduct()->getShortDescription());
        } else {
            $description = nl2br($this->getProduct()->getDescription());
        }

        if ($description) {
            $description = preg_replace('/([\r\n\t])/', ' ', $description);
        }

        return $description;
    }

    public function getBrandFieldFromConfig()
    {
        if ($value = $this->getConfig('structureddata/product/product_brand_field')) {
            return $value;
        }
        return false;
    }

    public function getBrand()
    {
        if ($this->_brand === null) {

            if ($value = $this->getBrandFieldFromConfig()) {
                if ($this->getProduct()->getData($value)) {
                    $this->_brand = $this->getProduct()->getAttributeText($value);
                }
            }

            if ($this->_brand === null) {
                if ($this->getProduct()->getBrand()) {
                    $this->_brand = $this->getProduct()->getAttributeText('brand');
                } elseif ($this->getProduct()->getManufacturer()) {
                    $this->_brand = $this->getProduct()->getAttributeText('manufacturer');
                } else {
                    $this->_brand = false;
                }
            }
        }

        return $this->_brand;
    }

    public function getAttributeText($attribute)
    {
        $attributeText = $this->getProduct()->getAttributeText($attribute);
        if (is_array($attributeText)) {
            $attributeText = implode(', ', $attributeText);
        }
        return $attributeText;
    }

    public function getReviewData()
    {
        if ($this->_reviewData === null) {
            $this->_reviewData = $this->_reviewSummaryFactory->create()->load($this->getProduct()->getId());
        }
        return $this->_reviewData;
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
        if ($this->_reviewsCount === null) {

            if ($data = $this->getYotpoProductSnippet()) {
                $reviewCount = isset($data['reviews_count']) ? $data['reviews_count'] : null;
            } else {
                $reviewCount = !empty($this->getReviewData()) ? $this->getReviewData()->getReviewsCount() : null;
            }

            $this->_reviewsCount = $reviewCount;

        }

        return $this->_reviewsCount;
    }

    public function getBundlePriceRange($productId)
    {
        $bundleObj = $this->loadProduct($productId)
            ->getPriceInfo()
            ->getPrice('final_price');
        $minPrice = $bundleObj->getMinimalPrice();
        $maxPrice = $bundleObj->getMaximalPrice();

        return compact("minPrice", "maxPrice");
    }

    public function checkTaxIncluded()
    {
        $taxDisplayType = $this->_scopeConfig->getValue('tax/display/type', ScopeInterface::SCOPE_STORE);
        if ($taxDisplayType == 2) {
            return 'true';
        } else {
            return 'false';
        }
    }
}
