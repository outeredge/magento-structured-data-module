<?php

class Edge_StructuredData_Block_Product extends Mage_Core_Block_Template
{
    protected $_product = null;

    protected function _toHtml()
    {
        if (!$this->getProduct()
            || !Mage::getStoreConfigFlag('structureddata/general/enabled')) {

            return '';
        }
        return parent::_toHtml();
    }

    public function getProduct()
    {
        if (null === $this->_product) {
            $this->_product = Mage::registry('product');
        }
        return $this->_product;
    }

    public function getReviewCount()
    {
        return $this->getProduct()->getRatingSummary()->getReviewsCount();
    }

    public function getStockStatusUrl()
    {
        if ($this->getProduct()->isSaleable()){
            $availability = 'InStock';
        } else {
            $availability = 'OutOfStock';
        }
        return $availability;
    }

    /**
     * @return mixed Array with min and max values or float
     */
    public function getPriceValues()
    {
        $product     = $this->getProduct();
        $priceModel  = $product->getPriceModel();
        $productType = $product->getTypeInstance();

        if ($productType instanceof Mage_Bundle_Model_Product_Type) {
            if (method_exists($priceModel, 'getTotalPrices')) {
                return $priceModel->getTotalPrices($product);
            }
        }

        if ($productType instanceof Mage_Catalog_Model_Product_Type_Grouped) {
            $assocProducts = $productType->getAssociatedProductCollection($product)
                ->addMinimalPrice()
                ->setOrder('minimal_price', 'ASC');

            $product = $assocProducts->getFirstItem();
            if ($product) {
                return array($product->getFinalPrice());
            }
        }

        $minPrice   = $product->getMinimalPrice();
        $finalPrice = $product->getFinalPrice();
        if ($minPrice && $minPrice < $finalPrice) {
            return array($minPrice, $finalPrice);
        }

        return $finalPrice;
    }

    public function getFormattedPrice($price)
    {
        return $this->helper('core')->currency(
            $this->helper('tax')->getPrice(
                $this->getProduct(),
                $price
            ),
            true,
            false
        );
    }

    public function getShortDescription()
    {
        $description = strip_tags($this->getProduct()->getShortDescription());
        $description = str_replace("\"", "'", $description);
        return $description;
    }

    public function getAttributeText($attributeCode)
    {
        $product   = $this->getProduct();
        $attribute = $product->getResource()
            ->getAttribute($attributeCode);

        if (!$attribute) {
            return false;
        }
        return str_replace("\"", "'", $attribute->getFrontend()->getValue($product));
    }
    
}