<?php


namespace Joomla\Component\JLSitemap\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Routing class of com_content
 *
 * @since  3.3
 */
class Router extends RouterView
{

    /**
     * The db
     *
     * @var DatabaseInterface
     *
     * @since  4.0.0
     */
    private $db;

    /**
     * Router constructor.
     *
     * @param   CMSApplication  $app   The application object.
     * @param   AbstractMenu    $menu  The menu object to work with.
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
     * @param   string  $id     ID of the item to retrieve the segments.
     * @param   array   $query  The request that is built right now.
     *
     * @return  array|string  The segments of this item.
     *
     * @since  1.6.0
     */
    public function getHTMLSegment($id, $query)
    {
        return [1 => 'html'];
    }

    /**
     * Method to get the id for html.
     *
     * @param   string  $segment  Segment to retrieve the ID.
     * @param   array   $query    The request that is parsed right now.
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
