<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;

class JLSitemapRouter extends RouterView
{
	/**
	 * Router constructor.
	 *
	 * @param  \Joomla\CMS\Application\CMSApplication $app  The application object.
	 * @param  \Joomla\CMS\Menu\AbstractMenu          $menu The menu object to work with.
	 *
	 * @since  1.6.0
	 */
	public function __construct($app = null, $menu = null)
	{
		// Registration route
		$html = new RouterViewConfiguration('html');
		$html->setKey('key');
		$this->registerView($html);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/**
	 * Method to get the segment(s) for html.
	 *
	 * @param  string $id    ID of the item to retrieve the segments.
	 * @param  array  $query The request that is built right now.
	 *
	 * @return  array|string  The segments of this item.
	 *
	 * @since  1.6.0
	 */
	public function getHTMLSegment($id, $query)
	{
		return array(1 => 'html');
	}

	/**
	 * Method to get the id for html.
	 *
	 * @param  string $segment Segment to retrieve the ID.
	 * @param  array  $query   The request that is parsed right now.
	 *
	 * @return  integer|false  The id of this item or false.
	 *
	 * @since  1.6.0
	 */
	public function getHTMLId($segment, $query)
	{
		return (@$query['view'] == 'html') ? 1 : false;
	}
}

/**
 * JLSitemap router functions.
 *
 * @param  array &$query An array of URL arguments.
 *
 * @throws  Exception
 *
 * @return  array  The URL arguments to use to assemble the subsequent URL.
 *
 * @since  1.6.0
 */
function JLSitemapBuildRoute(&$query)
{
	$app    = Factory::getApplication();
	$router = new JLSitemapRouter($app, $app->getMenu());

	return $router->build($query);
}

/**
 * Parse the segments of a URL.
 *
 * @param  array $segments The segments of the URL to parse.
 *
 * @throws  Exception
 *
 * @return  array  The URL attributes to be used by the application.
 *
 * @since  1.6.0
 */
function JLSitemapParseRoute($segments)
{
	$app    = Factory::getApplication();
	$router = new JLSitemapRouter($app, $app->getMenu());

	return $router->parse($segments);
}