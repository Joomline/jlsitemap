<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Component\JLSitemap\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;

class SitemapGenerationService
{
    public function __construct(
        protected SitemapRuntimeContextFactory $runtimeContextFactory
    ) {
    }

    public function generate(SitemapGeneratorAdapterInterface $generator, bool $debug = false)
    {
        $app    = Factory::getApplication();
        $params = $generator->getConfiguration();

        $app->triggerEvent('onBeforeGenerate', [$params]);

        $generator->setRuntimeContext($this->runtimeContextFactory->create());

        $result = $generator->getUrls();

        $app->triggerEvent('onAfterGetUrls', [&$result, $params]);

        if (!$debug) {
            $result->files = [];
            $xmlLimit      = (int) $params->get('xml_limit', 50000);
            $xml           = (count($result->includes) <= $xmlLimit)
                ? $generator->generateSingleXML($result->includes)
                : $generator->generateMultiXML($result->includes, $xmlLimit);

            if (!$xml) {
                throw new \Exception(Text::_('COM_JLSITEMAP_ERROR_SITEMAP_XML_CREATE_FAILED'), 500);
            }

            if (is_array($xml)) {
                $result->files = array_merge($result->files, $xml);

                if ($sitemapindex = $generator->generateXSL('sitemapindex')) {
                    $result->files[] = Path::clean(JPATH_ROOT . '/' . $sitemapindex);
                }

                if ($urlset = $generator->generateXSL('urlset')) {
                    $result->files[] = Path::clean(JPATH_ROOT . '/' . $urlset);
                }
            } else {
                $result->files[] = $xml;

                if ($urlset = $generator->generateXSL('urlset')) {
                    $result->files[] = Path::clean(JPATH_ROOT . '/' . $urlset);
                }
            }

            if ($json = $generator->generateJSON($result->includes)) {
                $result->files[] = $json;
            } else {
                throw new \Exception(Text::_('COM_JLSITEMAP_ERROR_SITEMAP_JSON_CREATE_FAILED'), 500);
            }

            $app->triggerEvent('onAfterGenerate', [&$result, $params]);
        }

        return $result;
    }
}
