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

use Joomla\CMS\Helper\RouteHelper;

class JLSitemapHelperRoute extends RouteHelper
{
	/**
	 * Fetches html route.
	 *
	 * @return  string  HTML view link.
	 *
	 * @since  1.6.0
	 */
	public static function getHTMLRoute()
	{
		return 'index.php?option=com_jlsitemap&view=html&key=1';
	}
}