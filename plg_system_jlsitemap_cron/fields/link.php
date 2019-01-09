<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;

class JFormFieldLink extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var string
	 *
	 * @since 0.0.2
	 */
	protected $type = 'link';

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var string
	 *
	 * @since 0.0.2
	 */
	protected $layout = 'plugins.system.jlsitemap_cron.fields.link';

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 *
	 * @since 0.0.2
	 */
	protected function getLayoutData()
	{
		$site   = SiteApplication::getInstance('site');
		$router = $site->getRouter();
		$link   = 'index.php?option=com_ajax&plugin=jlsitemap_cron&group=system&format=raw';
		$link   = str_replace('administrator/', '', $router->build($link)->toString());
		$link   = str_replace('/?', '?', $link);
		$link   = trim(Uri::root(), '/') . '/' . trim($link, '/');

		$data         = parent::getLayoutData();
		$data['link'] = $link;

		return $data;
	}
}