<?php

namespace OuterEdge\StructuredData\Plugin;

use Magento\Review\Block\Product\ReviewRenderer;

class StripReviewSummary
{
    public function afterGetReviewsSummaryHtml(ReviewRenderer $subject, $result)
    {
        if (empty(trim($result))) {
            return $result;
        }
        
        $useInternalErrors = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument;
            $dom->loadHTML('<?xml encoding="UTF-8">' . $result);
            $xpath = new \DOMXPath($dom);
            $nodes = $xpath->query("//@itemprop|//@itemscope|//@itemtype");
            foreach ($nodes as $node) {
                $node->parentNode->removeAttribute($node->nodeName);
            }
    
            $body = $dom->getElementsByTagName('body')->item(0);
            if (!$body) {
                return $result;
            }

            $html = '';
            foreach ($body->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }

            return $html;
        } catch(\Exception $e) {
            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }
    }
}
