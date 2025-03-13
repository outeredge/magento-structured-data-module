<?php

namespace OuterEdge\StructuredData\Plugin\Cache;

use OuterEdge\StructuredData\Model\Cache\Type\StructuredDataCache;
use Magento\Framework\App\Cache;
use Magento\Store\Model\StoreManagerInterface;

class FlushStructuredDataCache
{
    private $tagsList = ['cat_p_', 'catalog_product_'];

    public function __construct(
        protected StoreManagerInterface $storeManager
    ) {
    }

    public function beforeClean(Cache $subject, $tags = [])
    {
        if ($tags) {
            foreach((array)$tags as $tag) {
                $tagName = preg_replace('/\d/', '', $tag);
                if (in_array($tagName, $this->tagsList)) {
                    $prodId = str_replace($tagName, '', $tag);
                    $cacheId = StructuredDataCache::TYPE_IDENTIFIER . '_' . $this->storeManager->getStore()->getId() . '_' . $prodId;
                    $subject->remove($cacheId);
                }
            }
        }
        return null;
    }
}
