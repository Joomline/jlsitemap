<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;

class PlgSystemJLSitemap_CronInstallerScript
{
	/**
	 * Enable plugin after installation
	 *
	 * @param  string $type      Type of PostFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param         $parent    Parent object calling object.
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	function postflight($type, $parent)
	{
		// Enable plugin
		if ($type == 'install')
		{
			// Prepare plugin object
			$plugin          = new stdClass();
			$plugin->type    = 'plugin';
			$plugin->element = 'jlsitemap_cron';
			$plugin->folder  = 'system';
			$plugin->enabled = 1;

			// Update record
			Factory::getDbo()->updateObject('#__extensions', $plugin, array('type', 'element', 'folder'));
		}

		// Move layouts
		$src  = JPATH_PLUGINS . '/system/jlsitemap_cron/layouts';
		$dest = JPATH_ROOT . '/layouts/plugins/system';

		// System plugins layouts path check
		if (!Folder::exists($dest))
		{
			Folder::create($dest);
		}
		$dest .= '/jlsitemap_cron';

		// Delete old layouts
		if (Folder::exists($dest))
		{
			Folder::delete($dest);
		}

		// Move layouts
		Folder::move($src, $dest);

		return true;
	}

	/**
	 * Remove layouts
	 *
	 * @param   JAdapterInstance $adapter The object responsible for running this script
	 *
	 * @since 0.0.2
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		Folder::delete(JPATH_ROOT . '/layouts/plugins/system/jlsitemap_cron');
	}
}