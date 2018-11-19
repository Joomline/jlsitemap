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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class JLSiteMapController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $default_view = 'controlpanel';

	/**
	 * Method to generate sitemap.xml
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function generate()
	{
		// Prepare url
		$url = trim(Uri::root(), '/') . '/index.php?option=com_jlsitemap&task=generate&response=json';

		// Get Response
		$response = new Registry(File::read($url));
		$message  = $response->get('message');

		// Set error
		if (!$response->get('success'))
		{
			$this->setError($message);
			$this->setMessage($message, 'error');
			$this->setRedirect('index.php?option=com_jlsitemap');

			return false;
		}

		// Set success
		$this->setMessage($message);
		$this->setRedirect('index.php?option=com_jlsitemap');

		return true;
	}

	/**
	 * Method to show generation debug
	 *
	 * @since 1.1.0
	 */
	public function debug()
	{
		$redirect = trim(Uri::root(true), '/') . '/index.php?option=com_jlsitemap&task=generate&response=debug' .
			'&access_key=' . $this->getAccessKey();

		$this->setRedirect($redirect);
	}

	/**
	 * Method to get Access key
	 *
	 * @return string
	 *
	 * @since 1.1.0
	 */
	protected function getAccessKey()
	{
		// Check access key
		$params     = ComponentHelper::getComponent('com_jlsitemap')->getParams();
		$access_key = $params->get('access_key');
		if (empty($access_key))
		{
			// Prepare access key
			$access_key = '';
			$values     = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's',
				't', 'u', 'v', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
				'P', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
			for ($i = 0; $i < 15; $i++)
			{
				$key        = rand(0, count($values) - 1);
				$access_key .= $values[$key];
			}
			$params->set('access_key', $access_key);

			$component          = new stdClass();
			$component->element = 'com_jlsitemap';
			$component->params  = (string) $params;
			Factory::getDbo()->updateObject('#__extensions', $component, array('element'));
		}

		return $access_key;
	}
}