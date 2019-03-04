<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

class JLSitemapModelHTML extends ListModel
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
	protected $_links = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param  string $ordering  An optional ordering field.
	 * @param  string $direction An optional direction (asc|desc).
	 *
	 * @throws  Exception
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
		if ($menu)
		{
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
	 * Method to get an array of links data.
	 *
	 * @return  mixed  Links registry array on success, false on failure.
	 *
	 * @since  1.6.0
	 */
	public function getItems()
	{
		if ($items = parent::getItems())
		{
			foreach ($items as &$item)
			{
				$item = new Registry($item);
			}
		}

		return $items;
	}

	/**
	 * Gets an array of objects from json sitemap.
	 *
	 * @param   string  $query      The query.
	 * @param   integer $limitstart Offset.
	 * @param   integer $limit      The number of records.
	 *
	 * @return  array  An array of results.
	 *
	 * @since   1.6.0
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$items = array();
		$i     = 0;
		$limit = (int) $limitstart + (int) $limit;
		foreach ($this->getLinks() as $link)
		{
			if ($i < $limitstart)
			{
				$i++;
				continue;
			}
			if ($i == $limit)
			{
				break;
			}
			$items[] = $link;
			$i++;
		}

		return $items;
	}

	/**
	 * Returns a record count for the query.
	 *
	 * @param   string $query The query.
	 *
	 * @return  integer  Number of rows for query.
	 *
	 * @since   3.0
	 */
	protected function _getListCount($query)
	{
		return count($this->getLinks());
	}

	/**
	 * Method to get sitemap links array.
	 *
	 * @return  array  An array of results.
	 *
	 * @since   1.6.0
	 */
	protected function getLinks()
	{
		if ($this->_links === null)
		{
			$file = JPATH_ROOT . '/sitemap.json';
			if (!File::exists($file))
			{
				$this->generate();
			}

			// Decode json
			$registry     = new Registry(file_get_contents($file));
			$this->_links = $registry->toArray();

		}

		return $this->_links;
	}

	/**
	 * Method to generate sitemap
	 *
	 * @since 1.6.0
	 */
	protected function generate()
	{
		$model = BaseDatabaseModel::getInstance('Sitemap', 'JLSitemapModel', array('ignore_request' => true));
		if (!$urls = $model->generate())
		{
			throw new Exception(Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', $model->getError()));
		}
	}
}