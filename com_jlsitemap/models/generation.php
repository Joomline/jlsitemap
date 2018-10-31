<?php
/**
 * @package    JLSitemap Component
 * @version    0.0.1
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class JLSitemapModelGeneration extends BaseDatabaseModel
{
	/**
	 * jlsitemap plugins
	 *
	 * @var array
	 *
	 * @since 0.0.1
	 */
	protected $_plugins = null;

	/**
	 * Sitemap xml
	 *
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $_xml = null;

	/**
	 * Urls array
	 *
	 * @var array
	 *
	 * @since 0.0.1
	 */
	protected $_urls = null;

	/**
	 * Method to generate sitemap.xml
	 *
	 * @return bool|array Array if successful, false otherwise and internal error is set.
	 *
	 * @since 0.0.1
	 */
	public function generate()
	{
		// Check plugins
		if (empty($this->getPlugins()))
		{
			$this->setError('COM_JLSITEMAP_ERROR_PLUGINS_NOT_FOUND');

			return false;
		}

		// Get urs
		$urls = $this->getUrls();

		// Get sitemap xml
		$xml = $this->getXML();

		$file = JPATH_ROOT . '/sitemap.xml';
		if (File::exists($file))
		{
			File::delete($file);
		}
		File::append($file, $xml);

		return $urls;
	}

	/**
	 * Method to get sitemap xml sting
	 *
	 * @return string
	 *
	 * @since 0.0.1
	 */
	protected function getXML()
	{
		if ($this->_xml === null)
		{
			$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');

			foreach ($this->getUrls() as $registry)
			{
				$url = $xml->addChild('url');
				foreach ($registry->toArray() as $name => $value)
				{
					$url->addChild($name, $value);
				}
			}

			$this->_xml = $xml->asXML();
		}

		return $this->_xml;
	}

	/**
	 * Method to get sitemap urls array
	 *
	 * @return array
	 *
	 * @since 0.0.1
	 */
	protected function getUrls()
	{
		if ($this->_urls === null)
		{
			$site          = SiteApplication::getInstance('site');
			$router        = $site->getRouter();
			$config        = ComponentHelper::getParams('com_jlsitemap');
			$access        = array_unique(Factory::getUser(0)->getAuthorisedViewLevels());
			$multilanguage = Multilanguage::isEnabled();

			// Set Changefreq priority
			$changefreqPriority = array(
				'always'  => 1,
				'hourly'  => 2,
				'daily'   => 3,
				'weekly'  => 4,
				'monthly' => 5,
				'yearly'  => 6,
				'never'   => 7
			);

			// Create urls array
			$urls = array();
			foreach ($this->getPlugins() as $name => $plugin)
			{
				$pluginUrls = $plugin->onGetUrls($access, $multilanguage);
				if (!empty($pluginUrls))
				{
					foreach ($pluginUrls as $url)
					{
						$url = new Registry($url);
						if ($loc = $url->get('loc', false))
						{
							// Prepare url loc and array key
							$loc = trim(str_replace('administrator/', '', $router->build($loc)->toString()), '/');
							$key = (empty($loc)) ? 'site_root' : $loc;

							// Prepare url attributes
							$changefreq = $url->get('changefreq', $config->get('changefreq', 'weekly'));
							$priority   = $url->get('priority', $config->get('priority', '0.5'));
							$lastmod    = $url->get('lastmod', false);

							// Change attributes if url already exist
							$exist = (isset($urls[$key])) ? $urls[$key] : false;
							if ($exist)
							{
								$changefreq = ($changefreqPriority[$changefreq] < $changefreqPriority[$exist->get('changefreq')]) ?
									$changefreq : $exist->get('changefreq');
								$priority   = (floatval($priority) > floatval($exist->get('priority'))) ? $priority : $exist->get('priority');
								$lastmod    = ($lastmod && $lastmod > $exist->get('lastmod')) ? $lastmod : $exist->get('lastmod');
							}

							// Create url object
							$item             = new stdClass();
							$item->loc        = Uri::root() . $loc;
							$item->changefreq = $changefreq;
							$item->priority   = $priority;
							if ($lastmod)
							{
								$item->lastmod = Factory::getDate($lastmod)->toISO8601();
							}

							// Add url to array
							$urls[$key] = new Registry($item);
						}
					}
				}
			}

			// Add root if not in array
			if (empty($urls['site_root']))
			{
				// Prepare root object
				$root             = new stdClass();
				$root->loc        = Uri::root();
				$root->changefreq = $config->get('changefreq', 'weekly');
				$root->priority   = $config->get('priority', '0.5');

				// Add root to array
				$urls['site_root'] = new Registry($root);
			}

			// Remove slash in root page
			$urls['site_root']->set('loc', rtrim($urls['site_root']->get('loc'), '/'));

			// Remove index.php
			if (isset($urls['index.php']))
			{
				unset($urls['index.php']);
			}

			$this->_urls = $urls;
		}

		return $this->_urls;
	}

	/**
	 * Method to get jlsitemap plugins
	 *
	 * @return array
	 *
	 * @since 0.0.1
	 */
	protected function getPlugins()
	{
		if ($this->_plugins === null)
		{
			// Get jlsitemap plugins
			PluginHelper::importPlugin('jlsitemap');
			$rows = PluginHelper::getPlugin('jlsitemap');

			// Create jlsitemap plugins array
			$plugins = array();
			foreach ($rows as $plugin)
			{
				$key       = $plugin->name;
				$className = 'plg' . $plugin->type . $plugin->name;
				if (class_exists($className))
				{
					$plugin = new $className($this, (array) $plugin);
					if (method_exists($className, 'onGetUrls'))
					{
						$plugins[$key] = $plugin;
					}
				}
			}

			$this->_plugins = $plugins;
		}

		return $this->_plugins;
	}

	/**
	 * Attach an observer object
	 *
	 * @param   object $observer An observer object to attach
	 *
	 * @return  void
	 *
	 * @since 0.0.1
	 */
	public function attach($observer)
	{
	}
}