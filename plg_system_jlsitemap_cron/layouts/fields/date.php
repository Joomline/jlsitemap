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
use Joomla\CMS\Factory;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  string $id    DOM id of the field.
 * @var  string $name  Name of the input field.
 * @var  string $value Value attribute of the field.
 */

$date = (empty($value)) ? Text::_('JNEVER') : HTMLHelper::_('date', $value, Text::_('DATE_FORMAT_LC6')) .
	' (' . Factory::getConfig()->get('offset') . ')'
?>
<div data-input-date="<?php echo $id; ?>">
	<input type="text" value="<?php echo $date; ?>" readonly>
	<input id="<?php echo $id; ?>" type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" readonly>
</div>