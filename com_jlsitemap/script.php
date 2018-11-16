<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;

class com_jlsitemapInstallerScript
{
	/**
	 * Method to add access key to component params
	 *
	 * @return bool
	 *
	 * @since  1.1.0
	 */
	function postflight()
	{
		$params = ComponentHelper::getComponent('com_jlsitemap')->getParams();
		if (empty($params->get('access_key')))
		{
			// Prepare access key
			$access_key = '';
			$values     = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's',
				't', 'u', 'v', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
				'P', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
			for ($i = 0; $i < 15; $i++)
			{
				$key        = rand(0, count($values) - 1);
				$access_key .= $values[$key];
			}
			$params->set('access_key', $access_key);

			$component          = new stdClass();
			$component->element = 'com_jlsitemap';
			$component->params  = (string) $params;
			Factory::getDbo()->updateObject('#__extensions', $component, array('element'));
		}

		return true;
	}

	/**
	 * Method to remove home view folder
	 *
	 * @return bool
	 *
	 * @since  1.1.0
	 */
	function update()
	{
		if (Folder::exists(JPATH_ADMINISTRATOR . '/components/com_jlsitemap/views/home'))
		{
			Folder::delete(JPATH_ADMINISTRATOR . '/components/com_jlsitemap/views/home');
		}

		return true;
	}
}
