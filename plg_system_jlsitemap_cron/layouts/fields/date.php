<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  string $id    DOM id of the field.
 * @var  string $name  Name of the input field.
 * @var  string $value Value attribute of the field.
 */

$date = (empty($value)) ? Text::_('JNEVER') : HTMLHelper::_('date', $value, Text::_('DATE_FORMAT_LC2')) .
	' (' . Factory::getConfig()->get('offset') . ')'
?>
<div data-input-date="<?php echo $id; ?>">
	<input type="text" readonly value="<?php echo $date; ?>">
	<input id="<?php echo $id; ?>" type="hidden" readonly value="<?php echo $value; ?>">
</div>
