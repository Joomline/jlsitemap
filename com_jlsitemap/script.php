<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

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
	 * @since  1.4.0
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
	 * Method to add access key to component params
	 *
	 * @return void
	 *
	 * @since  1.4.0
	 */
	protected function addAccessKey()
	{
		JLoader::register('JLSitemapHelperSecrets', JPATH_ADMINISTRATOR . '/components/com_jlsitemap/helpers/secrets.php');

		JLSitemapHelperSecrets::getAccessKey();
	}

	/**
	 * This method is called after extension is uninstalled.
	 *
	 * @param  InstallerAdapter $parent Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.4.0
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

	/**
	 * Method to remove forgot model
	 *
	 * @return bool
	 *
	 * @since  1.6.0
	 */
	function update()
	{
		if (File::exists(JPATH_SITE . '/components/com_jlsitemap/models/generation.php'))
		{
			File::delete(JPATH_SITE . '/components/com_jlsitemap/models/generation.php');
		}

		return true;
	}
}