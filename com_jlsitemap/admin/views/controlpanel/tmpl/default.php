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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('stylesheet', 'media/com_jlsitemap/css/admin.min.css', array('version' => 'auto'));
?>

<div id="controlPanel">
	<div class="actions">
		<div class="wrapper">
			<?php
			$layout = 'components.jlsitemap.admin.action';

			// Generation
			echo LayoutHelper::render($layout, array(
				'class'     => 'generation',
				'url'       => 'index.php?option=com_jlsitemap&task=generate',
				'title'     => 'COM_JLSITEMAP_GENERATION',
				'icon'      => 'generation',
				'newWindow' => false
			));

			// Sitemap
			if (File::exists(JPATH_ROOT . '/sitemap.xml'))
			{
				echo LayoutHelper::render($layout, array(
					'class'     => 'sitemap',
					'url'       => Uri::root() . 'sitemap.xml',
					'title'     => 'COM_JLSITEMAP_SITEMAP',
					'icon'      => 'sitemap',
					'newWindow' => true
				));

				echo LayoutHelper::render($layout, array(
					'class'     => 'delete',
					'url'       => 'index.php?option=com_jlsitemap&task=delete',
					'title'     => 'COM_JLSITEMAP_DELETE',
					'icon'      => 'delete',
					'newWindow' => false
				));
			}
			else
			{
				echo LayoutHelper::render($layout, array(
					'class' => 'no-sitemap not-active error',
					'title' => 'COM_JLSITEMAP_ERROR_SITEMAP_NOT_FOUND',
					'icon'  => 'sitemap',
				));
			}

			// Plugins
			echo LayoutHelper::render($layout, array(
				'class'     => 'plugins',
				'url'       => 'index.php?option=com_plugins&filter[folder]=jlsitemap',
				'title'     => 'COM_JLSITEMAP_PLUGINS',
				'icon'      => 'plugins',
				'newWindow' => true
			));

			// Cron
			if ($cron = PluginHelper::getPlugin('system', 'jlsitemap_cron'))
			{
				echo LayoutHelper::render($layout, array(
					'class'     => 'cron',
					'url'       => 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $cron->id,
					'title'     => 'COM_JLSITEMAP_CRON',
					'icon'      => 'cron',
					'newWindow' => true
				));
			}

			// Debug
			echo LayoutHelper::render($layout, array(
				'class'     => 'debug',
				'url'       => 'index.php?option=com_jlsitemap&task=debug',
				'title'     => 'COM_JLSITEMAP_DEBUG',
				'icon'      => 'debug',
				'newWindow' => true
			));

			// Config
			$user = Factory::getUser();
			if ($user->authorise('core.admin', 'com_jlsitemap') || $user->authorise('core.options', 'com_jlsitemap'))
			{
				echo LayoutHelper::render($layout, array(
					'class'     => 'config',
					'url'       => 'index.php?option=com_config&view=component&component=com_jlsitemap',
					'title'     => 'COM_JLSITEMAP_CONFIG',
					'icon'      => 'config',
					'newWindow' => false
				));
			}
			?>
		</div>
	</div>
	<div class="sidebar">
		<div class="wrapper">
			<div class="title">
				<?php echo Text::_('COM_JLSITEMAP_ADMIN_TEXT'); ?>
			</div>
		</div>
	</div>
</div>
