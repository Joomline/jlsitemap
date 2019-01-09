<?php
/**
 * @package    JLSitemap - Kunena Plugin
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

class plgJLSitemapKunena extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var boolean
	 *
	 * @since 1.4.0
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
	 * @since 1.4.0
	 */
	public function onGetUrls(&$urls, $config)
	{
		// Topics
		if ($this->params->get('topics_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('t.id', 't.subject', 't.last_post_time'))
				->from($db->quoteName('#__kunena_topics', 't'))
				->group('t.id')
				->order($db->escape('t.last_post_time') . ' ' . $db->escape('asc'));

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$changefreq = $this->params->get('topics_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('topics_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				$guest  = KunenaUserHelper::get(0);
				$object = KunenaForumTopicHelper::get($row->id);

				// Prepare exclude attribute
				$exclude = array();
				if (!$object)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_TOPIC'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_TOPIC_EXIST'));
				}

				if ($object->getCategory()->tryAuthorise('read', $guest, false))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_CATEGORY_READ'));
				}

				// Prepare loc attribute
				$loc = ($object) ? $object->getUri()->toString() : '';

				// Prepare topic object
				$topic             = new stdClass();
				$topic->type       = Text::_('PLG_JLSITEMAP_KUNENA_TYPES_TOPIC');
				$topic->title      = $row->subject;
				$topic->loc        = $loc;
				$topic->changefreq = $changefreq;
				$topic->priority   = $priority;
				$topic->lastmod    = $row->last_post_time;
				$topic->exclude    = (!empty($exclude)) ? $exclude : false;

				// Add topic to array
				$urls[] = $topic;
			}
		}

		// Categories
		if ($this->params->get('categories_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('c.id', 'c.name', 'c.last_post_time'))
				->from($db->quoteName('#__kunena_categories', 'c'))
				->group('c.id')
				->order($db->escape('c.ordering') . ' ' . $db->escape('asc'));

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$changefreq = $this->params->get('categories_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('categories_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				$guest  = KunenaUserHelper::get(0);
				$object = KunenaForumCategoryHelper::get($row->id);

				// Prepare exclude attribute
				$exclude = array();
				if (!$object)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_TOPIC_EXIST'));
				}

				if ($object->tryAuthorise('read', $guest, false))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_CATEGORY_READ'));
				}

				// Prepare loc attribute
				$loc = ($object) ? KunenaRoute::getCategoryUrl($object) : '';

				// Prepare category object
				$category             = new stdClass();
				$category->type       = Text::_('PLG_JLSITEMAP_KUNENA_TYPES_CATEGORY');
				$category->title      = $row->name;
				$category->loc        = $loc;
				$category->changefreq = $changefreq;
				$category->priority   = $priority;
				$category->lastmod    = (!empty($row->last_post_time)) ? $row->last_post_time : false;
				$category->exclude    = (!empty($exclude)) ? $exclude : false;

				// Add category to array
				$urls[] = $category;
			}
		}

		// Users
		if ($this->params->get('users_enable', false))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('u.userid as id'))
				->from($db->quoteName('#__kunena_users', 'u'))
				->group('u.userid');

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$changefreq = $this->params->get('users_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('users_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				$guest  = KunenaUserHelper::get(0);
				$object = KunenaUserHelper::get($row->id);

				// Prepare exclude attribute
				$exclude = array();
				if (!$object)
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_USER'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_TOPIC_EXIST'));
				}

				if ($object->tryAuthorise('read', $guest, false))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_USER'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_KUNENA_EXCLUDE_USER_READ'));
				}

				// Prepare loc attribute
				$loc = ($object) ? KunenaRoute::getUserUrl($object) : '';

				// Prepare user object
				$user             = new stdClass();
				$user->type       = Text::_('PLG_JLSITEMAP_KUNENA_TYPES_USER');
				$user->title      = $object->getName();
				$user->loc        = $loc;
				$user->changefreq = $changefreq;
				$user->priority   = $priority;
				$user->lastmod    = (!empty($row->last_post_time)) ? $row->last_post_time : false;
				$user->exclude    = (!empty($exclude)) ? $exclude : false;

				// Add user to array
				$urls[] = $user;
			}
		}

		return $urls;
	}
}