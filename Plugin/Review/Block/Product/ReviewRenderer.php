<?php

namespace OuterEdge\StructuredData\Plugin\Review\Block\Product;

use Magento\Review\Block\Product\ReviewRenderer as ProductReviewRenderer;
use DOMDocument;
use DOMXPath;

class ReviewRenderer
{
    public function afterGetReviewsSummaryHtml(ProductReviewRenderer $subject, $html)
    {
        if (!$html) {
            return $html;
        }

        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
