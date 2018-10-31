<?php
/**
 * @package    JLSitemap - Content Plugin
 * @version    0.0.1
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

class plgJLSitemapContent extends CMSPlugin
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
	 * @param array $access        Guest access levels
	 * @param bool  $multilanguage Enable multilanguage in site
	 *
	 * @return array Urls array with attributes
	 *
	 * @since 0.0.1
	 */
	public function onGetUrls($access = array(), $multilanguage = false)
	{
		if ($this->_urls === null)
		{
			$db   = Factory::getDbo();
			$urls = array();

			// Categories
			if ($this->params->def('categories_enable', false))
			{
				$query = $db->getQuery(true)
					->select(array('id', 'language'))
					->from($db->quoteName('#__categories'))
					->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
					->where('published = 1');

				// Filter by access
				if (!empty($access))
				{
					$query->where('access IN (' . implode(',', $access) . ')');
				}

				$db->setQuery($query);
				$rows = $db->loadObjectList();

				$changefreq = $this->params->def('categories_changefreq', 'weekly');
				$priority   = $this->params->def('categories_priority', '0.5');

				// Add categories to urls array
				foreach ($rows as $row)
				{
					// Prepare loc attribute
					$loc = 'index.php?option=com_content&view=category&id=' . $row->id;
					if (!empty($row->language) && $row->language !== '*' && $multilanguage)
					{
						$loc .= '&lang=' . $row->language;
					}

					// Prepare ulr object
					$url             = new stdClass();
					$url->loc        = $loc;
					$url->changefreq = $changefreq;
					$url->priority   = $priority;

					// Add url to array
					$urls[] = $url;
				}
			}

			// Articles
			if ($this->params->def('articles_enable', false))
			{
				$query = $db->getQuery(true)
					->select(array('id', 'alias', 'catid', 'language'))
					->from($db->quoteName('#__content'))
					->where('state = 1');

				// Filter by access
				if (!empty($access))
				{
					$query->where('access IN (' . implode(',', $access) . ')');
				}

				$db->setQuery($query);
				$rows = $db->loadObjectList();

				$changefreq = $this->params->def('articles_changefreq', 'weekly');
				$priority   = $this->params->def('articles_priority', '0.5');

				// Add articles to urls array
				foreach ($rows as $row)
				{
					// Prepare loc attribute
					$slug = ($row->alias) ? ($row->id . ':' . $row->alias) : $row->id;
					$loc  = 'index.php?option=com_content&view=article&id=' . $slug . '&catid=' . $row->catid;
					if (!empty($row->language) && $row->language !== '*' && $multilanguage)
					{
						$loc .= '&lang=' . $row->language;
					}

					// Prepare ulr object
					$url             = new stdClass();
					$url->loc        = $loc;
					$url->changefreq = $changefreq;
					$url->priority   = $priority;

					// Add url to array
					$urls[] = $url;
				}
			}

			$this->_urls = $urls;
		}

		return $this->_urls;
	}
}