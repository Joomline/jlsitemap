<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Plugin\System\Jlsitemap_cron\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\SiteRouterAwareTrait;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class Jlsitemap_cron extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use SiteRouterAwareTrait;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var  boolean
     *
     * @since  0.0.2
     */
    protected $autoloadLanguage = true;

    /**
     * Cron last run date.
     *
     * @var  string
     *
     * @since  1.10.1
     */
    protected $_lastRun = null;

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
            'onBeforeRender'       => 'onBeforeRender',
            'onAjaxJlsitemap_cron' => 'onAjaxJlsitemap_cron',
        ];
    }

    /**
     * Method to add cron js.
     *
     * @throws  \Exception
     *
     * @since  0.0.2
     */
    public function onBeforeRender()
    {
        if ($this->params && $this->params->get('client_enable') && $this->checkCacheTime()) {
            $app  = $this->getApplication();
            $mode = $this->params->get('client_mode', 'all');
            if ($mode == 'all' || ($mode == 'admin' && $app->isClient(
                        'administrator'
                    )) || ($mode == 'site' && $app->isClient('site'))) {
                // Set params
//                $site   = SiteApplication::getInstance('site');
//                $router = $site->getRouter();
                $router = $this->getSiteRouter();
                $link   = 'index.php?option=com_ajax&plugin=jlsitemap_cron&group=system&format=json';
                $link   = str_replace('administrator/', '', $router->build($link)->toString());
                $link   = str_replace('/?', '?', $link);
                $link   = trim(Uri::root(true), '/') . '/' . trim($link, '/');

                $params = ['ajax_url' => $link];
                $app->getDocument()->addScriptOptions('jlsitemap_cron', $params);

                // Add script
                $app->getDocument()
                    ->getWebAssetManager()
                    ->registerAndUseScript(
                        'plg_system_jlsitemap_cron.cron.js',
                        'media/plg_system_jlsitemap_cron/js/cron.min.js',
                        ['version' => 'auto']
                    );
            }
        }
    }

    /**
     * Method to check client cache time
     *
     * @return  bool True if  run. False if don't  run
     *
     * @since  0.0.2
     */
    protected function checkCacheTime()
    {
        if ($this->_lastRun === null) {
            $db             = $this->getDatabase();
            $query          = $db->getQuery('true')
                ->select('params')
                ->from('#__extensions')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('jlsitemap_cron'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
            $params         = new Registry($db->setQuery($query)->loadResult());
            $this->_lastRun = $params->get('last_run', false);
        }

        if (!$this->_lastRun) {
            return true;
        }

        // Prepare cache time
        $offset = ' + ' . $this->params->get('client_cache_number', 1) . ' ' .
            $this->params->get('client_cache_value', 'day');
        $cache  = new Date($this->_lastRun . $offset);

        return (Factory::getDate()->toUnix() >= $cache->toUnix());
    }

    /**
     * Method to run cron
     *
     * @return  bool True on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  0.0.2
     */
    public function onAjaxJlsitemap_cron()
    {
        $app       = $this->getApplication();
        $generate  = false;
        $error     = '';

        $clientRun = $this->params->get('client_enable');


        // Client checks
        if ($clientRun) {
            if ($this->checkCacheTime()) {
                $generate = true;
            } else {
                $error = Text::_('PLG_SYSTEM_JLSITEMAP_GENERATION_ERROR_CACHE');
            }
        }

        // Server checks
        if (!$clientRun) {
            if (!$this->params->get('key_enabled')) {
                $generate = true;
            } elseif (!$generate = ($app->getInput()->get('key', '') == $this->params->get('key'))) {
                $error = Text::_('PLG_SYSTEM_JLSITEMAP_GENERATION_ERROR_KEY');
            }
        }

        // Run generation
        if (!$error && $generate && $urls = $this->generate()) {
            $success = Text::sprintf(
                'PLG_SYSTEM_JLSITEMAP_GENERATION_SUCCESS',
                count($urls->includes),
                count($urls->excludes),
                count($urls->all)
            );

            //  Prepare json response
            if ($app->getInput()->get('format', 'raw') == 'json') {
                $success = explode('<br>', $success);
            }

            return $success;
        } elseif ($error) {
            throw new \Exception(Text::sprintf('PLG_SYSTEM_JLSITEMAP_GENERATION_FAILURE', $error));
        }

        return false;
    }

    /**
     * Method to generate site map.
     *
     * @return  object|false Sitemap generation result on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  0.0.2
     */
    protected function generate()
    {
        try {
            // Update last run
            $this->params->set('last_run', Factory::getDate()->toSql());
            $plugin          = new \stdClass();
            $plugin->type    = 'plugin';
            $plugin->element = 'jlsitemap_cron';
            $plugin->folder  = 'system';
            $plugin->params  = (string)$this->params;
            $this->getDatabase()->updateObject('#__extensions', $plugin, ['type', 'element', 'folder']);

            // Run generation
            /** @var \Joomla\Component\JLSitemap\Site\Model\SitemapModel $model */
            $model = $this->getApplication()
                ->bootComponent('com_jlsitemap')
                ->getMVCFactory()
                ->createModel('Sitemap', 'Site', ['ignore_request' => true]);

            if (!$result = $model->generate()) {
                throw new \Exception(Text::sprintf('PLG_SYSTEM_JLSITEMAP_GENERATION_FAILURE', $model->getError()));
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception(Text::sprintf('PLG_SYSTEM_JLSITEMAP_GENERATION_FAILURE', $e->getMessage()));
        }
    }
}
