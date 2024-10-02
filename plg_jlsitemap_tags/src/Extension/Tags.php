<?php
/**
 * @package    JLSitemap - Tags Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Plugin\JLSitemap\Tags\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Tags\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

final class Tags extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var  boolean
     *
     * @since  1.3.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGetUrls' => 'onGetUrls',
        ];
    }

    /**
     * Method to get urls array
     *
     * @param   Event  $event
     *
     *
     * @since 0.0.1
     */
    public function onGetUrls(Event $event): void
    {

        if(!ComponentHelper::isEnabled('com_tags'))
        {
            return;
        }

        /**
         * @param   array     $urls    Urls array
         * @param   Registry  $config  Component config
         */
        [$urls, $config] = $event->getArguments();

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                't.id',
                't.title',
                't.alias',
                't.published',
                't.access',
                't.metadata',
                't.created_time',
                't.modified_time',
                'tm.tag_date',
            ])
            ->from($db->quoteName('#__tags', 't'))
            ->join('LEFT', '#__contentitem_tag_map AS tm ON tm.tag_id = t.id')
            ->where('t.id > 1')
            ->group('t.id')
            ->order($db->escape('t.lft') . ' ' . $db->escape('asc'));

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        $nullDate      = $db->getNullDate();
        $excludeStates = [
            0  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_UNPUBLISH'),
            -2 => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_TRASH'),
            2  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_ARCHIVE'),
        ];
        $changefreq    = $this->params->get('changefreq', $config->get('changefreq', 'weekly'));
        $priority      = $this->params->get('priority', $config->get('priority', '0.5'));

        foreach ($rows as $row) {
            // Prepare exclude attribute
            $metadata = new Registry($row->metadata);
            $exclude  = [];
	        $robots = $metadata->get('robots', $config->get('siteRobots'));
	        if (!empty($robots) && preg_match('/noindex/', $robots)) {
                $exclude[] = [
                    'type' => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE'),
                    'msg'  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_ROBOTS'),
                ];
            }

            if (isset($excludeStates[$row->published])) {
                $exclude[] = [
                    'type' => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE'),
                    'msg'  => $excludeStates[$row->published],
                ];
            }

            if (!in_array($row->access, $config->get('guestAccess', []))) {
                $exclude[] = [
                    'type' => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE'),
                    'msg'  => Text::_('PLG_JLSITEMAP_TAGS_EXCLUDE_ACCESS'),
                ];
            }

            // Prepare lastmod attribute
            $lastmod = (!empty($row->created_time) && $row->created_time != $nullDate) ?
                Factory::getDate($row->created_time)->toUnix() : false;

            if ((!empty($row->modified_time) && $row->modified_time != $nullDate) &&
                (!$lastmod || Factory::getDate($row->modified_time)->toUnix() > $lastmod)) {
                $lastmod = Factory::getDate($row->modified_time)->toUnix();
            }

            if ((!empty($row->tag_date) && $row->tag_date != $nullDate) &&
                (!$lastmod || Factory::getDate($row->tag_date)->toUnix() > $lastmod)) {
                $lastmod = Factory::getDate($row->tag_date)->toUnix();
            }

            $lastmod = Factory::getDate($lastmod)->toSql();

            // Prepare tag object
            $tag             = new \stdClass();
            $tag->id         = $row->id;
            $tag->type       = Text::_('PLG_JLSITEMAP_TAGS_TYPE');
            $tag->title      = $row->title;
            $tag->loc        = RouteHelper::getTagRoute($row->id . ':' . $row->alias);
            $tag->changefreq = $changefreq;
            $tag->priority   = $priority;
            $tag->lastmod    = $lastmod;
            $tag->exclude    = (!empty($exclude)) ? $exclude : false;

            // Add tag to array
            $urls[] = $tag;
        }

        $event->setArgument(0, $urls);
    }
}
