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
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class com_jlsitemapInstallerScript
{
	/**
	 * Runs right after any installation action.
	 *
	 * @param   string            $type    Type of PostFlight action. Possible values are:
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.4.0
	 */
	function postflight($type, $parent)
	{
		// Add access key
		$this->addAccessKey();

		// Parse layouts
		$this->parseLayouts($parent->getParent()->getManifest()->layouts, $parent->getParent());
	}

	/**
	 * Method to parse through a layout element of the installation manifest and take appropriate action.
	 *
	 * @param   SimpleXMLElement  $element    The XML node to process.
	 * @param   Installer         $installer  Installer calling object.
	 *
	 * @return  boolean     True on success
	 *
	 * @since  1.6.2
	 */
	public function parseLayouts(SimpleXMLElement $element, $installer)
	{
		if (!$element || !count($element->children()))
		{
			return false;
		}

		// Get destination
		$folder      = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
		$destination = Path::clean(JPATH_ROOT . '/layouts' . $folder);

		// Get source
		$folder = (string) $element->attributes()->folder;
		$source = ($folder && file_exists($installer->getPath('source') . '/' . $folder)) ?
			$installer->getPath('source') . '/' . $folder : $installer->getPath('source');

		// Prepare files
		$copyFiles = array();
		foreach ($element->children() as $file)
		{
			$path         = array();
			$path['src']  = Path::clean($source . '/' . $file);
			$path['dest'] = Path::clean($destination . '/' . $file);

			// Is this path a file or folder?
			$path['type'] = $file->getName() === 'folder' ? 'folder' : 'file';
			if (basename($path['dest']) !== $path['dest'])
			{
				$newdir = dirname($path['dest']);
				if (!Folder::create($newdir))
				{
					Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir), Log::WARNING, 'jerror');

					return false;
				}
			}

			$copyFiles[] = $path;
		}

		return $installer->copyFiles($copyFiles);
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
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.4.0
	 */
	public function uninstall($parent)
	{
		// Remove layouts
		$this->removeLayouts($parent->getParent()->getManifest()->layouts);

		// Remove sitemap files
		$files = array(
			JPATH_ROOT . '/sitemap.xml',
			JPATH_ROOT . '/sitemap.json',
		);
		foreach ($files as $file)
		{
			if (File::exists($file))
			{
				File::delete($file);
			}
		}
	}

	/**
	 * Method to parse through a layouts element of the installation manifest and remove the files that were installed.
	 *
	 * @param   SimpleXMLElement  $element  The XML node to process.
	 *
	 * @return  boolean     True on success
	 *
	 * @since  1.6.2
	 */
	protected function removeLayouts(SimpleXMLElement $element)
	{
		if (!$element || !count($element->children()))
		{
			return false;
		}

		// Get the array of file nodes to process
		$files = $element->children();

		// Get source
		$folder = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
		$source = Path::clean(JPATH_ROOT . '/layouts' . $folder);

		// Process each file in the $files array (children of $tagName).
		foreach ($files as $file)
		{
			$path = Path::clean($source . '/' . $file);

			// Actually delete the files/folders
			if (is_dir($path))
			{
				$val = Folder::delete($path);
			}
			else
			{
				$val = File::delete($path);
			}

			if ($val === false)
			{
				Log::add('Failed to delete ' . $path, Log::WARNING, 'jerror');
				$retval = false;
			}
		}

		if (!empty($folder))
		{
			Folder::delete($source);
		}

		return $retval;
	}
}