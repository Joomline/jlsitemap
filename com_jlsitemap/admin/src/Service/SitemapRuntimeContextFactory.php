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

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Uri\Uri;
use Throwable;

class SitemapRuntimeContextFactory
{
    protected static bool $siteRoutingContextPrepared = false;

    public function create(): array
    {
        $siteConfig       = Factory::getContainer()->get('config');
        $multilanguage    = Multilanguage::isEnabled();
        $defaultLanguage  = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

        $this->prepareSiteRoutingContext($multilanguage, $defaultLanguage);

        return [
            'siteSef'         => ($siteConfig->get('sef') == 1),
            'siteName'        => (string) $siteConfig->get('sitename'),
            'siteRobots'      => $siteConfig->get('robots'),
            'siteRoot'        => $this->resolveSiteRoot(),
            'guestAccess'     => $this->getGuestAccessLevels(),
            'multilanguage'   => $multilanguage,
            'defaultLanguage' => $defaultLanguage,
        ];
    }

    protected function getGuestAccessLevels(): array
    {
        return Access::getAuthorisedViewLevels(0);
    }

    protected function resolveSiteRoot(): string
    {
        return Uri::getInstance()->toString(['scheme', 'host', 'port']);
    }

    protected function prepareSiteRoutingContext(bool $multilanguage, string $defaultLanguage): void
    {
        if (!$multilanguage || self::$siteRoutingContextPrepared) {
            return;
        }

        $languageFilterEnabled = PluginHelper::isEnabled('system', 'languagefilter');

        if (!$languageFilterEnabled) {
            self::$siteRoutingContextPrepared = true;

            return;
        }

        try {
            $container = Factory::getContainer();
            $siteApp   = $container->get(SiteApplication::class);
            $siteApp->setLanguageFilter(true);
            $siteApp->set('language', $defaultLanguage);

            if (!$this->hasLanguageFilterBuildRule($container->get(SiteRouter::class))) {
                $languageFilter = Factory::getApplication()->bootPlugin('languagefilter', 'system');

                if (method_exists($languageFilter, 'onAfterInitialise')) {
                    $languageFilter->onAfterInitialise(
                        new AfterInitialiseEvent('onAfterInitialise', ['subject' => $siteApp])
                    );
                }
            }
        } catch (Throwable) {
            return;
        }

        self::$siteRoutingContextPrepared = true;
    }

    protected function hasLanguageFilterBuildRule(SiteRouter $router): bool
    {
        foreach ($router->getRules()['buildpreprocess'] ?? [] as $rule) {
            if (!is_array($rule) || ($rule[1] ?? null) !== 'preprocessBuildRule' || !is_object($rule[0] ?? null)) {
                continue;
            }

            if (str_contains(get_class($rule[0]), '\\Plugin\\System\\LanguageFilter\\')) {
                return true;
            }
        }

        return false;
    }
}
