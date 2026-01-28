<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Component\JLSitemap\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

class HtmlModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var  string
     *
     * @since  1.6.0
     */
    protected $context = 'jlsitemap.html';

    /**
     * Sitemap links array.
     *
     * @var  array
     *
     * @since  1.6.0
     */
    protected ?array $_links = [];

    /**
     * Method to get an array of links data.
     *
     * @return  mixed  Links registry array on success, false on failure.
     *
     * @since  1.6.0
     */
    public function getItems()
    {
        if ($items = parent::getItems()) {
            foreach ($items as &$item) {
                $item = new Registry($item);
            }
        }

        return $items;
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    protected function populateState($ordering = null, $direction = null)
    {
        // Merge Global and Menu Item params into new object
        $app        = Factory::getApplication('site');
        $params     = $app->getParams();
        $menuParams = new Registry();
        $menu       = $app->getMenu()->getActive();
        if ($menu) {
            $menuParams->loadString($menu->getParams());
        }
        $mergedParams = clone $menuParams;
        $mergedParams->merge($params);

        // Set params state
        $this->setState('params', $mergedParams);

        parent::populateState($ordering, $direction);

        // Set limit & start for query
        $this->setState('list.limit', $params->get('html_limit', 200, 'uint'));
        $this->setState('list.start', $app->input->get('start', 0, 'uint'));
    }

    /**
     * Gets an array of objects from json sitemap.
     *
     * @param   string   $query       The query.
     * @param   integer  $limitstart  Offset.
     * @param   integer  $limit       The number of records.
     *
     * @return  array  An array of results.
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    protected function _getList($query, $limitstart = 0, $limit = 0)
    {
        $items = [];
        $i     = 0;
        $limit = (int)$limitstart + (int)$limit;
        foreach ($this->getLinks() as $link) {
            if ($i < $limitstart) {
                $i++;
                continue;
            }
            if ($i == $limit) {
                break;
            }
            $items[] = $link;
            $i++;
        }

        return $items;
    }

    /**
     * Method to get sitemap links array.
     *
     * @return  array  An array of results.
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    protected function getLinks()
    {
        if (empty($this->_links)) {
            $filename = ComponentHelper::getParams('com_jlsitemap')->get('filename', 'sitemap');
            $file     = JPATH_ROOT . '/' . $filename . '.json';
            if (!\is_file($file)) {
                $this->generate();
            }

            // Decode json
            $registry     = new Registry(file_get_contents($file));
            $this->_links = $registry->toArray();
        }

        return $this->_links;
    }

    /**
     * Method to generate sitemap.
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    protected function generate()
    {
        $model = Factory::getApplication()
            ->bootComponent('com_jlsitemap')
            ->getMVCFactory()
            ->createModel('Sitemap', 'Site', ['ignore_request' => true]);
        if (!$result = $model->generate()) {
            throw new \Exception(Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', $model->getError()));
        }
    }

    /**
     * Returns a record count for the query.
     *
     * @param   string  $query  The query.
     *
     * @return  integer  Number of rows for query.
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    protected function _getListCount($query)
    {
        return \count($this->getLinks());
    }
}