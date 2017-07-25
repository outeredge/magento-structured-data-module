<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\View;

class Product extends View
{
    /**
     * @var \Magento\Review\Model\Review\SummaryFactory
     */
    protected $_reviewSummaryFactory;

    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_reviewCollectionFactory;
    
    /**
     * @var \Magento\Theme\Block\Html\Header\Logo
     */
    protected $_logoBlock;
    
    protected $_brand = null;
    
    protected $_reviewData = null;
    
    protected $_reviewsCount = null;
    
    /**
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface|\Magento\Framework\Pricing\PriceCurrencyInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Review\Model\Review\SummaryFactory $reviewSummaryFactory
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory
     * @param \Magento\Theme\Block\Html\Header\Logo $logoBlock
     * @param array $data
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Review\Model\Review\SummaryFactory $reviewSummaryFactory,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory,
        \Magento\Theme\Block\Html\Header\Logo $logoBlock,
        array $data = []
    ) {
        $this->_reviewSummaryFactory = $reviewSummaryFactory;
        $this->_reviewCollectionFactory = $reviewCollectionFactory;
        $this->_logoBlock = $logoBlock;
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
    
    public function getConfig($config)
    {
        return $this->_scopeConfig->getValue($config, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getStoreLogoUrl()
    {
        return $this->_logoBlock->getLogoSrc();
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
