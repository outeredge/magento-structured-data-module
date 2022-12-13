<?php

namespace OuterEdge\StructuredData\Plugin;

use Magento\Review\Block\Product\ReviewRenderer;

class StripReviewSummary
{
    public function afterGetReviewsSummaryHtml(ReviewRenderer $subject, $result)
    {
        if (empty($result)) {
            return '';
        }
        
        $dom = new \DOMDocument;
        $dom->loadHTML($result);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//@itemprop|//@itemscope|//@itemtype");
        foreach ($nodes as $node) {
            $node->parentNode->removeAttribute($node->nodeName);
        }

        return $dom->saveHTML();
    }
}
