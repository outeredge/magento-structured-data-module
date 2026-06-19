<?php

declare(strict_types=1);

namespace OuterEdge\StructuredData\Plugin\Block;

use MediaLounge\Storyblok\Block\Container;
use OuterEdge\StructuredData\Block\Jsonld\FaqCollector;

/**
 * Walks the Storyblok content tree of every rendered Container block,
 * finds any `category-faq` or `product-faq` components, and pushes their
 * Q&A pairs into the request-scoped FaqCollector registry so the Jsonld
 * block can emit a single FAQPage entity inside the @graph.
 */
class ContainerFaqCollector
{
    private const FAQ_COMPONENTS = ['category-faq', 'product-faq'];

    public function __construct(private readonly FaqCollector $faqCollector)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetStory(Container $subject, $result)
    {
        if (!is_array($result)) {
            return $result;
        }

        $this->scanForFaqs($result['content'] ?? []);

        return $result;
    }

    private function scanForFaqs(array $node): void
    {
        if (isset($node['component']) && in_array($node['component'], self::FAQ_COMPONENTS, true)) {
            $this->collectFromFaqComponent($node);
        }

        foreach ($node as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if ($key === 'content' || $key === 'body' || $key === 'blocks' || $key === 'items') {
                if ($this->isListOfBlocks($value)) {
                    foreach ($value as $child) {
                        if (is_array($child)) {
                            $this->scanForFaqs($child);
                        }
                    }
                } else {
                    $this->scanForFaqs($value);
                }
                continue;
            }

            if (array_is_list($value) || array_key_exists('_uid', $value)) {
                $this->scanForFaqs($value);
            }
        }
    }

    private function collectFromFaqComponent(array $node): void
    {
        $items = $node['items'] ?? [];
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = $this->extractText($item['question'] ?? '');
            $answer = $this->extractText($item['answer'] ?? '');

            $this->faqCollector->addItem($question, $answer);
        }
    }

    /**
     * Storyblok rich-text answers are arrays like ['content' => [...]].
     * Plain strings are returned as-is so we can handle either form.
     */
    private function extractText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return '';
        }

        if (isset($value['text']) && is_string($value['text'])) {
            return $value['text'];
        }

        $buffer = '';
        foreach ($value as $part) {
            if (is_string($part)) {
                $buffer .= $part . ' ';
                continue;
            }
            if (is_array($part)) {
                $buffer .= $this->extractText($part) . ' ';
            }
        }

        return trim(preg_replace('/\s+/', ' ', $buffer));
    }

    private function isListOfBlocks(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        foreach ($value as $entry) {
            if (!is_array($entry)) {
                return false;
            }
        }

        return true;
    }
}