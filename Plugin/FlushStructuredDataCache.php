<?php

namespace OuterEdge\StructuredData\Plugin;

use OuterEdge\StructuredData\Model\Cache\Type\StructuredDataCache;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Cache;

class FlushStructuredDataCache
{
    private $tagsList = ['cat_p_', 'catalog_product_'];

    public function __construct(
        protected CacheInterface $cache
    ) {
    }

    public function afterClean(Cache $subject, $result, $tags)
    {
        foreach($tags as $tag) {
            $tagName = preg_replace('/\d/', '', $tag);
            if (in_array($tagName, $this->tagsList)) {
                $prodId = str_replace($tagName, '', $tag);
                $cacheId = StructuredDataCache::TYPE_IDENTIFIER .'_'. $prodId;
                $this->cache->remove($cacheId);
            }
        }
        return $result;
    }
}
