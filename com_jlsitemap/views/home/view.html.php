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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class JLSitemapViewHome extends HtmlView
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
		ToolBarHelper::title(Text::_('COM_JLSITEMAP'), 'tree-2');

		// Set sidebar
		JLSitemapHelper::addSubmenu('home');
		$this->sidebar = JHtmlSidebar::render();

		$user = Factory::getUser();
		if ($user->authorise('core.admin', 'com_jlsitemap') || $user->authorise('core.options', 'com_jlsitemap'))
		{
			ToolbarHelper::preferences('com_jlsitemap');
		}

		return parent::display($tpl);
	}
}