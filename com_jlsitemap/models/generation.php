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
use Joomla\CMS\Language\Text;
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
	 * Object with urls array
	 *
	 * @var object
	 *
	 * @since 0.0.1
	 */
	protected $_urls = null;

	/**
	 * Menu items array
	 *
	 * @var array
	 *
	 * @since 0.0.1
	 */
	protected $_menuItems = null;

	/**
	 * Method to generate sitemap.xml
	 *
	 * @return bool|object Array if successful, false otherwise and internal error is set.
	 *
	 * @since 0.0.1
	 */
	public function generate()
	{
		// Get urs
		$urls = $this->getUrls();

		// Get sitemap xml
		$xml = $this->getXML($urls->inludes);

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
	 * @param array $rows Include urls array
	 *
	 * @return string
	 *
	 * @since 0.0.1
	 */
	protected function getXML($rows = array())
	{
		if ($this->_xml === null)
		{
			$rows = (empty($rows)) ? $this->getUrls()->includes : $rows;
			$xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');

			foreach ($rows as $row)
			{
				$url = $xml->addChild('url');
				foreach ($row->toArray() as $name => $value)
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
	 * @return object
	 *
	 * @since 0.0.1
	 */
	protected function getUrls()
	{
		if ($this->_urls === null)
		{
			// Prepare variables
			$site   = SiteApplication::getInstance('site');
			$router = $site->getRouter();
			$config = ComponentHelper::getParams('com_jlsitemap');
			$config->set('siteRobots', Factory::getConfig()->get('robots'));
			$config->set('guestAccess', array_unique(Factory::getUser(0)->getAuthorisedViewLevels()));
			$config->set('multilanguage', Multilanguage::isEnabled());


			// Create urls arrays
			$all      = array();
			$includes = array();
			$excludes = array();

			// Add home page to urls
			$home = new Registry();
			$home->set('loc', Uri::root());
			$home->set('changefreq', $config->get('changefreq', 'weekly'));
			$home->set('priority', $config->get('priority', '0.5'));
			$all ['/']     = $home;
			$includes['/'] = $home;

			// Add menu items to urls arrays
			$menuHomes    = array();
			$menuExcludes = array();
			foreach ($this->getMenuItems($config) as $menu)
			{
				// Prepare url loc and urls arrays key
				$loc = trim(str_replace('administrator/', '', $router->build($menu->loc)->toString()), '/');
				$key = (empty($loc)) ? '/' : $loc;

				// Create url Registry
				$url = new Registry();
				$url->set('loc', Uri::root() . $loc);
				$url->set('changefreq', $menu->changefreq);
				$url->set('priority ', $menu->priority);

				// Add url to arrays
				$all[$key] = $url;
				if ($menu->home)
				{
					$menuHomes[] = $key;
				}
				if ($menu->exclude && !$menu->home)
				{
					$excludes[$key]     = Text::_('COM_JLSITEMAP_EXCLUDE_MENU_' . strtoupper($menu->exclude));
					$menuExcludes[$key] = $menu->exclude;
				}
				else
				{
					$includes[$key] = $url;
				}
			}

			// Sort urls arrays
			ksort($all);
			ksort($includes);
			ksort($excludes);

			// Prepare urls object
			$urls           = new stdClass();
			$urls->includes = $includes;
			$urls->excludes = $excludes;
			$urls->all      = $all;

			echo '<pre>', print_r($urls, true), '</pre>';

			// Set urls object
			$this->_urls = $urls;
		}

		return $this->_urls;
	}

	/**
	 * Method to get menu items array
	 *
	 * @param Registry $config Component config
	 *
	 * @return array
	 *
	 * @since 0.0.1
	 */
	protected function getMenuItems($config)
	{
		if ($this->_menuItems === null)
		{
			// Get menu items
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('id', 'type', 'published', 'access', 'home', 'params', 'language'))
				->from($db->quoteName('#__menu'))
				->where('client_id = 0')
				->where('id > 1')
				->order($db->escape('lft') . ' ' . $db->escape('asc'));
			$db->setQuery($query);
			$rows = $db->loadObjectList('id');

			// Create menu items array
			$items        = array();
			$excludeTypes = array('alias', 'separator', 'heading', 'url');
			foreach ($rows as $row)
			{
				// Prepare loc attribute
				$loc = 'index.php?Itemid=' . $row->id;
				if (!empty($row->language) && $row->language !== '*' && $config->get('multilanguage', false))
				{
					$loc .= '&lang=' . $row->language;
				}

				// Prepare exclude & noAccess attribute
				$params = new Registry($row->params);

				$exclude = false;
				if (preg_match('/noindex/', $params->get('robots', $config->get('siteRobots'))))
				{
					$exclude = 'noindex';
				}
				if ($row->published != 1)
				{
					$exclude = 'published';
				}
				if (in_array($row->type, $excludeTypes))
				{
					$exclude = 'system_type';
				}
				if (!in_array($row->access, $config->get('guestAccess', array())))
				{
					$exclude = 'access';
				}

				// Prepare menu item object
				$item             = new stdClass();
				$item->loc        = $loc;
				$item->changefreq = $config->get('changefreq', 'weekly');
				$item->priority   = $config->get('priority', '0.5');
				$item->home       = $row->home;
				$item->exclude    = $exclude;

				// Add menu item to array
				$items[] = $item;
			}

			$this->_menuItems = $items;
		}

		return $this->_menuItems;
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