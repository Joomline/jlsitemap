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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Uri\Uri;

class SitemapRuntimeContextFactory
{
    public function create(): array
    {
        $siteConfig = Factory::getContainer()->get('config');

        return [
            'siteSef'         => ($siteConfig->get('sef') == 1),
            'siteName'        => (string) $siteConfig->get('sitename'),
            'siteRobots'      => $siteConfig->get('robots'),
            'siteRoot'        => $this->resolveSiteRoot(),
            'guestAccess'     => $this->getGuestAccessLevels(),
            'multilanguage'   => Multilanguage::isEnabled(),
            'defaultLanguage' => ComponentHelper::getParams('com_languages')->get('site', 'en-GB'),
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
}
