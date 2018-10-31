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
use Joomla\CMS\Plugin\CMSPlugin;

class plgJLSitemapMenu extends CMSPlugin
{
	/**
	 * Urls array
	 *
	 * @var    array
	 *
	 * @since 0.0.1
	 */
	protected $_urls = null;

	/**
	 * Method to get urls array
	 *
	 * @param array $access Guest access levels
	 *
	 * @return array Urls array with attributes
	 *
	 * @since 0.0.1
	 */
	public function onGetUrls($access = array())
	{
		if ($this->_urls === null)
		{
			// Get menu items
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('id', 'language'))
				->from($db->quoteName('#__menu'))
				->where('client_id = 0')
				->where('published = 1');

			// Filter by types
			$excludeTypes = array(
				$db->quote('alias'),
				$db->quote('separator'),
				$db->quote('heading'),
				$db->quote('url')
			);
			$query->where('type NOT IN (' . implode(',', $excludeTypes) . ')');

			// Filter by menus
			$menus = $this->params->def('menus', array());
			if (!empty($menus))
			{
				foreach ($menus as $key => $menu)
				{
					$menus[$key] = $db->quote($menu);
				}
				$query->where('menutype IN (' . implode(',', $menus) . ')');
			}

			// Filter by access
			if (!empty($access))
			{
				$query->where('access IN (' . implode(',', $access) . ')');
			}

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$changefreq = $this->params->def('changefreq', 'weekly');
			$priority   = $this->params->def('priority', '0.5');

			// Create urls array
			$urls = array();
			foreach ($rows as $row)
			{
				// Prepare ulr object
				$url             = new stdClass();
				$url->loc        = 'index.php?Itemid=' . $row->id . '&lang=' . $row->language;
				$url->changefreq = $changefreq;
				$url->priority   = $priority;

				// Add url to array
				$urls[] = $url;
			}

			$this->_urls = $urls;
		}

		return $this->_urls;
	}
}