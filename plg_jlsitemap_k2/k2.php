<?php
/**
 * @package    JLSitemap - K2 Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

class plgJLSitemapK2 extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var  boolean
	 *
	 * @since  1.3.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to get urls array
	 *
	 * @param   array     $urls    Urls array
	 * @param   Registry  $config  Component config
	 *
	 * @return  array Urls array with attributes
	 *
	 * @since  1.3.0
	 */
	public function onGetUrls(&$urls, $config)
	{
		// Load K2HelperRoute
		if ($this->params->get('items_enable', false) ||
			$this->params->get('categories_enable', false) ||
			$this->params->get('tags_enable', false) ||
			$this->params->get('users_enable', false))
		{
			JLoader::register('K2HelperRoute', JPATH_SITE . '/components/com_k2/helpers/route.php');
		}

		// Items
		if ($this->params->get('items_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('i.id', 'i.title', 'i.alias', 'i.published', 'i.created', 'i.modified',
					'i.publish_up', 'i.publish_down', 'i.trash', 'i.access', 'i.params', 'i.metadata',
					'c.id as category_id', 'c.alias as category_alias', 'c.published as category_published',
					'c.access as category_access', 'c.trash as category_trash'))
				->from($db->quoteName('#__k2_items', 'i'))
				->join('LEFT', '#__k2_categories AS c ON c.id = i.catid')
				->group('i.id')
				->order($db->escape('i.ordering') . ' ' . $db->escape('asc'));

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$nullDate   = $db->getNullDate();
			$nowDate    = Factory::getDate()->toUnix();
			$changefreq = $this->params->get('items_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('items_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare loc attribute
				$loc = K2HelperRoute::getItemRoute($row->id . ':' . urlencode($row->alias),
					$row->category_id . ':' . urlencode($row->category_alias));

				// Prepare exclude attribute
				$exclude = array();
				if (preg_match('/noindex/', $row->metadata . ' ' . $config->get('siteRobots', '')))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM_ROBOTS'));
				}

				if ($row->published == 0)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM_UNPUBLISH'));
				}

				if ($row->trash)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM_TRASH'));
				}

				if ($row->publish_up == $nullDate || Factory::getDate($row->publish_up)->toUnix() > $nowDate)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM_PUBLISH_UP'));
				}

				if ($row->publish_down != $nullDate && Factory::getDate($row->publish_down)->toUnix() < $nowDate)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM_PUBLISH_DOWN'));
				}

				if (!in_array($row->access, $config->get('guestAccess', array())))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_ITEM_ACCESS'));
				}

				if ($row->category_published == 0)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY_UNPUBLISH'));
				}

				if ($row->category_trash)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY_TRASH'));
				}

				if (!in_array($row->category_access, $config->get('guestAccess', array())))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY_ACCESS'));
				}

				// Prepare lastmod attribute
				$lastmod = (!empty($row->modified) && $row->modified != $nullDate &&
					Factory::getDate($row->modified)->toUnix() > Factory::getDate($row->created)->toUnix()) ?
					$row->modified : $row->created;

				// Prepare item object
				$item             = new stdClass();
				$item->type       = Text::_('PLG_JLSITEMAP_K2_TYPES_ITEM');
				$item->title      = $row->title;
				$item->loc        = $loc;
				$item->changefreq = $changefreq;
				$item->priority   = $priority;
				$item->lastmod    = $lastmod;
				$item->exclude    = (!empty($exclude)) ? $exclude : false;

				// Add item to array
				$urls[] = $item;
			}
		}

		// Categories
		if ($this->params->get('categories_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('c.id', 'c.name', 'c.alias', 'c.published', 'c.access', 'c.params', 'c.trash',
					'MAX(i.created) as created', 'MAX(i.modified) as modified'))
				->from($db->quoteName('#__k2_categories', 'c'))
				->join('LEFT', '#__k2_items AS i ON i.catid = c.id')
				->group('c.id')
				->order(array(
					$db->escape('c.parent') . ' ' . $db->escape('asc'),
					$db->escape('c.ordering') . ' ' . $db->escape('asc'),
				));

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$changefreq = $this->params->get('categories_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('categories_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare loc attribute
				$loc = K2HelperRoute::getCategoryRoute($row->id . ':' . urlencode($row->alias));

				// Prepare exclude attribute
				$exclude = array();
				$params  = new Registry($row->params);
				if (preg_match('/noindex/', $params->get('catMetaRobots', $config->get('siteRobots'))))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY_ROBOTS'));
				}

				if ($row->published == 0)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY_UNPUBLISH'));
				}

				if ($row->trash)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY_TRASH'));
				}

				if (!in_array($row->access, $config->get('guestAccess', array())))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_CATEGORY_ACCESS'));
				}

				// Prepare lastmod attribute
				$lastmod = (Factory::getDate($row->modified)->toUnix() > Factory::getDate($row->created)->toUnix()) ?
					$row->modified : $row->created;

				// Prepare category object
				$category             = new stdClass();
				$category->type       = Text::_('PLG_JLSITEMAP_K2_TYPES_CATEGORY');
				$category->title      = $row->name;
				$category->loc        = $loc;
				$category->changefreq = $changefreq;
				$category->priority   = $priority;
				$category->lastmod    = $lastmod;
				$category->exclude    = (!empty($exclude)) ? $exclude : false;

				// Add category to array
				$urls[] = $category;
			}
		}

		// Tags
		if ($this->params->get('tags_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('t.id', 't.name', 't.published', 'MAX(i.created) as created', 'MAX(i.modified) as modified'))
				->from($db->quoteName('#__k2_tags', 't'))
				->join('LEFT', '#__k2_tags_xref AS xref ON xref.tagID = t.id')
				->join('LEFT', '#__k2_items AS i ON i.id = xref.itemID')
				->group('t.id')
				->order($db->escape('t.name') . ' ' . $db->escape('asc'));

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$nullDate   = $db->getNullDate();
			$changefreq = $this->params->get('tags_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('tags_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare loc attribute
				$loc = K2HelperRoute::getTagRoute($row->name);

				// Prepare exclude attribute
				$exclude = array();
				if ($row->published == 0)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_TAG'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_TAG_UNPUBLISH'));
				}

				// Prepare lastmod attribute
				$lastmod = (!empty($row->modified) && $row->modified != $nullDate &&
					Factory::getDate($row->modified)->toUnix() > Factory::getDate($row->created)->toUnix()) ?
					$row->modified : $row->created;

				// Prepare tag object
				$tag             = new stdClass();
				$tag->type       = Text::_('PLG_JLSITEMAP_K2_TYPES_TAG');
				$tag->title      = $row->name;
				$tag->loc        = $loc;
				$tag->changefreq = $changefreq;
				$tag->priority   = $priority;
				$tag->lastmod    = $lastmod;
				$tag->exclude    = (!empty($exclude)) ? $exclude : false;

				// Add tag to array
				$urls[] = $tag;
			}
		}

		// Users
		if ($this->params->get('users_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('u.id', 'u.name', 'u.block', 'MAX(i.created) as created', 'MAX(i.modified) as modified'))
				->from($db->quoteName('#__users', 'u'))
				->join('LEFT', '#__k2_items AS i ON i.created_by = u.id')
				->group('u.id')
				->order($db->escape('u.name') . ' ' . $db->escape('asc'));

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$nullDate   = $db->getNullDate();
			$changefreq = $this->params->get('users_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('users_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare loc attribute
				$loc = K2HelperRoute::getUserRoute($row->id);

				// Prepare exclude attribute
				$exclude = array();
				if ($row->block)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_USER'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_K2_EXCLUDE_USER_BLOCK'));
				}

				// Prepare lastmod attribute
				$lastmod = (!empty($row->modified) && $row->modified != $nullDate &&
					Factory::getDate($row->modified)->toUnix() > Factory::getDate($row->created)->toUnix()) ?
					$row->modified : $row->created;

				// Prepare user object
				$user             = new stdClass();
				$user->type       = Text::_('PLG_JLSITEMAP_K2_TYPES_USER');
				$user->title      = $row->name;
				$user->loc        = $loc;
				$user->changefreq = $changefreq;
				$user->priority   = $priority;
				$user->lastmod    = $lastmod;
				$user->exclude    = (!empty($exclude)) ? $exclude : false;

				// Add user to array
				$urls[] = $user;
			}
		}

		return $urls;
	}
}