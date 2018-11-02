<?php
/**
 * @package    JLSitemap Component
 * @version    0.0.2
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

class JLSiteMapController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $default_view = 'home';

	/**
	 * Method to generate sitemap.xml
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function generate()
	{
		try
		{
			$model = $this->getModel('Generation', 'JLSitemapModel');
			if (!$urls = $model->generate())
			{
				$this->setError($model->getError());
				$this->setMessage(Text::sprintf('COM_JLSITEMAP_GENERATION_FAILURE', Text::_($this->getError())), 'error');
				$this->setRedirect('index.php?option=com_jlsitemap');

				return false;
			}

			$this->setMessage(Text::sprintf('COM_JLSITEMAP_GENERATION_SUCCESS', count($urls->includes),
				count($urls->excludes), count($urls->all)));
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			$this->setMessage(Text::sprintf('COM_JLSITEMAP_GENERATION_FAILURE', $this->getError()), 'error');
			$this->setRedirect('index.php?option=com_jlsitemap');

			return false;
		}

		$this->setRedirect('index.php?option=com_jlsitemap');

		return true;
	}
}