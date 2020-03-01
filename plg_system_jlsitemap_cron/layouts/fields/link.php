<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  string $id   DOM id of the field.
 * @var  string $name Name of the input field.
 * @var  string $link Base cron link.
 */

HTMLHelper::_('jquery.framework');
HTMLHelper::_('stylesheet', 'media/plg_system_jlsitemap_cron/css/link.min.css', array('version' => 'auto'));
HTMLHelper::_('script', 'media/plg_system_jlsitemap_cron/js/link.min.js', array('version' => 'auto'));

?>
<div id="<?php echo $id; ?>" data-input-link="<?php echo $link; ?>">
	<div class="title">
		<?php echo Text::_('PLG_SYSTEM_JLSITEMAP_CRON_PARAMS_LINK'); ?>
	</div>
	<div class="input clearfix">
		<code class=""></code>
		<input type="hidden" name="<?php echo $name; ?>">
	</div>
	<div class="description">
		<?php echo Text::_('PLG_SYSTEM_JLSITEMAP_CRON_PARAMS_LINK_DESC'); ?>
	</div>
</div>