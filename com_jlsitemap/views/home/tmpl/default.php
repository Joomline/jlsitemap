<?php
/**
 * @package    JLSitemap Component
 * @version    0.0.2
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('stylesheet', 'media/com_jlsitemap/css/admin.min.css', array('version' => 'auto'));
?>

<div class="row-fluid">
	<div class="span8">
		<div class="row-fluid icons-block">
			<div class="span3">
				<a class="item"
				   href="<?php echo Route::_('index.php?option=com_jlsitemap&task=generate'); ?>">
					<div class="img">
						<span class="icon-play large-icon"></span>
					</div>
					<div class="title">
						<?php echo Text::_('COM_JLSITEMAP_GENERATION'); ?>
					</div>
				</a>
			</div>
			<div class="span3">
				<?php if (File::exists(JPATH_ROOT . '/sitemap.xml')): ?>
					<a class="item" href="<?php echo Uri::root() . 'sitemap.xml'; ?>" target="_blank">
						<div class="img">
							<span class="icon-tree-2 large-icon"></span>
						</div>
						<div class="title">
							<?php echo Text::_('COM_JLSITEMAP_SITEMAP'); ?>
						</div>
					</a>
				<?php else: ?>
					<div class="item not-active">
						<div class="img">
							<span class="icon-tree-2 large-icon"></span>
						</div>
						<div class="title text-error">
							<?php echo Text::_('COM_JLSITEMAP_ERROR_SITEMAP_NOT_FOUND'); ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<div class="span3">
				<a class="item" href="<?php echo Route::_('index.php?option=com_plugins&filter[folder]=jlsitemap'); ?>"
				   target="_blank">
					<div class="img">
						<span class="icon-power-cord large-icon"></span>
					</div>
					<div class="title">
						<?php echo Text::_('COM_JLSITEMAP_PLUGINS'); ?>
					</div>
				</a>
			</div>
			<div class="span3">
				<a class="item"
				   href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_jlsitemap'); ?>">
					<div class="img">
						<span class="icon-options large-icon"></span>
					</div>
					<div class="title">
						<?php echo Text::_('COM_JLSITEMAP_CONFIG'); ?>
					</div>
				</a>
			</div>
		</div>
	</div>
	<div class="span4">
		<div class="row-fluid icons-block">	
			<div class="item">
				<div class="title">
					<?php echo Text::_('COM_JLSITEMAP_ADMIN_TEXT'); ?>					
				</div>
			</div>
		</div>
	</div>
</div>