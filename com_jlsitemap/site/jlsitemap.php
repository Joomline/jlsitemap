<?php
/**
 * @package    JLSitemap Component
 * @version    ${VERSION}
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
use Joomla\CMS\Component\ComponentHelper;

$access_key = ComponentHelper::getComponent('com_jlsitemap')->getParams()->get('access_key');

if (empty($access_key) || $access_key != Factory::getApplication()->input->get('access_key'))
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = BaseController::getInstance('JLSitemap');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();