<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Registry;

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
        protected Registry $registry,
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
                "@type" => "ListItem",
                "position" => $i++,
                "url" => $product->getProductUrl(),
                "name" => (string) $product->getName()
            ];
        }

        $itemList = [
            "@type" => "ItemList",
            "itemListElement" => $listData
        ];

        // Wrap the ItemList in a richer CollectionPage that GEO can cite.
        $category = $this->registry->registry('current_category');
        $description = ($category && is_object($category))
            ? trim((string) $category->getDescription())
            : '';

        $collectionPage = [
            "@type" => "CollectionPage",
            "mainEntity" => $itemList
        ];

        if ($description !== '') {
            $collectionPage["about"] = $description;
        }

        $collectionPage["isPartOf"] = [
            "@id" => rtrim($this->_storeManager->getStore()->getBaseUrl(), '/') . '/#website'
        ];
        $collectionPage["publisher"] = [
            "@id" => rtrim($this->_storeManager->getStore()->getBaseUrl(), '/') . '/#org'
        ];

        return $collectionPage;
    }
}
