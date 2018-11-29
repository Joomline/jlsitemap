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
use Joomla\CMS\Uri\Uri;

class JLSiteMapControllerSitemap extends BaseController
{
	/**
	 * Method to generate sitemap
	 *
	 * @return bool
	 *
	 * @since 1.4.1
	 */
	public function generate()
	{
		$app      = Factory::getApplication();
		$debug    = (!empty($app->input->get('debug')));
		$model    = $this->getModel('Generation', 'JLSitemapModel');
		$error    = (!$result = $model->generate($debug)) ? $model->getError() : false;
		$includes = (!$error) ? count($result->includes) : 0;
		$excludes = (!$error) ? count($result->excludes) : 0;

		// Set messages
		if (!$debug && !empty($app->input->get('messages')))
		{
			if ($error)
			{
				$app->enqueueMessage(Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', Text::_($error)), 'error');
			}
			else
			{
				$app->enqueueMessage(Text::_('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS'));
				$app->enqueueMessage(Text::_('COM_JLSITEMAP_SITEMAP_GENERATION') . ': ' .
					Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS_INCLUDES', $includes), 'notice');

				if (!empty($excludes))
				{
					$app->enqueueMessage(Text::_('COM_JLSITEMAP_SITEMAP_GENERATION') . ': ' .
						Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS_EXCLUDES', $excludes), 'warning');
				}
			}
		}

		// Set cookies
		if (!$debug && !empty($app->input->get('cookies')))
		{
			$name    = 'jlsitemap_generation';
			$message = (!$error) ? Text::_('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS') :
				Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', Text::_($error));
			$value   = new JsonResponse(array('includes' => $includes, 'excludes' => $excludes), $message, $error);
			$expires = Factory::getDate('+1 day')->toUnix();

			$app->input->cookie->set($name, $value, $expires, $app->get('cookie_path', '/'),
				$app->get('cookie_domain'), $app->isSSLConnection());
		}

		// Redirect
		if (!$debug && !empty($app->input->get('redirect')))
		{
			$url = (!empty($this->input->get('return', null, 'base64'))) ?
				base64_decode($this->input->get('return', null, 'base64')) : Uri::root(true);

			$app->redirect(str_replace('&amp;', '&', $url));

			return (!$error);
		}

		// Json response
		if (!$debug && $app->input->get('response') == 'json')
		{
			$message = (!$error) ? Text::_('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS') :
				Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', Text::_($error));

			echo new JsonResponse(array('includes' => $includes, 'excludes' => $excludes), $message, $error);;
			$app->close();

			return (!$error);
		}

		return (!$error);
	}
}