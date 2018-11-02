<?php
/**
 * @package    JLSitemap Component
 * @version    0.0.2
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\CMSHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class JLSitemapHelper extends CMSHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  void
	 *
	 * @since 0.0.1
	 */
	static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(Text::_('COM_JLSITEMAP_HOME'),
			'index.php?option=com_jlsitemap&view=home',
			$vName == 'home');

		JHtmlSidebar::addEntry(Text::_('COM_JLSITEMAP_GENERATION'),
			'index.php?option=com_jlsitemap&task=generate',
			$vName == 'generation');

		if (File::exists(JPATH_ROOT . '/sitemap.xml'))
		{
			JHtmlSidebar::addEntry(Text::_('COM_JLSITEMAP_SITEMAP'),
				Uri::root() . 'sitemap.xml',
				$vName == 'sitemap');
		}

		JHtmlSidebar::addEntry(Text::_('COM_JLSITEMAP_PLUGINS'),
			'index.php?option=com_plugins&filter[folder]=jlsitemap',
			$vName == 'plugins');


		JHtmlSidebar::addEntry(Text::_('COM_JLSITEMAP_CONFIG'),
			'index.php?option=com_config&view=component&component=com_jlsitemap',
			$vName == 'config');
	}
}