<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerAdapter;

class PlgSystemJLSitemap_CronInstallerScript
{
	/**
	 * Runs right after any installation action.
	 *
	 * @param  string           $type   Type of PostFlight action. Possible values are:
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since 0.0.1
	 */
	function postflight($type, $parent)
	{
		// Enable plugin
		if ($type == 'install')
		{
			$this->enablePlugin($parent);
		}

		// Install layouts
		$this->installLayouts($parent);
	}

	/**
	 * Enable plugin after installation
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since 1.4.0
	 */
	protected function enablePlugin($parent)
	{
		// Prepare plugin object
		$plugin          = new stdClass();
		$plugin->type    = 'plugin';
		$plugin->element = $parent->getElement();
		$plugin->folder  = (string) $parent->getParent()->manifest->attributes()['group'];
		$plugin->enabled = 1;

		// Update record
		Factory::getDbo()->updateObject('#__extensions', $plugin, array('type', 'element', 'folder'));
	}

	/**
	 * Method to install/update extension layouts
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.4.0
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
		array_pop($dirs);

		if (!empty($dirs))
		{
			foreach ($dirs as $i => $dir)
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
	 * This method is called after extension is uninstalled.
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  0.0.2
	 */
	public function uninstall($parent)
	{
		// Uninstall layouts
		$this->uninstallLayouts($parent);
	}

	/**
	 * Method to uninstall extension layouts
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.4.0
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