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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

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
		ToolBarHelper::title(Text::_('COM_JLSITEMAP').': '. Text::_('COM_JLSITEMAP_CONTROL_PANEL'), 'tree-2');

		// Set sidebar
		JLSitemapHelper::addSubmenu('controlpanel');
		$this->sidebar = JHtmlSidebar::render();

		$user = Factory::getUser();
		if ($user->authorise('core.admin', 'com_jlsitemap') || $user->authorise('core.options', 'com_jlsitemap'))
		{
			ToolbarHelper::preferences('com_jlsitemap');
			ToolbarHelper::link(Route::_('index.php?option=com_jlsitemap&task=debug'), Text::_('COM_JLSITEMAP_DEBUG'), 'tools');
		}

		return parent::display($tpl);
	}
}