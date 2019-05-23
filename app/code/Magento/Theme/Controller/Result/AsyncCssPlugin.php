<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Controller\Result;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Response\Http;

/**
 * Plugin for asynchronous CSS loading.
 */
class AsyncCssPlugin
{
    private const XML_PATH_USE_CSS_CRITICAL_PATH = 'dev/css/use_css_critical_path';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Load CSS asynchronously if it is enabled in configuration.
     *
     * @param Http $subject
     * @return void
     */
    public function beforeSendResponse(Http $subject): void
    {
        $content = $subject->getContent();

        if (strpos($content, '</body') !== false && $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_CSS_CRITICAL_PATH,
            ScopeInterface::SCOPE_STORE
            )) {
            // add link rel preload to style sheets
            $content = preg_replace_callback(
                '@<link\b.*?rel=("|\')stylesheet\1.*?/>@',
                function ($matches) {
                    preg_match('@href=("|\')(.*?)\1@', $matches[0], $hrefAttribute);
                    $href = $hrefAttribute[2];
                    if (preg_match('@media=("|\')(.*?)\1@', $matches[0], $mediaAttribute)) {
                        $media = $mediaAttribute[2];
                    }
                    $media = $media ?? 'all';
                    $loadCssAsync = sprintf(
                        '<link rel="preload" as="style" media="%s" onload="this.onload=null;this.rel=\'stylesheet\'"' .
                        'href="%s"><noscript><link rel="stylesheet" href="%s"></noscript>',
                        $media,
                        $href,
                        $href
                    );

                    return $loadCssAsync;
                },
                $content
            );

            $subject->setContent($content);
        }
    }
}
