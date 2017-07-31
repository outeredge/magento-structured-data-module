<?php

namespace OuterEdge\StructuredData\Block\Review\Product;

use Magento\Review\Block\Product\ReviewRenderer;
use DOMDocument;
use DOMXPath;

class ReviewRendererPlugin
{
    public function afterGetReviewsSummaryHtml(ReviewRenderer $subject, $html)
    {
        if (!$html) {
            return $html;
        }
        
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        foreach (['itemprop', 'itemtype', 'itemscope'] as $schemaAttribute) {
            $nodes = $xpath->query('//*[@' . $schemaAttribute . ']');
            foreach ($nodes as $node) {
                $node->removeAttribute($schemaAttribute);
            }
        }
        return $dom->saveHTML();
    }
}
