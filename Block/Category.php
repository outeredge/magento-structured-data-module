<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class Category extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ListProduct
     */
    protected $listProductBlock;

    /**
     * @var AbstractCollection
     */
    protected $_productCollection = null;

    public function __construct(
        ListProduct $listProductBlock,
        Context $context,
        array $data = [])
    {
        $this->listProductBlock = $listProductBlock;

        parent::__construct($context, $data);
    }

    public function getSchemaJson()
    {
        $collection = $this->listProductBlock->getLoadedProductCollection();
        return json_encode($this->getSchemaData($collection), JSON_UNESCAPED_SLASHES);
    }

    public function getSchemaData(AbstractCollection $productCollection)
    {
        if (!$this->_productCollection) {
            $this->_productCollection = $productCollection;
        }

        $listData = [];

        $i = 1;
        foreach ($this->_productCollection as $product) {
            $listData[] = [
                "@context" => "https://schema.org/",
                "@type" => "ListItem",
                "position" => $i++,
                "url" => $product->getProductUrl()
            ];
        }

        $data = [
            "@type" => "ItemList",
            "itemListElement" => $listData
        ];

        return $data;
    }
}
