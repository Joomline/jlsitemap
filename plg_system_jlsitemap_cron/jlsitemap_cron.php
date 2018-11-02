<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    0.0.2
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;

class PlgSystemJLSitemap_Cron extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var boolean
	 *
	 * @since 0.0.2
	 */
	protected $autoloadLanguage = true;

	/**
	 * Run cron
	 *
	 * @since 0.0.2
	 */
	public function onAjaxJLSitemap_Cron()
	{
		$generate = false;
		$error    = '';
		$usersRun = $this->params->get('users_enabled');

		// Server checks
		if (!$usersRun)
		{
			if (!$this->params->get('key_enabled'))
			{
				$generate = true;
			}
			elseif (!$generate = (Factory::getApplication()->input->get('key', '') == $this->params->get('key')))
			{
				$error = Text::_('PLG_SYSTEM_JLSITEMAP_GENERATION_ERROR_KEY');
			}
		}

		// Run generation
		if (!$error && $generate && $urls = $this->generate())
		{
			echo Text::sprintf('PLG_SYSTEM_JLSITEMAP_GENERATION_SUCCESS', count($urls->includes),
				count($urls->excludes), count($urls->all));
		}
		elseif ($error)
		{
			throw new Exception(Text::sprintf('PLG_SYSTEM_JLSITEMAP_GENERATION_FAILURE', $error));
		}

	}

	/**
	 * Method to generate site map
	 *
	 * @return boolean|object
	 *
	 * @since 0.0.2
	 */
	protected function generate()
	{
		try
		{
			// Update last run
			$this->params->set('last_run', Factory::getDate()->toSql());
			$plugin          = new stdClass();
			$plugin->type    = 'plugin';
			$plugin->element = 'jlsitemap_cron';
			$plugin->folder  = 'system';
			$plugin->params  = (string) $this->params;
			Factory::getDbo()->updateObject('#__extensions', $plugin, array('type', 'element', 'folder'));

			// Run generation
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlsitemap/models');
			$model = BaseDatabaseModel::getInstance('Generation', 'JLSitemapModel', array('ignore_request' => true));

			if (!$urls = $model->generate())
			{
				throw new Exception(Text::sprintf('PLG_SYSTEM_JLSITEMAP_GENERATION_FAILURE', $model->getError()));
			}

			return $urls;
		}
		catch (Exception $e)
		{
			throw new Exception(Text::sprintf('PLG_SYSTEM_JLSITEMAP_GENERATION_FAILURE', $e->getMessage()));
		}
	}
}