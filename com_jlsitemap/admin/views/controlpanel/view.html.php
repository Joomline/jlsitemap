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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class JLSitemapViewControlPanel extends HtmlView
{
	/**
	 * Component params.
	 *
	 * @var  Registry;
	 *
	 * @since  1.5.1
	 */
	protected $params;

	/**
	 * Sidebar html
	 *
	 * @var  string
	 *
	 * @since  0.0.1
	 */
	protected $sidebar;

	/**
	 * Generate url
	 *
	 * @var  string
	 *
	 * @since  1.4.1
	 */
	protected $generate = 'index.php?option=com_jlsitemap&task=sitemap.generate';

	/**
	 * Sitemap info object
	 *
	 * @var  false|object
	 *
	 * @since  1.4.0
	 */
	protected $sitemap = false;

	/**
	 * Plugins url
	 *
	 * @var  string
	 *
	 * @since  1.4.1
	 */
	protected $plugins = 'index.php?option=com_plugins&filter[folder]=jlsitemap';

	/**
	 * Cron plugin
	 *
	 * @var  false|object
	 *
	 * @since  1.4.0
	 */
	protected $cron = false;

	/**
	 * Debug url
	 *
	 * @var  string
	 *
	 * @since  1.4.1
	 */
	protected $debug = 'index.php?option=com_jlsitemap&task=sitemap.generate&debug=1';

	/**
	 * Delete url
	 *
	 * @var  string
	 *
	 * @since  1.4.1
	 */
	protected $delete = 'index.php?option=com_jlsitemap&task=sitemap.delete';

	/**
	 * Config link
	 *
	 * @var  false|object
	 *
	 * @since  1.4.0
	 */
	protected $config = false;

	/**
	 * System messages
	 *
	 * @var  false|array
	 *
	 * @since  1.4.0
	 */
	protected $messages = false;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @throws  Exception
	 *
	 * @return  mixed A string if successful, otherwise an Error object.
	 *
	 * @since  0.0.1
	 */
	public function display($tpl = null)
	{
		// Set title
		ToolBarHelper::title(Text::_('COM_JLSITEMAP') . ': ' . Text::_('COM_JLSITEMAP_CONTROL_PANEL'), 'tree-2');

		// Set params
		$this->params = ComponentHelper::getParams('com_jlsitemap');

		// Set sidebar
		JLSitemapHelper::addSubmenu('controlpanel');
		$this->sidebar = JHtmlSidebar::render();

		// Set sitemap
		$filename = $this->params->get('filename', 'sitemap');
		if (File::exists(JPATH_ROOT . '/' . $filename . '.xml'))
		{
			$sitemap               = new stdClass();
			$sitemap->file         = $filename . '.xml';
			$sitemap->path         = JPATH_ROOT . '/' . $sitemap->file;
			$sitemap->url          = Uri::root() . $sitemap->file;
			$sitemap->date         = '';
			$sitemap->unidentified = true;
			if (preg_match('/<!-- JLSitemap (.*) -->/', file_get_contents($sitemap->path), $matches))
			{
				$sitemap->date         = $matches[1];
				$sitemap->unidentified = false;
			}

			$this->sitemap = $sitemap;
		}

		// Set cron
		if ($cron = PluginHelper::getPlugin('system', 'jlsitemap_cron'))
		{
			$cron->url      = 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $cron->id;
			$cron->params   = new Registry($cron->params);
			$cron->last_run = $cron->params->get('last_run', false);

			$this->cron = $cron;
		}

		// Set config
		$user = Factory::getUser();
		if ($user->authorise('core.admin', 'com_jlsitemap') || $user->authorise('core.options', 'com_jlsitemap'))
		{
			$this->config = 'index.php?option=com_config&view=component&component=com_jlsitemap';
		}

		// Set messages
		if ($messages = Factory::getApplication()->getMessageQueue())
		{
			$this->messages = $messages;
		}

		return parent::display($tpl);
	}
}