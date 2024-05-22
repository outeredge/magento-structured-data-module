<?php

namespace OuterEdge\StructuredData\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OuterEdge\StructuredData\Model\Cache\Type\StructuredDataCache;
use Magento\Catalog\Model\Product;

class FlushStructuredDataCache implements ObserverInterface
{
    public function __construct(
        protected StructuredDataCache $cache
    ) {
    }

    public function execute(Observer $observer)
    {
        $object = $observer->getEvent()->getObject();

        if ($object instanceof Product && $object->hasDataChanges()) {
            $cacheId = StructuredDataCache::TYPE_IDENTIFIER .'_'. $object->getEntity();
            $this->cache->remove($cacheId);
        }
    }
}
