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
			$debug = ($this->input->get('response') == 'debug');
			if (!$urls = $model->generate($debug))
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
		$app     = Factory::getApplication();
		$message = $this->message;

		if (empty($type))
		{
			$type = $this->input->get('response', 'redirect');
		}

		// Unset message if don't redirect response
		if ($type != 'redirect')
		{
			$this->setMessage(null);
		}

		// Admin response
		if ($type == 'admin')
		{
			$name    = 'jlsitemap_generation';
			$value   = new JsonResponse('', $message, !empty($this->_errors));
			$expires = Factory::getDate('+1 day')->toUnix();

			$app->input->cookie->set($name, $value, $expires, $app->get('cookie_path', '/'),
				$app->get('cookie_domain'), $app->isSSLConnection());

			$redirect = rtrim(Uri::base(true), '/') . '/administrator/index.php?option=com_jlsitemap&task=generate';
			$this->setRedirect(Route::_($redirect, false));

			return true;
		}

		// Json Response
		if ($type == 'json')
		{
			$response = (!empty($data['response'])) ? $data['response'] : '';
			echo new JsonResponse($response, $message, !empty($this->_errors));

			$app->close();

			return true;
		}

		// Debug response
		if ($type == 'debug')
		{
			if (!empty($this->_errors))
			{
				throw new Exception($message);
			}

			echo '<pre>', print_r($data, true), '</pre>';

			$app->close();

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

	/**
	 * Redirects the browser or returns false if no redirect is set.
	 *
	 * @return  boolean  False if no redirect exists.
	 *
	 * @since   1.2.1
	 */
	public function redirect()
	{
		if ($this->redirect)
		{
			$app = Factory::getApplication();

			// Enqueue the redirect message
			if (!empty($this->message))
			{
				$app->enqueueMessage($this->message, $this->messageType);
			}

			// Execute the redirect
			$app->redirect($this->redirect);
		}

		return false;
	}
}
