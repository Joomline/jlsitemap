<?php
/**
 * @package    JLSitemap Component
 * @version    1.0.0
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::register('JLSitemapHelper', __DIR__ . '/helpers/jlsitemap.php');

if (!Factory::getUser()->authorise('core.manage', 'com_jlsitemap'))
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = BaseController::getInstance('JLSitemap');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();