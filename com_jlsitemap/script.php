<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\CMS\Version;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

return new class () implements ServiceProviderInterface {
	public function register(Container $container)
	{
		$container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {

			/**
			 * The application object
			 *
			 * @var  AdministratorApplication
			 *
			 * @since  1.0.0
			 */
			protected AdministratorApplication $app;

			/**
			 * Minimum Joomla version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  1.0.0
			 */
			protected string $minimumJoomla = '5.0.0';

			/**
			 * Minimum PHP version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  1.0.0
			 */
			protected string $minimumPhp = '8.1.0';

			/**
			 * Constructor.
			 *
			 * @param   AdministratorApplication  $app  The application object.
			 *
			 * @since 1.0.0
			 */
			public function __construct(AdministratorApplication $app)
			{
				$this->app = $app;
			}

			/**
			 * This method is called after a component is installed.
			 *
			 * @param   \stdClass  $installer  - Parent object calling this method.
			 *
			 * @return void
			 */
			public function install(InstallerAdapter $adapter): bool
			{

				return true;

			}

			/**
			 * Function called after the extension is uninstalled.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function uninstall(InstallerAdapter $adapter): bool
			{
				// Remove layouts
				$this->removeLayouts($adapter->getParent()->getManifest()->layouts);

				// Remove sitemap files
				$filename = ComponentHelper::getParams('com_jlsitemap')->get('filename', 'sitemap');
				$files    = Folder::files(JPATH_ROOT, $filename . '_[0-9]*\.xml', false, true);
				$files [] = JPATH_ROOT . '/' . $filename . '.xml';
				$files [] = JPATH_ROOT . '/' . $filename . '.json';
				$files [] = JPATH_ROOT . '/' . $filename . '.xml';
				$files [] = JPATH_ROOT . '/' . $filename . '_urlset.xsl';
				$files [] = JPATH_ROOT . '/' . $filename . '_sitemapindex.xsl';
				foreach ($files as $file)
				{
					if (\is_file($file))
					{
						File::delete($file);
					}
				}

				return true;
			}

			/**
			 * Function called after the extension is updated.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function update(InstallerAdapter $adapter): bool
			{

				return true;

			}

			/**
			 * Function called before extension installation/update/removal procedure commences.
			 *
			 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function preflight(string $type, InstallerAdapter $adapter): bool
			{

				return true;

			}


			/**
			 * Function called after extension installation/update/removal procedure commences.
			 *
			 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.0.0
			 */
			public function postflight(string $type, InstallerAdapter $adapter): bool
			{
				if ($type != 'uninstall')
				{
					// Parse layouts
					$this->parseLayouts($adapter->getParent()->getManifest()->layouts, $adapter->getParent());
				}

				return true;
			}


			/**
			 * Method to parse through a layout element of the installation manifest and take appropriate action.
			 *
			 * @param   SimpleXMLElement  $element    The XML node to process.
			 * @param   InstallerAdapter         $installer  Installer calling object.
			 *
			 * @return  boolean     True on success. False on failure.
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
				$copyFiles = [];
				foreach ($element->children() as $file)
				{
					$path         = [];
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
			 * Method to parse through a layouts element of the installation manifest and remove the files that were installed.
			 *
			 * @param   SimpleXMLElement  $element  The XML node to process.
			 *
			 * @return  boolean  True on success.
			 *
			 * @since  1.3.0
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

						return false;
					}
				}

				if (!empty($folder))
				{
					Folder::delete($source);
				}

				return true;
			}

		});
	}
};
