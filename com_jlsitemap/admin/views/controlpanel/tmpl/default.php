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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

?>
<div id="controlPanel">
	<div class="main">
		<?php echo LayoutHelper::render('components.jlsitemap.admin.messages', $this->messages); ?>
		<div class="actions">
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
			if ($this->sitemap)
			{
				echo LayoutHelper::render($layout, array(
					'class'     => 'sitemap',
					'url'       => $this->sitemap->url,
					'title'     => 'COM_JLSITEMAP_SITEMAP',
					'icon'      => 'sitemap',
					'newWindow' => true,
					'badge'      => HTMLHelper::_('date', $this->sitemap->date, Text::_('DATE_FORMAT_LC2'))
				));

				echo LayoutHelper::render($layout, array(
					'class'     => 'delete',
					'url'       => 'index.php?option=com_jlsitemap&task=delete',
					'title'     => 'COM_JLSITEMAP_DELETE',
					'icon'      => 'delete'
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
			if ($this->cron)
			{
				echo LayoutHelper::render($layout, array(
					'class'     => 'cron',
					'url'       => 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $this->cron->id,
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
			if ($this->config)
			{
				echo LayoutHelper::render($layout, array(
					'class'     => 'config',
					'url'       => $this->config,
					'title'     => 'COM_JLSITEMAP_CONFIG',
					'icon'      => 'config'
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
