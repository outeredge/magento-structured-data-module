<?php

namespace OuterEdge\StructuredData\Plugin\Theme\Block\Html;

use Magento\Theme\Block\Html\Breadcrumbs as Subject;

/**
 * Captures crumbs whenever the standard Magento breadcrumbs block is
 * populated, regardless of the template (Luma, Hyvä, or any theme that
 * uses the public addCrumb() API). The captured array is exposed on the
 * structured data block so the unified @graph BreadcrumbList mirrors the
 * visible navigation trail.
 */
class BreadcrumbsPlugin
{
    public function afterAddCrumb(
        Subject $subject,
        $result,
        $crumbName,
        $crumbInfo
    ) {
        $crumbs = $subject->getData('structured_data_crumbs');
        if (!is_array($crumbs)) {
            $crumbs = [];
        }
        $crumbs[$crumbName] = is_array($crumbInfo) ? $crumbInfo : [];
        $subject->setData('structured_data_crumbs', $crumbs);
        return $result;
    }

    public function afterToHtml(Subject $subject, string $result): string
    {
        $this->captureCrumbs($subject);
        return $result;
    }

    private function captureCrumbs(Subject $subject): void
    {
        try {
            $crumbs = $subject->getData('structured_data_crumbs');
            if (!is_array($crumbs) || !$crumbs) {
                return;
            }

            $layout = $subject->getLayout();
            if (!$layout) {
                return;
            }

            $jsonld = $layout->getBlock('structured.data.jsonld');
            if ($jsonld === null) {
                return;
            }

            $existing = $jsonld->getData('structured_data_crumbs');
            if (is_array($existing) && $existing) {
                return;
            }

            $jsonld->setData('structured_data_crumbs', $crumbs);
        } catch (\Throwable $e) {
            // never break rendering for telemetry
        }
    }
}
