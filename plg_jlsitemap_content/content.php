<?php
/**
 * @package    JLSitemap - Content Plugin
 * @version    0.0.2
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

class plgJLSitemapContent extends CMSPlugin
{

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var boolean
	 *
	 * @since 0.0.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to get urls array
	 *
	 * @param array    $urls   Urls array
	 * @param Registry $config Component config
	 *
	 * @return array Urls array with attributes
	 *
	 * @since 0.0.1
	 */
	public function onGetUrls(&$urls, $config)
	{
		// Categories
		if ($this->params->get('categories_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('id', 'published', 'access', 'metadata', 'language'))
				->from($db->quoteName('#__categories'))
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
				->where('published IN (0, 1)')
				->order($db->escape('lft') . ' ' . $db->escape('asc'));
			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$changefreq = $this->params->get('categories_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('categories_priority', $config->get('priority', '0.5'));

			// Add categories to urls array
			foreach ($rows as $row)
			{
				// Prepare loc attribute
				$loc = 'index.php?option=com_content&view=category&id=' . $row->id;
				if (!empty($row->language) && $row->language !== '*' && $config->get('multilanguage'))
				{
					$loc .= '&lang=' . $row->language;
				}

				// Prepare exclude attribute
				$metadata = new Registry($row->metadata);
				$exclude  = false;
				if (preg_match('/noindex/', $metadata->get('robots', $config->get('siteRobots'))))
				{
					$exclude = 'noindex';
				}
				if ($row->published != 1)
				{
					$exclude = 'published';
				}
				if (!in_array($row->access, $config->get('guestAccess', array())))
				{
					$exclude = 'access';
				}

				// Prepare ulr object
				$url             = new stdClass();
				$url->loc        = $loc;
				$url->changefreq = $changefreq;
				$url->priority   = $priority;
				$url->exclude    = ($exclude) ? Text::_('PLG_JLSITEMAP_CONTENT_EXCLUDE_CATEGORY_' . strtoupper($exclude)) : $exclude;

				// Add url to array
				$urls[] = $url;
			}
		}

		// Articles
		if ($this->params->get('articles_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('a.id', 'a.alias', 'a.state', 'a.publish_up', 'a.publish_down', 'a.access', 'a.metadata', 'a.language'))
				->from($db->quoteName('#__content', 'a'))
				->select(array('c.id as category_id', 'c.published as  category_published', 'c.access as category_access'))
				->join('LEFT', '#__categories AS c ON c.id = a.catid')
				->where('a.state IN (0, 1)')
				->where('c.published IN (0, 1)')
				->group('a.id')
				->order($db->escape('a.ordering') . ' ' . $db->escape('asc'));

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$nullDate   = $db->getNullDate();
			$nowDate    = Factory::getDate()->toUnix();
			$changefreq = $this->params->get('articles_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('articles_priority', $config->get('priority', '0.5'));

			// Add articles to urls array
			foreach ($rows as $row)
			{
				// Prepare loc attribute
				$slug = ($row->alias) ? ($row->id . ':' . $row->alias) : $row->id;
				$loc  = 'index.php?option=com_content&view=article&id=' . $slug . '&catid=' . $row->category_id;
				if (!empty($row->language) && $row->language !== '*' && $config->get('multilanguage'))
				{
					$loc .= '&lang=' . $row->language;
				}

				// Prepare exclude attribute
				$metadata = new Registry($row->metadata);

				$exclude = false;
				if (preg_match('/noindex/', $metadata->get('robots', $config->get('siteRobots'))))
				{
					$exclude = 'article_noindex';
				}
				if ($row->state != 1)
				{
					$exclude = 'article_state';
				}
				if ($row->publish_up == $nullDate || Factory::getDate($row->publish_up)->toUnix() > $nowDate)
				{
					$exclude = 'article_publish_up';
				}
				if ($row->publish_down != $nullDate && Factory::getDate($row->publish_down)->toUnix() < $nowDate)
				{
					$exclude = 'article_publish_down';
				}
				if (!in_array($row->access, $config->get('guestAccess', array())))
				{
					$exclude = 'article_access';
				}
				if ($row->category_published != 1)
				{
					$exclude = 'category_published';
				}
				if (!in_array($row->category_access, $config->get('guestAccess', array())))
				{
					$exclude = 'category_access';
				}

				// Prepare ulr object
				$url             = new stdClass();
				$url->loc        = $loc;
				$url->changefreq = $changefreq;
				$url->priority   = $priority;
				$url->exclude    = ($exclude) ? Text::_('PLG_JLSITEMAP_CONTENT_EXCLUDE_' . strtoupper($exclude)) : $exclude;

				// Add url to array
				$urls[] = $url;
			}
		}

		return $urls;
	}
}