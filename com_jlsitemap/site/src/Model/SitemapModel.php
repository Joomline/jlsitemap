<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Component\JLSitemap\Site\Model;

use Joomla\Component\JLSitemap\Administrator\Service\SitemapGeneratorAdapterInterface;
use Joomla\Component\JLSitemap\Administrator\Service\SitemapRuntimeContextFactory;
use Joomla\Component\JLSitemap\Administrator\Service\SitemapServiceFactory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

\defined('_JEXEC') or die;

class SitemapModel extends BaseDatabaseModel implements SitemapGeneratorAdapterInterface
{
    /**
     * JLSitemap component configuration.
     *
     * @var  ?Registry
     *
     * @since  1.9.0
     */
    protected ?Registry $_configuration = null;

    /**
     * Object with urls array.
     *
     * @var  ?object
     *
     * @since  1.1.0
     */
    protected ?object $_urls = null;

    /**
     * Menu items array.
     *
     * @var  ?array
     *
     * @since  1.1.0
     */
    protected ?array $_menuItems = null;

    /**
     * Sitemap stylesheet.
     *
     * @var  ?array
     *
     * @since  1.10.0
     */
    protected ?array $_xsl = [];

    /**
     * Runtime context normalized by the shared generation service.
     *
     * @var  ?array
     *
     * @since  2.1.1
     */
    protected ?array $_runtimeContext = null;

    /**
     * Constructor.
     *
     * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
     *
     * @throws  \Exception
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct($config = [])
    {
        // Import plugins
        PluginHelper::importPlugin('jlsitemap');

        parent::__construct($config);
    }

    /**
     * Method to generate sitemap.
     *
     * @param   bool  $debug  Debug generation.
     *
     * @return  object|false Array if successful, false otherwise and internal error is set.
     *
     * @throws  \Exception
     *
     * @since  1.1.0
     */
    public function generate($debug = false)
    {
        return SitemapServiceFactory::getGenerationService()->generate($this, (bool) $debug);
    }

    /**
     * Method to get component configuration.
     *
     * @return  Registry JLSitemap component configuration.
     *
     * @throws  \Exception
     *
     * @since  1.9.0
     */
    public function getConfiguration()
    {
        if ($this->_configuration === null) {
            $configuration = ComponentHelper::getParams('com_jlsitemap');

            Factory::getApplication()->triggerEvent('onGetConfiguration', [&$configuration]);

            $this->_configuration = $configuration;
        }

        return $this->_configuration;
    }

