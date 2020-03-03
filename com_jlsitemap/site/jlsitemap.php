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

JLoader::register('JLSitemapHelperRoute', JPATH_SITE . '/components/com_jlsitemap/helpers/route.php');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

$controller = BaseController::getInstance('JLSitemap');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();