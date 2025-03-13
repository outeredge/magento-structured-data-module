<?php

namespace OuterEdge\StructuredData\Plugin\Cache;

use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save;
use OuterEdge\StructuredData\Model\Cache\Type\StructuredDataCache;
use Magento\Framework\App\CacheInterface;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use Magento\Store\Model\StoreManagerInterface;

class BulkUpFlushStructuredCache
{
    public function __construct(
        protected Attribute $attributeHelper,
        protected CacheInterface $cache,
        protected StoreManagerInterface $storeManager
    ) {
    }

    public function aroundExecute(Save $subject, callable $proceed)
    {
        $originalReturn = $proceed();
        $selectedProductIds = $this->attributeHelper->getProductIds();

        foreach ($selectedProductIds as $productId) {
            $cacheId = StructuredDataCache::TYPE_IDENTIFIER . '_' . $this->storeManager->getStore()->getId() . '_' . $productId;
            $this->cache->remove($cacheId);
        }

        return $originalReturn;

    }
}
