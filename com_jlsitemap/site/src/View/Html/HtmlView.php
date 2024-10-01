<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Component\JLSitemap\Site\View\Html;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\JLSitemap\Site\Helper\RouteHelper;

class HtmlView extends BaseHtmlView
{
    /**
     * Application params.
     *
     * @var  \Joomla\Registry\Registry;
     *
     * @since  1.6.0
     */
    public $params;
    /**
     * Page class suffix from params.
     *
     * @var  string
     *
     * @since  1.6.0
     */
    public $pageclass_sfx;
    /**
     * Model state variables.
     *
     * @var  Joomla\CMS\Object\CMSObject
     *
     * @since  1.6.0
     */
    protected $state;
    /**
     * Links array.
     *
     * @var  array
     *
     * @since  1.6.0
     */
    protected $items;
    /**
     * Pagination object.
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     *
     * @since  1.6.0
     */
    protected $pagination;
    /**
     * Active menu item.
     *
     * @var  \Joomla\CMS\Menu\MenuItem
     *
     * @since  1.6.0
     */
    protected $menu;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    public function display($tpl = null)
    {
        $this->state      = $this->get('State');
        $this->params     = $this->state->get('params');
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->menu       = Factory::getApplication()->getMenu()->getActive();

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode('\n', $errors), 500);
        }

        // Set layout
        $menu = $this->menu;
        if ($menu && $menu->query['option'] == 'com_jlsitemap' && $menu->query['view'] == 'html'
            && isset($menu->query['layout'])) {
            $this->setLayout($menu->query['layout']);
        }

        // Escape strings for html output
        $pageclass_sfx       = $this->params->get('pageclass_sfx');
        $this->pageclass_sfx = (!empty($pageclass_sfx) ? \htmlspecialchars($pageclass_sfx) : '');

        // Prepare the document
        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepare the document.
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    protected function _prepareDocument()
    {
        $app     = Factory::getApplication();
        $root    = Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $menu    = $this->menu;
        $current = ($menu
            && $menu->query['option'] === 'com_jlsitemap'
            && $menu->query['view'] === 'html');
        $title   = ($current) ? $menu->title : Text::_('COM_JLSITEMAP_SITEMAP');

        // Add  pathway item if no current menu
        if ($menu && !$current) {
            $app->getPathway()->addItem($title, '');
        }

        // Set meta title
        $title    = (!$current || empty($this->params->get('page_title'))) ? $title : $this->params->get('page_title');
        $sitename = $app->get('sitename');
        if ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $sitename, $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $sitename);
        }
        $this->getDocument()->setTitle($title);

        // Set meta description
        if ($current && $this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        // Set meta keywords
        if ($current && $this->params->get('menu-meta_keywords')) {
            $this->getDocument()->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        // Set meta robots
        if ($this->params->get('robots')) {
            $this->getDocument()->setMetadata('robots', $this->params->get('robots'));
        }

        // Set meta url
        $url = $root . Route::_(RouteHelper::getHTMLRoute());
        $this->getDocument()->setMetaData('url', $url);

        // Set meta twitter
        $this->getDocument()->setMetaData('twitter:card', 'summary_large_image');
        $this->getDocument()->setMetaData('twitter:site', $sitename);
        $this->getDocument()->setMetaData('twitter:creator', $sitename);
        $this->getDocument()->setMetaData('twitter:title', $title);
        $this->getDocument()->setMetaData('twitter:url', $url);
        if ($description = $this->getDocument()->getMetaData('description')) {
            $this->getDocument()->setMetaData('twitter:description', $description);
        }
        if ($image = $this->getDocument()->getMetaData('image')) {
            $this->getDocument()->setMetaData('twitter:image', $image);
        }

        // Set meta open graph
        $this->getDocument()->setMetadata('og:type', 'website', 'property');
        $this->getDocument()->setMetaData('og:site_name', $sitename, 'property');
        $this->getDocument()->setMetaData('og:title', $title, 'property');
        $this->getDocument()->setMetaData('og:url', $url, 'property');
        if ($description) {
            $this->getDocument()->setMetaData('og:description', $description, 'property');
        }
        if ($image) {
            $this->getDocument()->setMetaData('og:image', $image, 'property');
        }
    }
}
