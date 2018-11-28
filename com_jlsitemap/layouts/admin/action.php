<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @url       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  string  $url       Action url
 * @var  string  $class     Action class
 * @var  string  $title     Action title
 * @var  string  $icon      Action icon
 * @var  boolean $newWindow Open in new window
 */

// Prepare variables
$url       = (!empty($url)) ? Route::_($url) : false;
$class     = (!empty($class)) ? 'action ' . $class : 'action';
$title     = (!empty($title)) ? Text::_($title) : '';
$newWindow = (isset($newWindow)) ? $newWindow : false;
$icon      = (isset($icon) && File::exists(JPATH_ROOT . '/media/com_jlsitemap/icons/' . $icon . '.svg')) ?
	file_get_contents(JPATH_ROOT . '/media/com_jlsitemap/icons/' . $icon . '.svg') : false;
?>
<?php if ($url): ?>
	<a href="<?php echo $url; ?>"
	   class="<?php echo $class; ?>"<?php echo ($newWindow) ? ' target="_blank"' : ''; ?>>
		<div class="head">
			<?php if ($icon): ?>
				<div class="icon">
					<?php echo $icon; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="title">
			<?php echo $title; ?>
		</div>
	</a>
<?php else: ?>
	<div class="<?php echo $class; ?>">
		<div class="header">
			<?php if ($icon): ?>
				<div class="icon">
					<?php echo $icon; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="title">
			<?php echo $title; ?>
		</div>
	</div>
<?php endif; ?>