    /**
     * Method to get sitemap urls array
     *
     * @return  object
     *
     * @throws  \Exception
     *
     * @since  1.1.0
     */
    public function getUrls()
    {
        if ($this->_urls === null) {
            $config           = $this->getConfiguration();
            $runtimeContext   = $this->getRuntimeContext();
            $siteSef          = $runtimeContext['siteSef'];
            $siteName         = $runtimeContext['siteName'];
            $siteRobots       = $runtimeContext['siteRobots'];
            $siteRoot         = $runtimeContext['siteRoot'];
            $guestAccess      = $runtimeContext['guestAccess'];
            $multilanguage    = $runtimeContext['multilanguage'];
            $defaultLanguage  = $runtimeContext['defaultLanguage'];
            $changefreqValues = [
                'always'  => 1,
                'hourly'  => 2,
                'daily'   => 3,
                'weekly'  => 4,
                'monthly' => 5,
                'yearly'  => 6,
                'never'   => 7
            ];

            // Prepare menus filter
            $filterMenus = ($config->get('filter_menu')) ? $config->get('filter_menu_menus', []) : false;

            // Prepare menu items filter;
            $filterMenuItems = (is_array($filterMenus)) ? [] : false;

            // Prepare menu home filter
            $filterMenuHomes = [];

            // Prepare raw filter
            $filterRaw = ($config->get('filter_raw_index') || $config->get('filter_raw_component')
                || $config->get('filter_raw_get')) ? [] : false;
            if ($config->get('filter_raw_index')) {
                $filterRaw[] = 'index.php';
            }
            if ($config->get('filter_raw_component')) {
                $filterRaw[] = 'component/';
            }
            if ($config->get('filter_raw_get')) {
                $filterRaw[] = '?';
            }

            // Prepare strpos filter
            $filterStrpos = !empty($config->get('filter_strpos')) ? $config->get('filter_strpos') : false;
            if ($filterStrpos && !empty(trim($filterStrpos))) {
                $filterStrpos = preg_split('/\r\n|\r|\n/', $filterStrpos);
                $filterStrpos = array_filter(array_map('trim', $filterStrpos), function ($string) {
                    return !empty($string);
                });

                if (empty($filterStrpos)) {
                    $filterStrpos = false;
                }
            }

            // Create urls arrays
            $all      = [];
            $includes = [];
            $excludes = [];
            $menus    = [];

            // Add home page
            $type            = [Text::_('COM_JLSITEMAP_TYPES_MENU')];
            $title           = $siteName;
            $link            = ($siteSef) ? '/' : '/index.php';
            $level           = count(explode('/', $link)) - 1;
            $key             = (empty($link)) ? '/' : $link;
            $loc             = $siteRoot . $link;
            $changefreq      = $config->get('changefreq', 'weekly');
            $changefreqValue = $changefreqValues[$changefreq];
            $priority        = $config->get('priority', '0.5');
            $exclude         = false;

            $url = new Registry();
            $url->set('type', $type);
            $url->set('title', $title);
            $url->set('link', $link);
            $url->set('level', ($level > 0) ? $level : 1);
            $url->set('loc', $loc);
            $url->set('changefreq', $changefreq);
            $url->set('changefreqValue', $changefreqValue);
            $url->set('priority', $priority);
            $url->set('exclude', $exclude);

            $all[$key]        = $url;
            $includes[$key]    = $url;
            $filterMenuHomes[] = $key;

            // Add menu items to urls arrays
            foreach ($this->getMenuItems($multilanguage, $filterMenus, $siteRobots, $guestAccess) as $item) {
                $type            = [$item->type];
                $link            = ($siteSef) ? Route::link('site', $item->loc) : $item->loc;
                $level           = count(explode('/', $link)) - 1;
                $key             = (empty($link)) ? '/' : $link;
                $loc             = $siteRoot . $link;
                $changefreq      = $config->get('changefreq', 'weekly');
                $changefreqValue = $changefreqValues[$changefreq];
                $priority        = $config->get('priority', '0.5');

                // Prepare exclude
                $exclude = [];
                if (!$item->home) {
                    $exclude = ($item->exclude) ? $item->exclude : [];
                    $exclude = array_merge($exclude, $this->filtering($link, $filterRaw, $filterStrpos));

                    foreach ($exclude as &$value) {
                        $value = new Registry($value);
                    }
                }

                // Prepare alternates
                $alternates = [];
                if (is_array($item->alternates)) {
                    foreach ($item->alternates as $lang => $href) {
                        $href = ($siteSef) ? Route::link('site', $href) : $href;
                        if (empty($href)) {
                            $href = '/';
                        }
                        if (!isset($excludes[$href]) && empty($this->filtering($href, $filterRaw, $filterStrpos))) {
                            $alternates[$lang] = rtrim(Uri::root(), '/') . $href;
                        }
                    }

                    if (!empty($alternates) && !isset($alternates['x-default']) && isset($alternates[$defaultLanguage])) {
                        $alternates['x-default'] = $alternates[$defaultLanguage];
                    }
                }

                // Create url Registry
                $url = new Registry();
                $url->set('type', $type);
                $url->set('title', $item->title);
                $url->set('link', $link);
                $url->set('level', ($level > 0) ? $level : 1);
                $url->set('loc', $loc);
                $url->set('changefreq', $changefreq);
                $url->set('changefreqValue', $changefreqValue);
                $url->set('priority', $priority);
                $url->set('exclude', (!empty($exclude)) ? $exclude : false);
                $url->set('alternates', (!empty($alternates)) ? $alternates : false);

                // Add url to arrays
                $all[$key] = $url;
                if (!empty($exclude)) {
                    $excludes[$key] = $url;
                } else {
                    $includes[$key] = $url;
                }
                $menus[$key] = $url;

                if ($item->home) {
                    $filterMenuHomes[] = $key;
                } elseif (is_array($filterMenuItems) && empty($exclude)) {
                    $filterMenuItems[] = $key;
                }
            }

            // Prepare config
            $config->set('siteName', $siteName);
            $config->set('siteSef', $siteSef);
            $config->set('siteRobots', $siteRobots);
            $config->set('siteRoot', $siteRoot);
            $config->set('guestAccess', $guestAccess);
            $config->set('multilanguage', $multilanguage);
            $config->set('defaultLanguage', $defaultLanguage);
            $config->set('changefreqValues', $changefreqValues);
            $config->set('filterMenus', $filterMenus);
            $config->set('filterMenuItems', $filterMenuItems);
            $config->set('filterMenuHomes', $filterMenuHomes);
            $config->set('filterRaw', $filterRaw);
            $config->set('filterStrpos', $filterStrpos);

            // Add urls from jlsitemap plugins
            $rows = [];
            Factory::getApplication()->triggerEvent('onGetUrls', [&$rows, &$config]);
            foreach ($rows as $row) {
                $item = new Registry($row);
                if (!$loc = $item->get('loc', false)) {
                    continue;
                }
                $type       = [$item->get('type', Text::_('COM_JLSITEMAP_TYPES_UNKNOWN'))];
                $title      = $item->get('title');
                $link       = ($siteSef && !$item->get('noRoute', false)) ? Route::link(
                    'site',
                    $item->get('loc')
                ) : $item->get('loc');
                $level      = count(explode('/', $link)) - 1;
                $key        = (empty($link)) ? '/' : $link;
                $loc        = $siteRoot . $link;
                $changefreq = $item->get('changefreq', $config->get('changefreq', 'weekly'));
                if (empty($changefreq)) {
                    $changefreq = $config->get('changefreq', 'weekly');
                }
                $changefreqValue = $changefreqValues[$changefreq];
                $priority        = $item->get('priority', $config->get('priority', '0.5'));
                if (empty($priority)) {
                    $priority = $config->get('priority', '0.5');
                }
                $lastmod = ($item->get('lastmod', false)
                    && $item->get('lastmod') != Factory::getContainer()->get(DatabaseInterface::class)->getNullDate()) ?
                    Factory::getDate($item->get('lastmod'))->toUnix() : false;

                // Prepare title
                if (!empty($menus[$key])) {
                    $title = $menus[$key]->get('title');
                }

                // Prepare exclude
                $exclude = [];
                if (!in_array($key, $filterMenuHomes)) {
                    // Legacy old plugins
                    if (is_string($item->get('exclude'))) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_UNKNOWN'),
                            'msg'  => $item->get('exclude', '')
                        ];
                    } elseif ($item->get('exclude')) {
                        $exclude = $item->get('exclude');
                    }

                    $exclude = array_merge(
                        $exclude,
                        $this->filtering($link, $filterRaw, $filterStrpos, $filterMenuItems)
                    );

                    foreach ($exclude as &$value) {
                        $value = new Registry($value);
                    }
                }

                // Prepare alternates
                $alternates = [];
                if ($item->get('alternates', false)) {
                    foreach ($item->get('alternates') as $lang => $href) {
                        $href = ($siteSef && !$item->get('noRoute', false)) ? Route::link('site', $href) : $href;
                        if (empty($href)) {
                            $href = '/';
                        }
                        if (!isset($excludes[$href]) && empty($this->filtering($href, $filterRaw, $filterStrpos))) {
                            $alternates[$lang] = rtrim(Uri::root(), '/') . $href;
                        }
                    }

                    if (!empty($alternates) && !isset($alternates['x-default']) && isset($alternates[$defaultLanguage])) {
                        $alternates['x-default'] = $alternates[$defaultLanguage];
                    }
                }

                // Prepare images
                $images = [];
                if ($item->get('images', false)) {
                    foreach ($item->get('images') as $href) {
                        $href = $this->normalizeImageUrl($href);
                        if ($href !== '') {
                            $images[] = $href;
                        }
                    }
                }

                // Exist url override
                if (isset($all[$key])) {
                    $exist = $all[$key];
                    $type  = array_merge($exist->get('type'), $type);

                    if (empty($title) && !empty($exist->get('title'))) {
                        $title = $exist->get('title');
                    }

                    if ($exist->get('changefreqValue') < $changefreqValue) {
                        $changefreq      = $exist->get('changefreq');
                        $changefreqValue = $exist->get('changefreqValue');
                    }

                    if ((float)$priority < (float)$exist->get('priority')) {
                        $priority = $exist->get('priority');
                    }

                    if ($exist->get('lastmod', false)) {
                        if (!$lastmod) {
                            $lastmod = $exist->get('lastmod');
                        } elseif ($exist->get('lastmod') > $lastmod) {
                            $lastmod = $exist->get('lastmod');
                        }
                    }

                    if ($exist->get('exclude')) {
                        $exclude = array_merge($exist->get('exclude'), $exclude);
                    }

                    if (is_array($exist->get('alternates'))) {
                        $alternates = $alternates + $exist->get('alternates');
                    }
                }

                // Create url Registry
                $url = new Registry();
                $url->set('type', $type);
                $url->set('title', $title);
                $url->set('link', $link);
                $url->set('level', ($level > 0) ? $level : 1);
                $url->set('loc', $loc);
                $url->set('changefreq', $changefreq);
                $url->set('changefreqValue', $changefreqValue);
                $url->set('priority', $priority);
                $url->set('exclude', (!empty($exclude)) ? $exclude : false);
                $url->set('lastmod', $lastmod);
                $url->set('alternates', (!empty($alternates)) ? $alternates : false);
                $url->set('images', (!empty($images)) ? $images : false);

                // Add url to arrays
                $all[$key] = $url;
                if (!empty($exclude)) {
                    $excludes[$key] = $url;

                    // Exclude item if already in array (last item has priority)
                    unset($includes[$key]);
                } else {
                    $includes[$key] = $url;
                }
            }

