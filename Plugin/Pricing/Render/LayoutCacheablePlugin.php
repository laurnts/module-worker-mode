<?php
declare(strict_types=1);

namespace MageOS\WorkerMode\Plugin\Pricing\Render;

use Magento\Framework\Pricing\Render\Layout as PriceLayout;
use Magento\Framework\View\LayoutInterface;
use MageOS\WorkerMode\Model\View\Layout as WorkerLayout;

/**
 * Keeps the pricing render's nested layout in step with the current page's cacheability.
 *
 * Pricing\Render\Layout creates its nested layout once, in its constructor, with
 * ['cacheable' => $generalLayout->isCacheable()]. In a worker the object lives across requests, and
 * Layout::_resetState() restores $cacheable = true on every layout, so the nested layout always reports
 * cacheable. When it generates elements, PageCache's LayoutPlugin then calls setPublicHeaders() on the
 * shared response, and non-cacheable pages (cart, login, customer account) are sent as
 * "Cache-Control: public, max-age=86400" and cached by Varnish.
 *
 * Before the nested layout is generated, copy the page layout's current isCacheable() onto it.
 * Relies on isIsolated=false (etc/di.xml), so the shared LayoutInterface is the page's layout.
 */
class LayoutCacheablePlugin
{
    public function __construct(
        private readonly LayoutInterface $generalLayout
    ) {}

    /**
     * Sync the nested layout's cacheable flag before loadLayout() generates its elements.
     *
     * @param PriceLayout $subject
     * @return void
     */
    public function beforeLoadLayout(PriceLayout $subject): void
    {
        $layout = (new \ReflectionProperty(PriceLayout::class, 'layout'))->getValue($subject);
        if ($layout instanceof WorkerLayout) {
            $layout->setCacheable((bool)$this->generalLayout->isCacheable());
        }
    }
}
