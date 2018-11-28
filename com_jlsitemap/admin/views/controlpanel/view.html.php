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
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

class JLSitemapViewControlPanel extends HtmlView
{
	/**
	 * The sidebar html
	 *
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $sidebar;

	/**
	 * The sitemap info
	 *
	 * @var false|object
	 *
	 * @since 1.4.0
	 */
	protected $sitemap = false;

	/**
	 * The cron plugin
	 *
	 * @var false|object
	 *
	 * @since 1.4.0
	 */
	protected $cron = false;

	/**
	 * The config link
	 *
	 * @var false|object
	 *
	 * @since 1.4.0
	 */
	protected $config = false;

	/**
	 * The system message
	 *
	 * @var false|array
	 *
	 * @since 1.4.0
	 */
	protected $messages = false;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @since 0.0.1
	 */
	public function display($tpl = null)
	{
		// Set title
		ToolBarHelper::title(Text::_('COM_JLSITEMAP') . ': ' . Text::_('COM_JLSITEMAP_CONTROL_PANEL'), 'tree-2');

		// Set sidebar
		JLSitemapHelper::addSubmenu('controlpanel');
		$this->sidebar = JHtmlSidebar::render();

		// Set sitemap
		if (File::exists(JPATH_ROOT . '/sitemap.xml'))
		{
			$sitemap       = new stdClass();
			$sitemap->file = 'sitemap.xml';
			$sitemap->path = JPATH_ROOT . '/' . $sitemap->file;
			$sitemap->url  = Uri::root() . $sitemap->file;
			$sitemap->date = stat($sitemap->path)['mtime'];

			$this->sitemap = $sitemap;
		}

		// Set cron
		if ($cron = PluginHelper::getPlugin('system', 'jlsitemap_cron'))
		{
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