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
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerAdapter;

class com_jlsitemapInstallerScript
{
	/**
	 * Runs right after any installation action.
	 *
	 * @param  string           $type   Type of PostFlight action. Possible values are:
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.3.1
	 */
	function postflight($type, $parent)
	{
		// Add access key
		$this->addAccessKey();

		// Install layouts
		$this->installLayouts($parent);
	}

	/**
	 * Method to install/update extension layouts
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.3.1
	 */
	protected function installLayouts($parent)
	{
		$root   = JPATH_ROOT . '/layouts';
		$source = $parent->getParent()->getPath('source');

		// Get attributes
		$attributes = $parent->getParent()->manifest->xpath('layouts');
		if (!is_array($attributes) || empty($attributes[0])) return;

		// Get destination
		$destination = (!empty($attributes[0]->attributes()->destination)) ?
			(string) $attributes[0]->attributes()->destination : false;
		if (!$destination) return;

		// Remove old layouts
		if (Folder::exists($root . '/' . trim($destination, '/')))
		{
			Folder::delete($root . '/' . trim($destination, '/'));
		}

		// Get folder
		$folder = (!empty($attributes[0]->attributes()->folder)) ?
			(string) $attributes[0]->attributes()->folder : 'layouts';
		if (!Folder::exists($source . '/' . trim($folder, '/'))) return;

		// Prepare src and dest
		$src  = $source . '/' . trim($folder, '/');
		$dest = $root . '/' . trim($destination, '/');

		// Check destination
		$path = $root;
		$dirs = explode('/', $destination);
		if (count($dirs) > 1)
		{
			foreach (array_pop($dirs) as $dir)
			{
				$path .= '/' . $dir;
				if (!Folder::exists($path))
				{
					Folder::create($path);
				}
			}
		}

		// Move layouts
		Folder::move($src, $dest);
	}

	/**
	 * Method to add access key to component params
	 *
	 * @return void
	 *
	 * @since  1.3.1
	 */
	protected function addAccessKey()
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
	}

	/**
	 * This method is called after extension is uninstalled.
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.3.1
	 */
	public function uninstall($parent)
	{
		// Uninstall layouts
		$this->uninstallLayouts($parent);

		// Remove sitemap
		if (File::exists(JPATH_ROOT . '/sitemap.xml'))
		{
			File::delete(JPATH_ROOT . '/sitemap.xml');
		}
	}

	/**
	 * Method to uninstall extension layouts
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.3.1
	 */
	protected function uninstallLayouts($parent)
	{
		$attributes = $parent->getParent()->manifest->xpath('layouts');
		if (!is_array($attributes) || empty($attributes[0])) return;

		$destination = (!empty($attributes[0]->attributes()->destination)) ?
			(string) $attributes[0]->attributes()->destination : false;
		if (!$destination) return;

		$dest = JPATH_ROOT . '/layouts/' . trim($destination, '/');

		if (Folder::exists($dest))
		{
			Folder::delete($dest);
		}
	}
}
