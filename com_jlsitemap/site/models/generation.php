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
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class JLSitemapModelGeneration extends BaseDatabaseModel
{
	/**
	 * Sitemap xml
	 *
	 * @var string
	 *
	 * @since 1.1.0
	 */
	protected $_xml = null;

	/**
	 * Object with urls array
	 *
	 * @var object
	 *
	 * @since 1.1.0
	 */
	protected $_urls = null;

	/**
	 * Menu items array
	 *
	 * @var array
	 *
	 * @since 1.1.0
	 */
	protected $_menuItems = null;

	/**
	 * Method to generate sitemap.xml
	 *
	 * @param bool $debug Debug generation
	 *
	 * @return bool|object Array if successful, false otherwise and internal error is set.
	 *
	 * @since 1.1.0
	 */
	public function generate($debug = false)
	{
		// Get urs
		$urls = $this->getUrls();

		// Generate sitemap file
		if (!$debug)
		{
			// Get sitemap xml
			$xml = $this->getXML($urls->includes);

			// Regexp filter
			$filterRegexp = ComponentHelper::getParams('com_jlsitemap')->get('filter_regexp');
			if (!empty($filterRegexp))
			{
				foreach (ArrayHelper::fromObject($filterRegexp) as $regexp)
				{
					if (!empty($regexp['pattern']))
					{
						$xml = preg_replace($regexp['pattern'], $regexp['replacement'], $xml);
					}
				}
			}

			$file = JPATH_ROOT . '/sitemap.xml';
			if (File::exists($file))
			{
				File::delete($file);
			}
			File::append($file, $xml);
		}

		return $urls;
	}

	/**
	 * Method to get sitemap xml sting
	 *
	 * @param array $rows Include urls array
	 *
	 * @return string
	 *
	 * @since 1.1.0
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
					if (in_array($name, array('loc', 'changefreq', 'priority', 'lastmod')))
					{
						$url->addChild($name, $value);
					}
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
	 * @since 1.1.0
	 */
	protected function getUrls()
	{
		if ($this->_urls === null)
		{
			// Prepare config
			$config = ComponentHelper::getParams('com_jlsitemap');
			$config->set('siteRobots', Factory::getConfig()->get('robots'));
			$config->set('guestAccess', array_unique(Factory::getUser(0)->getAuthorisedViewLevels()));
			$config->set('multilanguage', Multilanguage::isEnabled());
			$config->set('changefreqPriority',
				array('always' => 1, 'hourly' => 2, 'daily' => 3, 'weekly' => 4, 'monthly' => 5, 'yearly' => 6, 'never' => 7));

			// Prepare menus filter
			$filterMenus = ($config->get('filter_menu')) ? $config->get('filter_menu_menus', array()) : false;
			$config->set('filterMenus', $filterMenus);

			// Prepare Raw filter
			$filterRaw = ($config->get('filter_raw_index') || $config->get('filter_raw_component')
				|| $config->get('filter_raw_get')) ? array() : false;
			if ($config->get('filter_raw_index'))
			{
				$filterRaw[] = 'index.php';
			}
			if ($config->get('filter_raw_component'))
			{
				$filterRaw[] = 'component/';
			}
			if ($config->get('filter_raw_get'))
			{
				$filterRaw[] = '?';
			}
			$config->set('filterRaw', $filterRaw);

			// Prepare strpos filter
			$filterStrpos = false;
			if (!empty(trim($config->get('filter_strpos'))))
			{
				$filterStrpos = preg_split('/\r\n|\r|\n/', $config->get('filter_strpos'));
				$filterStrpos = array_filter(array_map('trim', $filterStrpos), function ($string) {
					return !empty($string);
				});

				if (empty($filterStrpos))
				{
					$filterStrpos = false;
				}
			}
			$config->set('filterStrpos', $filterStrpos);

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
			$menuHomes       = array();
			$menuExcludes    = array();
			$filterMenuItems = (is_array($config->get('filterMenus', false))) ? array() : false;
			foreach ($this->getMenuItems($config) as $menu)
			{
				// Prepare url loc and urls arrays key
				$loc = Route::_($menu->loc);
				$key = (empty($loc)) ? '/' : $loc;

				// Create url Registry
				$url = new Registry();
				$url->set('loc', rtrim(Uri::root(), '/') . $loc);
				$url->set('changefreq', $menu->changefreq);
				$url->set('priority', $menu->priority);


				// Prepare exclude
				$exclude = false;
				if (!$menu->home)
				{
					$exclude = ($menu->exclude) ? Text::_('COM_JLSITEMAP_EXCLUDE_' . strtoupper($menu->exclude)) : false;

					// Filter by strpos
					if (!$exclude && is_array($filterStrpos))
					{
						$excludeByRaw = false;
						foreach ($filterStrpos as $filter)
						{
							if (mb_stripos($loc, $filter, 0, 'UTF-8') !== false)
							{
								$excludeByRaw = Text::_('COM_JLSITEMAP_EXCLUDE_FILTER_STRPOS');
								break;
							}
						}
						$exclude = $excludeByRaw;
					}

					// Filter by raw
					if (!$exclude && is_array($filterRaw))
					{
						$excludeByRaw = false;
						foreach ($filterRaw as $filter)
						{
							if (mb_stripos($loc, $filter, 0, 'UTF-8') !== false)
							{
								$excludeByRaw = Text::_('COM_JLSITEMAP_EXCLUDE_FILTER_RAW');
								break;
							}
						}
						$exclude = $excludeByRaw;
					}
				}

				// Add url to arrays
				$all[$key] = $url;
				if ($menu->home)
				{
					$menuHomes[] = $key;
				}
				if ($exclude)
				{
					$excludes[$key]     = $exclude;
					$menuExcludes[$key] = $menu->exclude;
				}
				else
				{
					$includes[$key] = $url;

					// Set menu items filter
					if (!$menu->home && is_array($filterMenuItems))
					{
						$filterMenuItems[] = $loc;
					}
				}
			}
			$config->set('menuHomes', $menuHomes);
			$config->set('filterMenuItems', (is_array($filterMenuItems) ? array_unique($filterMenuItems) : false));

			// Add urls from plugins
			$rows = array();
			PluginHelper::importPlugin('jlsitemap');
			$dispatcher = JEventDispatcher::getInstance();
			$dispatcher->trigger('onGetUrls', array(&$rows, &$config));
			foreach ($rows as $row)
			{
				$item = new Registry($row);
				if ($loc = $item->get('loc', false))
				{
					$loc  = Route::_($loc);
					$key  = (empty($loc)) ? '/' : $loc;
					$home = (in_array($key, $menuHomes));

					// Check menu excludes
					if (in_array($key, $menuExcludes)) continue;

					// Prepare url attributes
					$changefreq         = $item->get('changefreq', $config->get('changefreq', 'weekly'));
					$changefreqPriority = $config->get('changefreqPriority')[$changefreq];
					$priority           = $item->get('priority', $config->get('priority', '0.5'));
					$lastmod            = $item->get('lastmod', false);

					// Change attributes if url already exist
					$exist = (isset($all[$key])) ? $all[$key] : false;
					if ($exist)
					{
						$changefreq = ($changefreqPriority < $exist->get('changefreqPriority')) ? $changefreq : $exist->get('changefreq');
						$priority   = (floatval($priority) > floatval($exist->get('priority'))) ? $priority : $exist->get('priority');
						$lastmod    = ($lastmod && Factory::getDate($lastmod)->toUnix() > Factory::getDate($exist->get('lastmod'))->toUnix())
							? $lastmod : $exist->get('lastmod');
					}

					// Create url Registry
					$url = new Registry();
					$url->set('loc', rtrim(Uri::root(), '/') . $loc);
					$url->set('changefreq', $changefreq);
					$url->set('changefreqPriority', $config->get('changefreqPriority')[$changefreq]);
					$url->set('priority', $priority);
					if ($lastmod)
					{
						$url->set('lastmod', Factory::getDate($lastmod)->toISO8601());
					}

					// Prepare exclude
					$exclude = false;
					if (!$home)
					{
						$exclude = $item->get('exclude', false);

						// Filter by strpos
						if (!$exclude && is_array($filterStrpos))
						{
							$excludeByRaw = false;
							foreach ($filterStrpos as $filter)
							{
								if (mb_stripos($loc, $filter, 0, 'UTF-8') !== false)
								{
									$excludeByRaw = Text::_('COM_JLSITEMAP_EXCLUDE_FILTER_STRPOS');
									break;
								}
							}
							$exclude = $excludeByRaw;
						}

						// Filter by raw
						if (!$exclude && is_array($filterRaw))
						{
							$excludeByRaw = false;
							foreach ($filterRaw as $filter)
							{
								if (mb_stripos($loc, $filter, 0, 'UTF-8') !== false)
								{
									$excludeByRaw = Text::_('COM_JLSITEMAP_EXCLUDE_FILTER_RAW');
									break;
								}
							}
							$exclude = $excludeByRaw;
						}

						// Filter by menu
						if (!$exclude && is_array($filterMenuItems))
						{
							$excludeByMenu = Text::_('COM_JLSITEMAP_EXCLUDE_FILTER_MENU');
							foreach ($filterMenuItems as $filter)
							{
								if (mb_stripos($loc, $filter, 0, 'UTF-8') !== false)
								{
									$excludeByMenu = false;
									break;
								}
							}
							$exclude = $excludeByMenu;
						}
					}

					// Add url to arrays
					$all[$key] = $url;
					if ($exclude)
					{
						$excludes[$key] = $exclude;

						// Exclude item if already in array (last item has priority)
						unset($includes[$key]);
					}
					else
					{
						$includes[$key] = $url;
					}
				}
			}

			// Unset index.php page from arrays
			unset($all['index.php']);
			unset($includes['index.php']);
			unset($excludes['index.php']);

			// Sort urls arrays
			ksort($all);
			ksort($includes);
			ksort($excludes);

			// Prepare urls object
			$urls           = new stdClass();
			$urls->includes = $includes;
			$urls->excludes = $excludes;
			$urls->all      = $all;

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
	 * @since 1.1.0
	 */
	protected function getMenuItems($config)
	{
		if ($this->_menuItems === null)
		{
			// Get menu items
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('m.id', 'm.menutype', 'm.type', 'm.published', 'm.access', 'm.home', 'm.params', 'm.language', 'e.extension_id'))
				->from($db->quoteName('#__menu', 'm'))
				->join('LEFT', '#__extensions AS e ON e.extension_id = m.component_id AND e.enabled = 1')
				->where('m.client_id = 0')
				->where('m.id > 1')
				->where('m.published IN (0, 1)')
				->order($db->escape('m.lft') . ' ' . $db->escape('asc'));
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

				// Prepare exclude attribute
				$params  = new Registry($row->params);
				$exclude = false;
				if (!$row->home)
				{
					if (is_array($config->get('filterMenus', false)) && !empty($config->get('filterMenus')) &&
						!in_array($row->menutype, $config->get('filterMenus')))
					{
						$exclude = 'filter_menu';
					}
					if (preg_match('/noindex/', $params->get('robots', $config->get('siteRobots'))))
					{
						$exclude = 'menu_noindex';
					}
					if ($row->published != 1)
					{
						$exclude = 'menu_published';
					}
					if (in_array($row->type, $excludeTypes))
					{
						$exclude = 'menu_system_type';
					}
					if ($row->type == 'component' && empty($row->extension_id))
					{
						$exclude = 'menu_component';
					}
					if (!in_array($row->access, $config->get('guestAccess', array())))
					{
						$exclude = 'menu_access';
					}
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
}