            // Sort urls arrays
            ksort($all);
            ksort($includes);
            ksort($excludes);

            // Prepare urls object
            $urls           = new \stdClass();
            $urls->includes = $includes;
            $urls->excludes = $excludes;
            $urls->all      = $all;

            // Set urls object
            $this->_urls = $urls;
        }

        return $this->_urls;
    }

    public function setRuntimeContext(array $runtimeContext): void
    {
        $this->_runtimeContext = $runtimeContext;
    }

    public function getRuntimeContext(): array
    {
        if ($this->_runtimeContext === null) {
            $this->_runtimeContext = (new SitemapRuntimeContextFactory())->create();
        }

        return $this->_runtimeContext;
    }

    /**
     * Method to get menu items array
     *
     * @param   bool         $multilanguage  Enable multilanguage
     * @param   array|false  $menutypes      Menutypes filter
     * @param   string       $siteRobots     Site config robots
     * @param   array        $guestAccess    Guest access levels
     *
     * @return  array
     *
     * @since  1.1.0
     */
    protected function getMenuItems(
        $multilanguage = false,
        $menutypes = false,
        $siteRobots = null,
        $guestAccess = []
    ) {
        if ($this->_menuItems === null) {
            // Get menu items
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select([
                    'm.id',
                    'm.menutype',
                    'm.title',
                    'm.link',
                    'm.type',
                    'm.published',
                    'm.access',
                    'm.home',
                    'm.params',
                    'm.language',
                    'e.extension_id as component_exist',
                    'e.enabled as component_enabled',
                    'e.element as component'
                ])
                ->from($db->quoteName('#__menu', 'm'))
                ->join('LEFT', '#__extensions AS e ON e.extension_id = m.component_id')
                ->where('m.client_id = 0')
                ->where('m.id > 1')
                ->order($db->escape('m.lft') . ' ' . $db->escape('asc'));

            // Join over associations
            if ($multilanguage) {
                $query->select('assoc.key as association')
                    ->join(
                        'LEFT',
                        '#__associations AS assoc ON assoc.id = m.id AND assoc.context = ' .
                        $db->quote('com_menus.item')
                    );
            }

            $db->setQuery($query);
            $rows = $db->loadObjectList('id');

            // Create menu items array
            $items         = [];
            $alternates    = [];
            $excludeTypes  = ['alias', 'separator', 'heading', 'url'];
            $excludeStates = [
                0  => Text::_('COM_JLSITEMAP_EXCLUDE_MENU_UNPUBLISH'),
                -2 => Text::_('COM_JLSITEMAP_EXCLUDE_MENU_TRASH')
            ];
            foreach ($rows as $row) {
                $params    = new Registry($row->params);
                $home      = ($row->home && !isset($excludeStates[$row->published]));
                $component = $row->component;
                if ($row->type == 'component' && empty($row->component)) {
                    preg_match("/^index.php\?option=([a-zA-Z\-0-9_]*)/", $row->link, $matches);
                    $component = (!empty($matches[1])) ? $matches[1] : 'unknown';
                }

                // Prepare title attribute
                $title = ($params->get('page_title', false)) ? $params->get('page_title') : $row->title;

                // Prepare loc attribute
                $loc = 'index.php?Itemid=' . $row->id;
                if (!empty($row->language) && $row->language !== '*' && $multilanguage) {
                    $loc .= '&lang=' . $row->language;
                }

                // Prepare exclude attribute
                $exclude = [];
                if (!$home) {
                    if ($menutypes && !empty($menutypes) && !in_array($row->menutype, $menutypes)) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_MENU'),
                            'msg'  => Text::_('COM_JLSITEMAP_EXCLUDE_MENU_MENUTYPES')
                        ];
                    }
                    $robots = trim((string) $params->get('robots', ''));
                    if ($robots === '') {
                        $robots = (string) $siteRobots;
                    }

                    if ($robots !== '' && \preg_match('/\bnoindex\b/i', $robots)) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_MENU'),
                            'msg'  => Text::_('COM_JLSITEMAP_EXCLUDE_MENU_ROBOTS')
                        ];
                    }

                    if (isset($excludeStates[$row->published])) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_MENU'),
                            'msg'  => $excludeStates[$row->published]
                        ];
                    }

                    if (in_array($row->type, $excludeTypes)) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_MENU'),
                            'msg'  => Text::sprintf('COM_JLSITEMAP_EXCLUDE_MENU_SYSTEM_TYPE', $row->type)
                        ];
                    }

                    if ($row->type == 'component' && empty($row->component_exist)) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_MENU'),
                            'msg'  => Text::sprintf(
                                'COM_JLSITEMAP_EXCLUDE_MENU_COMPONENT_EXIST',
                                $component
                            )
                        ];
                    } elseif ($row->type == 'component' && empty($row->component_enabled)) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_MENU'),
                            'msg'  => Text::sprintf(
                                'COM_JLSITEMAP_EXCLUDE_MENU_COMPONENT_ENABLED',
                                $component
                            )
                        ];
                    }

                    if (!in_array($row->access, $guestAccess)) {
                        $exclude[] = [
                            'type' => Text::_('COM_JLSITEMAP_EXCLUDE_MENU'),
                            'msg'  => Text::_('COM_JLSITEMAP_EXCLUDE_MENU_ACCESS')
                        ];
                    }
                }

                // Prepare menu item object
                $item             = new \stdClass();
                $item->loc        = $loc;
                $item->type       = Text::_('COM_JLSITEMAP_TYPES_MENU');
                $item->title      = $title;
                $item->home       = $home;
                $item->exclude    = (!empty($exclude)) ? $exclude : false;
                $item->alternates = ($multilanguage && !empty($row->association)) ? $row->association : false;

                // Add menu item to array
                $items[] = $item;

                // Add menu items to alternates array
                if ($multilanguage && !empty($row->association) && empty($exclude)) {
                    if (!isset($alternates[$row->association])) {
                        $alternates[$row->association] = [];
                    }

                    $alternates[$row->association][$row->language] = $loc;
                };
            }

            // Add alternates to menu items
            if (!empty($alternates)) {
                foreach ($items as &$item) {
                    $item->alternates = ($item->alternates && !empty($alternates[$item->alternates])) ?
                        $alternates[$item->alternates] : false;
                }
            }

            $this->_menuItems = $items;
        }

        return $this->_menuItems;
    }

    /**
     * Method to filtering urls
     *
     * @param   string      $link    Url
     * @param   array|bool  $raw     Raw filter
     * @param   array|bool  $strpos  Strpos filter
     * @param   array|bool  $menu    Menu items filter
     *
     * @return  array Excludes array
     *
     * @since  1.3.0
     */
    public function filtering($link = null, $raw = false, $strpos = false, $menu = false)
    {
        $exclude = [];

        // Check empty url
        if (empty($link)) {
            $exclude['filter_null'] = [
                'type' => Text::_('COM_JLSITEMAP_EXCLUDE_FILTER'),
                'msg'  => Text::_('COM_JLSITEMAP_EXCLUDE_FILTER_NULL')
            ];

            return $exclude;
        }

        // Filter by raw
        if ($raw && is_array($raw)) {
            foreach ($raw as $filter) {
                if (mb_stripos($link, $filter, 0, 'UTF-8') !== false) {
                    $exclude['filter_raw_' . $filter] = [
                        'type' => Text::_('COM_JLSITEMAP_EXCLUDE_FILTER'),
                        'msg'  => Text::sprintf('COM_JLSITEMAP_EXCLUDE_FILTER_RAW', $filter)
                    ];
                    break;
                }
            }
        }

        // Filter by strpos
        if ($strpos && is_array($strpos)) {
            foreach ($strpos as $filter) {
                if (mb_stripos($link, $filter, 0, 'UTF-8') !== false) {
                    $exclude['filter_strpos_' . $filter] = [
                        'type' => Text::_('COM_JLSITEMAP_EXCLUDE_FILTER'),
                        'msg'  => Text::sprintf('COM_JLSITEMAP_EXCLUDE_FILTER_STRPOS', $filter)
                    ];
                    break;
                }
            }
        }

        // Filter by menu
        if (is_array($menu)) {
            $excludeMenu = true;
            foreach ($menu as $filter) {
                $filter   = str_replace(['.html'], '', $filter);
                $filter   = str_replace(['/'], '\/', $filter);
                $patterns = [
                    '/^' . $filter . '\//',
                    '/^' . $filter . '$/',
                    '/^' . $filter . '\.html/',
                ];
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $link)) {
                        $excludeMenu = false;
                        break;
                    }
                }
                if (!$excludeMenu) {
                    break;
                }
            }

            if ($excludeMenu) {
                $exclude['filter_menu'] = [
                    'type' => Text::_('COM_JLSITEMAP_EXCLUDE_FILTER'),
                    'msg'  => Text::_('COM_JLSITEMAP_EXCLUDE_FILTER_MENU')
                ];
            }
        }

        return $exclude;
    }

    /**
     * Method to generate single sitemap.
     *
     * @param   array  $rows  Include urls array
     *
     * @return  string|false Sitemap file path on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  1.7
     */
    public function generateSingleXML($rows = []): false|string
    {
        $xml      = $this->filterRegexp($this->getXML($rows));
        $filename = $this->getConfiguration()->get('filename', 'sitemap');
        $file     = Path::clean(JPATH_ROOT . '/' . $filename . '.xml');

        return $this->writeAtomicFile($file, $xml);
    }

    /**
     * Method to filtering sitemap string via regexp
     *
     * @param   string  $string  Sitemap string
     *
     * @return  string Sitemap string.
     *
     * @throws  \Exception
     *
     * @since  1.7
     */
    protected function filterRegexp($string = ''): string
    {
        if (empty($string)) {
            return $string;
        }

        // Regexp filter
        $filterRegexp = $this->getConfiguration()->get('filter_regexp');
        if (!empty($filterRegexp)) {
            foreach (ArrayHelper::fromObject($filterRegexp) as $regexp) {
                if (!empty($regexp['pattern'])) {
                    $string = preg_replace($regexp['pattern'], $regexp['replacement'], $string);
                }
            }
        }

        return $string;
    }

    /**
     * Method to get sitemap xml string
     *
     * @param   array  $rows  Include urls array
     *
     * @return  string Sitemap XML sting on success, \Exception on failure.
     *
     * @throws  \Exception
     *
     * @since  1.1.0
     */
    public function getXML($rows = [])
    {
        $rows       = (empty($rows)) ? $this->getUrls()->includes : $rows;
        $date       = (new Date('now'))->toSql();
        $comment    = '<!-- JLSitemap ' . $date . ' -->';
        $xsl        = $this->generateXSL('urlset');
        $stylesheet = ($xsl) ? '<?xml-stylesheet type="text/xsl" href="' . Uri::root() . $xsl . '"?>' : '';

        // Create sitemap
        $sitemap = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . $comment . $stylesheet
            . '<urlset'
            . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.w3.org/1999/xhtml http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd"'
            . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
            . ' xhtml="http://www.w3.org/1999/xhtml"'
            . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'
            . '/>'
        );

        // Add urls
        foreach ($rows as $row) {
            if ($loc = $row->get('loc', false)) {
                $url = $sitemap->addChild('url');

                // Loc
                $this->addTextChild($url, 'loc', $loc);

                // Changefreq
                if ($changefreq = $row->get('changefreq', false)) {
                    $this->addTextChild($url, 'changefreq', $changefreq);
                }

                // Priority
                if ($priority = $row->get('priority', false)) {
                    $this->addTextChild($url, 'priority', $row->get('priority'));
                }

                // Lastmod
                if ($lastmod = $row->get('lastmod', false)) {
                    $this->addTextChild($url, 'lastmod', Factory::getDate($lastmod)->toISO8601());
                }

                // Alternates
                if ($alternates = $row->get('alternates', false)) {
                    // Add x-default
                    if (!isset($alternates['x-default']) && isset(
                            $alternates[Factory::getApplication()->getLanguage()->getDefault()]
                        )) {
                        $alternates['x-default'] = $alternates[Factory::getApplication()->getLanguage()->getDefault()];
                    }

                    foreach ($alternates as $lang => $href) {
                        $alternate = $url->addChild('xhtml:link', '', 'http://www.w3.org/1999/xhtml');
                        $alternate->addAttribute('rel', 'alternate');
                        $alternate->addAttribute('hreflang', $lang);
                        $alternate->addAttribute('href', $href);
                    }
                }

                // Images
                if ($images = $row->get('images', false)) {
                    foreach ($images as $href) {
                        $href = $this->normalizeImageUrl($href);
                        if ($href === '') {
                            continue;
                        }

                        $image = $url->addChild('image', '', 'http://www.google.com/schemas/sitemap-image/1.1');
                        $this->addTextChild($image, 'loc', $href, 'http://www.google.com/schemas/sitemap-image/1.1');
                    }
                }
            }
        }

        return $sitemap->asXML();
    }

    /**
     * Normalize sitemap image URL and reject empty root URLs.
     *
     * @param   mixed  $href  Raw image URL.
     *
     * @return  string Normalized image URL or empty string when it must be skipped.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function normalizeImageUrl(mixed $href): string
    {
        $href = trim((string) $href);

        if ($href === '') {
            return '';
        }

        $siteRoot     = rtrim(Uri::root(), '/');
        $relativeRoot = trim(Uri::root(true), '/');

        if (rtrim($href, '/') === $siteRoot || $href === '/') {
            return '';
        }

        if ($relativeRoot !== '' && trim($href, '/') === $relativeRoot) {
            return '';
        }

        return $href;
    }

    /**
     * Add a text child with XML-safe content.
     *
     * @param   \SimpleXMLElement  $element    Parent element.
     * @param   string             $name       Child element name.
     * @param   mixed              $value      Raw text value.
     * @param   ?string            $namespace  Optional namespace.
     *
     * @return  \SimpleXMLElement
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function addTextChild(
        \SimpleXMLElement $element,
        string $name,
        mixed $value,
        ?string $namespace = null
    ): \SimpleXMLElement {
        $value = htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8', false);

        return $element->addChild($name, $value, $namespace);
    }

    /**
     * Method to generate sitemap xsl style files.
     *
     * @param   string  $type  Layout type.
     *
     * @return  string|false Style src on success, false on failure.
     *
     * @throws  \Exception
     *
     * @since  1.10.0
     */
    public function generateXSL($type = null)
    {
        if (empty($type)) {
            return false;
        }

        if (!isset($this->_xsl[$type])) {
            $config = $this->getConfiguration();
            $src    = false;

            // Generate file
            if ($config->get('xsl', 1)) {
                Factory::getApplication()->getLanguage()->load(
                    'com_jlsitemap',
                    JPATH_SITE,
                    ComponentHelper::getParams('com_languages')->get('site', 'en-GB'),
                    true
                );
                $xsl = '<?xml version="1.0" encoding="UTF-8"?>'
                    . PHP_EOL . LayoutHelper::render(
                        'components.jlsitemap.xsl.' . $type,
                        ['date' => (new Date('now'))->toSql()]
                    );

                $filename = $config->get('filename', 'sitemap');
                $file     = $filename . '_' . $type . '.xsl';
                $path     = Path::clean(JPATH_ROOT . '/' . $file);

                $src = (File::write(file:$path, buffer:$xsl, useStreams: false)) ? $file : false;
            }

            $this->_xsl[$type] = $src;
        }

        return $this->_xsl[$type];
    }

    /**
     * Method to delete sitemap
     *
     * @return  bool True on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  1.4.1
     */
    public function delete()
    {
        // Delete single sitemap
        $filename = $this->getConfiguration()->get('filename', 'sitemap');
        $file     = Path::clean(JPATH_ROOT . '/' . $filename . '.xml');
        if (\is_file($file) && !File::delete($file)) {
            return false;
        }

        // Delete multi sitemap
        $files = Folder::files(JPATH_ROOT, $filename . '_[0-9]*\.xml');
        foreach ($files as $file) {
            $path = Path::clean(JPATH_ROOT . '/' . $file);
            if (!File::delete($path)) {
                return false;
            }
        }

        // Delete xsl stylesheets
        $files = [$filename . '_sitemapindex.xsl', $filename . '_urlset.xsl'];
        foreach ($files as $file) {
            $path = Path::clean(JPATH_ROOT . '/' . $file);
            if (\is_file($path) && !File::delete($path)) {
                return false;
            }
        }

        // Delete json sitemap
        $file = Path::clean(JPATH_ROOT . '/' . $filename . '.json');
        if (\is_file($file) && !File::delete($file)) {
            return false;
        }

        return true;
    }

    /**
     * Method to generate multi sitemap.
     *
     * @param   array  $rows      Include urls array
     * @param   int    $xmlLimit  Limit in xml file
     *
     * @return  string[]|false Sitemap XML sting on success, \Exception on failure.
     *
     * @throws  \Exception
     *
     * @since  1.7
     */
    public function generateMultiXML($rows = [], $xmlLimit = 50000)
    {
        $filename = $this->getConfiguration()->get('filename', 'sitemap');
        $oldFiles = Folder::files(JPATH_ROOT, $filename . '_[0-9]*\.xml', false, true);

        // Generate files
        $result      = [];
        $tempFiles   = [];
        $splitFiles  = [];
        $i           = 0;
        $t           = 0;
        $f           = 0;
        $total       = count($rows);
        $includes    = [];
        foreach ($rows as $row) {
            $includes[] = $row;
            $i++;
            $t++;

            if ($i === $xmlLimit || $t === $total) {
                $f++;

                $xml  = $this->filterRegexp($this->getXML($includes));
                $file = Path::clean(JPATH_ROOT . '/' . $filename . '_' . $f . '.xml');

                if (!$temp = $this->writeTemporaryFile($file, $xml)) {
                    $this->deleteTemporaryFiles($tempFiles);

                    return false;
                }

                $tempFiles[]  = ['temp' => $temp, 'file' => $file];
                $splitFiles[] = $file;
                $result[]     = $file;

                // Reset
                $i        = 0;
                $includes = [];
            }
        }

        // Main sitemap
        $date       = new Date('now');
        $xsl        = $this->generateXSL('sitemapindex');
        $stylesheet = ($xsl) ? '<?xml-stylesheet type="text/xsl" href="' . Uri::root() . $xsl . '"?>' : '';
        $comment    = '<!-- JLSitemap ' . $date->toSql() . ' -->';
        $sitemap    = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . $comment . $stylesheet
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />'
        );
        for ($i = 1; $i <= $f; $i++) {
            $child = $sitemap->addChild('sitemap');
            $child->addChild('loc', Uri::root() . $filename . '_' . $i . '.xml');
            $child->addChild('lastmod', $date->toISO8601());
        }
        $xml = $sitemap->asXML();

        // Filter regexp
        $xml = $this->filterRegexp($xml);

        // Put to file
        $file = Path::clean(JPATH_ROOT . '/' . $filename . '.xml');

        if (!$temp = $this->writeTemporaryFile($file, $xml)) {
            $this->deleteTemporaryFiles($tempFiles);

            return false;
        }

        $tempFiles[] = ['temp' => $temp, 'file' => $file];
        $result[]    = $file;

        if (!$this->promoteTemporaryFiles($tempFiles)) {
            return false;
        }

        $this->deleteStaleGeneratedFiles($oldFiles, $splitFiles);

        return $result;
    }

    /**
     * Method to create json sitemap.
     *
     * @param   array  $rows  Include urls array
     *
     * @return  string|false Sitemap file path on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    public function generateJSON($rows = [])
    {
        // Get json
        foreach ($rows as &$row) {
            $row = $row->toObject();
        }
        $registry = new Registry($rows);
        $json     = $registry->toString('json', ['bitmask' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES]);

        // Filter regexp
        $json = $this->filterRegexp($json);

        // Create sitemap json file
        $filename = $this->getConfiguration()->get('filename', 'sitemap');
        $file     = Path::clean(JPATH_ROOT . '/' . $filename . '.json');

        return $this->writeAtomicFile($file, $json);
    }

    /**
     * Write a generated file through a temporary file and promote it after the write succeeds.
     *
     * @param   string  $file        Final file path.
     * @param   string  $buffer      File contents.
     * @param   bool    $useStreams  True to use streams.
     *
     * @return  string|false Final file path on success, false on failure.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function writeAtomicFile(string $file, string $buffer, bool $useStreams = false): false|string
    {
        if (!$temp = $this->writeTemporaryFile($file, $buffer, $useStreams)) {
            return false;
        }

        return $this->promoteTemporaryFiles([['temp' => $temp, 'file' => $file]]) ? $file : false;
    }

    /**
     * Write generated content to a temporary file in the same directory as the final file.
     *
     * @param   string  $file        Final file path.
     * @param   string  $buffer      File contents.
     * @param   bool    $useStreams  True to use streams.
     *
     * @return  string|false Temporary file path on success, false on failure.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function writeTemporaryFile(string $file, string $buffer, bool $useStreams = false): false|string
    {
        $temp = $this->getTemporaryFilePath($file, 'tmp');

        return File::write(file: $temp, buffer: $buffer, useStreams: $useStreams) ? $temp : false;
    }

    /**
     * Promote temporary files to final paths. Existing final files are backed up and restored on failure.
     *
     * @param   array  $files  List of arrays with `temp` and `file` paths.
     *
     * @return  bool True on success.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function promoteTemporaryFiles(array $files): bool
    {
        $backups  = [];
        $promoted = [];

        try {
            foreach ($files as $entry) {
                $file = $entry['file'];

                if (!\is_file($file)) {
                    continue;
                }

                $backup = $this->getTemporaryFilePath($file, 'bak');

                if (!$this->moveFile($file, $backup)) {
                    throw new \RuntimeException('Could not backup sitemap file.');
                }

                $backups[$file] = $backup;
            }

            foreach ($files as $entry) {
                if (!$this->moveFile($entry['temp'], $entry['file'])) {
                    throw new \RuntimeException('Could not promote sitemap file.');
                }

                $promoted[] = $entry['file'];
            }
        } catch (\Throwable) {
            foreach ($promoted as $file) {
                $this->deleteFile($file);
            }

            foreach ($backups as $file => $backup) {
                if (\is_file($backup)) {
                    $this->moveFile($backup, $file);
                }
            }

            $this->deleteTemporaryFiles($files);

            return false;
        }

        foreach ($backups as $backup) {
            $this->deleteFile($backup);
        }

        return true;
    }

    /**
     * Delete old generated split sitemap files that are not part of the new sitemap set.
     *
     * @param   array  $oldFiles  Old split files.
     * @param   array  $newFiles  New split files.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function deleteStaleGeneratedFiles(array $oldFiles, array $newFiles): void
    {
        foreach ($oldFiles as $file) {
            $file = Path::clean($file);

            if (!\in_array($file, $newFiles, true)) {
                $this->deleteFile($file);
            }
        }
    }

    /**
     * Delete temporary generated files.
     *
     * @param   array  $files  List of arrays with `temp` paths.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function deleteTemporaryFiles(array $files): void
    {
        foreach ($files as $entry) {
            if (!empty($entry['temp'])) {
                $this->deleteFile($entry['temp']);
            }
        }
    }

    /**
     * Build a temporary file path next to a generated file.
     *
     * @param   string  $file    Final file path.
     * @param   string  $suffix  Temporary suffix.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getTemporaryFilePath(string $file, string $suffix): string
    {
        return Path::clean(\dirname($file) . '/' . \basename($file) . '.' . $suffix . '.' . \uniqid('', true));
    }

    /**
     * Move a file and normalize Joomla filesystem exceptions to a boolean result.
     *
     * @param   string  $source       Source file.
     * @param   string  $destination  Destination file.
     *
     * @return  bool True on success.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function moveFile(string $source, string $destination): bool
    {
        try {
            return File::move($source, $destination) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Delete a file and normalize Joomla filesystem exceptions to a boolean result.
     *
     * @param   string  $file  File path.
     *
     * @return  bool True on success.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function deleteFile(string $file): bool
    {
        if (!\is_file($file)) {
            return true;
        }

        try {
            return File::delete($file) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Method to set component configuration parameter.
     *
     * @param   string  $path       Registry Path (e.g. joomla.content.showauthor)
     * @param   mixed   $value      Value of entry
     * @param   string  $separator  The key separator
     *
     * @throws  \Exception
     *
     * @since  1.9.0
     */
    public function setConfigurationParameter($path, $value, $separator = null)
    {
        if ($this->_configuration === null) {
            $this->getConfiguration();
        }

        $this->_configuration->set($path, $value, $separator);
    }
}
