<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
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

$app    = Factory::getApplication();
$access = false;

// Check access key
$access_key = ComponentHelper::getComponent('com_jlsitemap')->getParams()->get('access_key');
if (!empty($access_key) && $access_key == $app->input->get('access_key'))
{
	$access = true;
}

// Check can manage component
if (!$access && Factory::getUser()->authorise('core.manage', 'com_jlsitemap'))
{
	$access = true;
}

// Check if server request
if (!$access && $app->input->server->get('SERVER_ADDR') == $app->input->server->get('REMOTE_ADDR'))
{
	$access = true;
}

// Trow if don't has access
if (!$access)
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = BaseController::getInstance('JLSitemap');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();