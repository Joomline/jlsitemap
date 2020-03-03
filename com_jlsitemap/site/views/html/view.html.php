<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2020 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class JLSitemapViewHTML extends HtmlView
{
	/**
	 * Model state variables.
	 *
	 * @var  Joomla\CMS\Object\CMSObject
	 *
	 * @since  1.6.0
	 */
	protected $state;

	/**
	 * Application params.
	 *
	 * @var  \Joomla\Registry\Registry;
	 *
	 * @since  1.6.0
	 */
	public $params;

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
	 * Page class suffix from params.
	 *
	 * @var  string
	 *
	 * @since  1.6.0
	 */
	public $pageclass_sfx;

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse.
	 *
	 * @throws  Exception
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
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
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode('\n', $errors), 500);
		}

		// Set layout
		$menu = $this->menu;
		if ($menu && $menu->query['option'] == 'com_jlsitemap' && $menu->query['view'] == 'html'
			&& isset($menu->query['layout']))
		{
			$this->setLayout($menu->query['layout']);
		}

		// Escape strings for html output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));

		// Prepare the document
		$this->_prepareDocument();

		return parent::display($tpl);
	}

	/**
	 * Prepare the document.
	 *
	 * @throws  Exception
	 *
	 * @since  1.6.0
	 */
	protected function _prepareDocument()
	{
		$app     = Factory::getApplication();
		$root    = Uri::getInstance()->toString(array('scheme', 'host', 'port'));
		$menu    = $this->menu;
		$current = ($menu
			&& $menu->query['option'] === 'com_jlsitemap'
			&& $menu->query['view'] === 'html');
		$title   = ($current) ? $menu->title : Text::_('COM_JLSITEMAP_SITEMAP');

		// Add  pathway item if no current menu
		if ($menu && !$current)
		{
			$app->getPathway()->addItem($title, '');
		}

		// Set meta title
		$title    = (!$current || empty($this->params->get('page_title'))) ? $title : $this->params->get('page_title');
		$sitename = $app->get('sitename');
		if ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $sitename, $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $sitename);
		}
		$this->document->setTitle($title);

		// Set meta description
		if ($current && $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		// Set meta keywords
		if ($current && $this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		// Set meta robots
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}

		// Set meta url
		$url = $root . Route::_(JLSitemapHelperRoute::getHTMLRoute());
		$this->document->setMetaData('url', $url);

		// Set meta twitter
		$this->document->setMetaData('twitter:card', 'summary_large_image');
		$this->document->setMetaData('twitter:site', $sitename);
		$this->document->setMetaData('twitter:creator', $sitename);
		$this->document->setMetaData('twitter:title', $title);
		$this->document->setMetaData('twitter:url', $url);
		if ($description = $this->document->getMetaData('description'))
		{
			$this->document->setMetaData('twitter:description', $description);
		}
		if ($image = $this->document->getMetaData('image'))
		{
			$this->document->setMetaData('twitter:image', $image);
		}

		// Set meta open graph
		$this->document->setMetadata('og:type', 'website', 'property');
		$this->document->setMetaData('og:site_name', $sitename, 'property');
		$this->document->setMetaData('og:title', $title, 'property');
		$this->document->setMetaData('og:url', $url, 'property');
		if ($description)
		{
			$this->document->setMetaData('og:description', $description, 'property');
		}
		if ($image)
		{
			$this->document->setMetaData('og:image', $image, 'property');
		}
	}
}