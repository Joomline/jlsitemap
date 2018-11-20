<?php
/**
 * @package    JLSitemap - Tags Plugin
 * @version    @version@
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

class plgJLSitemapTags extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var boolean
	 *
	 * @since 1.2.1
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
	 * @since 1.2.1
	 */
	public function onGetUrls(&$urls, $config)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('t.id', 't.title', 't.alias', 't.published', 't.access', 't.metadata', 't.created_time',
				't.modified_time', 'tm.tag_date'))
			->from($db->quoteName('#__tags', 't'))
			->join('LEFT', '#__contentitem_tag_map AS tm ON tm.tag_id = t.id')
			->where('t.id > 1')
			->group('t.id')
			->order($db->escape('t.lft') . ' ' . $db->escape('asc'));

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$nullDate      = $db->getNullDate();
		$excludeStates = array(
			0  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_UNPUBLISH'),
			-2 => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_TRASH'),
			2  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_ARCHIVE'));
		$changefreq    = $this->params->get('changefreq', $config->get('changefreq', 'weekly'));
		$priority      = $this->params->get('priority', $config->get('priority', '0.5'));

		JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');

		foreach ($rows as $row)
		{
			// Prepare exclude attribute
			$metadata = new Registry($row->metadata);
			$exclude  = array();
			if (preg_match('/noindex/', $metadata->get('robots', $config->get('siteRobots'))))
			{
				$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE'),
				                   'msg'  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_ROBOTS'));
			}

			if (isset($excludeStates[$row->published]))
			{
				$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE'),
				                   'msg'  => $excludeStates[$row->published]);
			}

			if (!in_array($row->access, $config->get('guestAccess', array())))
			{
				$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE'),
				                   'msg'  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_ACCESS'));
			}

			// Prepare lastmod attribute


			$lastmod = (!empty($row->created_time) && $row->created_time != $nullDate) ?
				Factory::getDate($row->created_time)->toUnix() : false;

			if ((!empty($row->modified_time) && $row->modified_time != $nullDate) &&
				(!$lastmod || Factory::getDate($row->modified_time)->toUnix() > $lastmod))
			{
				$lastmod = Factory::getDate($row->modified_time)->toUnix();
			}


			if ((!empty($row->tag_date) && $row->tag_date != $nullDate) &&
				(!$lastmod || Factory::getDate($row->tag_date)->toUnix() > $lastmod))
			{
				$lastmod = Factory::getDate($row->tag_date)->toUnix();
			}

			$lastmod = Factory::getDate($lastmod)->toSql();

			// Prepare tag object
			$tag             = new stdClass();
			$tag->id         = $row->id;
			$tag->type       = Text::_('PLG_JLSITEMAP_TAGS_TYPE');
			$tag->title      = $row->title;
			$tag->loc        = TagsHelperRoute::getTagRoute($row->id . ':' . $row->alias);
			$tag->changefreq = $changefreq;
			$tag->priority   = $priority;
			$tag->lastmod    = $lastmod;
			$tag->exclude    = (!empty($exclude)) ? $exclude : false;

			// Add tag to array
			$urls[] = $tag;
		}

		return $urls;
	}
}