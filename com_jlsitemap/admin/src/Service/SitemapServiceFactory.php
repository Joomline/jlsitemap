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
use Joomla\Component\JLSitemap\Administrator\Extension\JLSitemapComponent;

abstract class SitemapServiceFactory
{
    public static function getGenerationService(): SitemapGenerationService
    {
        $component = Factory::getApplication()->bootComponent('com_jlsitemap');

        if ($component instanceof JLSitemapComponent && $component->hasContainerService(SitemapGenerationService::class)) {
            return $component->getContainer()->get(SitemapGenerationService::class);
        }

        return new SitemapGenerationService(new SitemapRuntimeContextFactory());
    }
}
