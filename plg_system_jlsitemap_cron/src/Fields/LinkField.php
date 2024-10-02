<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Plugin\System\Jlsitemap_cron\Fields;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SpacerField;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Uri\Uri;

class LinkField extends FormField
{
    /**
     * The form field type.
     *
     * @var  string
     *
     * @since  0.0.2
     */
    protected $type = 'link';

    /**
     * Name of the layout being used to render the field
     *
     * @var  string
     *
     * @since  0.0.2
     */
    protected $layout = 'plugins.system.jlsitemap_cron.fields.link';

    protected function getLabel()
    {
        return ' ';
    }

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     *
     * @since  0.0.2
     */
    protected function getLayoutData()
    {
        $router = Factory::getContainer()->get(SiteRouter::class);
        $root   = Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $link   = 'index.php?option=com_ajax&plugin=jlsitemap_cron&group=system&format=raw';
        $link   = $router->build($link)->toString();
        $link   = str_replace('/?', '?', $link);
        $link   = $root . $link;
        $data         = parent::getLayoutData();
        $data['link'] = $link;

        return $data;
    }
}
