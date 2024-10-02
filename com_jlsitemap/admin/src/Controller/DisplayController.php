<?php
/*
 * @package    SW JProjects
 * @version    2.1.1
 * @author     Sergey Tolkachyov
 * @сopyright  Copyright (c) 2018 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 */

namespace Joomla\Component\JLSitemap\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\SWJProjects\Site\Helper\RouteHelper;
use Joomla\Registry\Registry;

class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var  string
     *
     * @since  0.0.1
     */
    protected $default_view = 'controlpanel';

    /**
     * Typical view method for MVC based architecture
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link       https://web-tolk.ru
     *
     * @return  BaseController  A BaseController object to support chaining.
     *
     * @throws  \Exception
     *
     * @since   1.5.2
     */
    public function display($cachable = false, $urlparams = array())
    {
        return parent::display($cachable, $urlparams);
    }
}