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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class JLSiteMapController extends BaseController
{

	/**
	 * Method to generate sitemap.xml
	 *
	 * @return bool
	 *
	 * @since 1.1.0
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
				$this->setResponse();

				return false;
			}

			$this->setMessage(Text::sprintf('COM_JLSITEMAP_GENERATION_SUCCESS', count($urls->includes),
				count($urls->excludes), count($urls->all)));

			$this->setResponse(null, array('response' => $urls));

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			$this->setMessage(Text::sprintf('COM_JLSITEMAP_GENERATION_FAILURE', $this->getError()), 'error');
			$this->setResponse();

			return false;
		}
	}

	/**
	 * Method to set response
	 *
	 * @param string $type Response type
	 * @param mixed  $data Response data
	 *
	 * @return bool
	 *
	 * @since 1.1.0
	 */
	protected function setResponse($type = null, $data = null)
	{
		if (empty($type))
		{
			$type = $this->input->get('response', 'redirect');
		}

		// Json Response
		if ($type == 'json')
		{
			$response = (!empty($data['response'])) ? $data['response'] : '';
			echo new JsonResponse($response, $this->message, !empty($this->_errors));

			Factory::getApplication()->close();

			return true;
		}

		// Debug response
		if ($type == 'debug')
		{
			echo '<pre>', print_r($data, true), '</pre>';

			Factory::getApplication()->close();

			return true;
		}

		// Redirect response

		if (empty($data['redirect']))
		{
			$redirect = rtrim(Uri::base(true), '/') . '/';
		}
		$this->setRedirect(Route::_($redirect, false));

		return true;
	}
}