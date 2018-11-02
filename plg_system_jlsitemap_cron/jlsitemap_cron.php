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