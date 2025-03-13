<?php

namespace OuterEdge\StructuredData\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OuterEdge\StructuredData\Model\Cache\Type\StructuredDataCache;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\CacheInterface;
use Magento\Store\Model\StoreManagerInterface;

class FlushStructuredDataCache implements ObserverInterface
{
    public function __construct(
        protected CacheInterface $cache,
        protected StoreManagerInterface $storeManager
    ) {
    }

    public function execute(Observer $observer)
    {
        $object = $observer->getEvent()->getObject();

        if ($object instanceof Product && $object->hasDataChanges()) {
            $cacheId = StructuredDataCache::TYPE_IDENTIFIER . '_' . $this->storeManager->getStore()->getId() . '_' . $object->getEntityId();
            $this->cache->remove($cacheId);
        }
    }
}
