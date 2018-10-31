<?php
/**
 * @package    JLSitemap - Menu Plugin
 * @version    0.0.1
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class PlgJLSitemapMenuInstallerScript
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
		if ($type == 'install')
		{
			// Prepare plugin object
			$plugin          = new stdClass();
			$plugin->type    = 'plugin';
			$plugin->element = 'menu';
			$plugin->folder  = 'jlsitemap';
			$plugin->enabled = 1;

			// Update record
			Factory::getDbo()->updateObject('#__extensions', $plugin, array('type', 'element', 'folder'));
		}

		return true;
	}
}