<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Catalog\Block\Product\View;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Catalog\Block\Product\Context;
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
     * @var Logo
     */
    protected $_logo;

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
     * @param Logo $logo
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
        Logo $logo,
        array $data = []
    ) {
        $this->_reviewSummaryFactory = $reviewSummaryFactory;
        $this->_reviewCollectionFactory = $reviewCollectionFactory;
        $this->_productFactory = $productFactory;
        $this->_logo = $logo;
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

    public function getChildren()
    {
        $configurableCode = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
        if(!$this->getConfig('structureddata/product/include_children') || $this->getProduct()->getTypeId() !== $configurableCode) {
            return array();
        }
        
        return $this->getProduct()->getTypeInstance()->getChildrenIds($this->getProduct()->getId(), true);
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

    public function getStoreLogoUrl()
    {
        return $this->_logo->getLogoSrc();
    }

    public function getDescription()
    {
        if ($this->getConfig('structureddata/product/use_short_description')) {
            $description = nl2br($this->getProduct()->getShortDescription());
        } else {
            $description = nl2br($this->getProduct()->getDescription());
        }
        return preg_replace('/([\r\n\t])/',' ', $description);
    }

    public function getBrand()
    {
        if ($this->_brand === null) {
            if ($this->getProduct()->getBrand()) {
                $this->_brand = $this->getProduct()->getAttributeText('brand');
            } elseif ($this->getProduct()->getManufacturer()) {
                $this->_brand = $this->getProduct()->getAttributeText('manufacturer');
            } else {
                $this->_brand = false;
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
        $ratingSummary = !empty($this->getReviewData()) ? $this->getReviewData()->getRatingSummary() : 20;
        return $ratingSummary / 20;
    }

    public function getReviewsCount()
    {
        if ($this->_reviewsCount === null) {
            $this->_reviewsCount = !empty($this->getReviewData()) ? $this->getReviewData()->getReviewsCount() : 0;
        }
        return $this->_reviewsCount;
    }

    public function getReviews()
    {
        $collection = $this->_reviewCollectionFactory->create()
            ->addEntityFilter('product', $this->getProduct()->getId())
            ->addRateVotes();

        foreach ($collection as $item) {
            $ratingValue = 0;
            $ratingCount = count($item->getRatingVotes());
            foreach ($item->getRatingVotes() as $rating) {
                $ratingValue += $rating->getPercent();
            }
            $item->setRatingValue($ratingValue / (20 * $ratingCount));
        }

        return $collection;
    }
}