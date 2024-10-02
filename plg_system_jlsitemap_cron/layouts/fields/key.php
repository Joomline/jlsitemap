<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
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
 * @var  string $id    DOM id of the field.
 * @var  string $name  Name of the input field.
 * @var  string $value Value attribute of the field.
 */

HTMLHelper::_('jquery.framework');
HTMLHelper::_('script', 'media/plg_system_jlsitemap_cron/js/key.min.js', ['version' => 'auto']);

?>
<div class="input-group" data-input-key="<?php
echo $id; ?>">
    <input id="<?php
    echo $id; ?>" type="text" class="form-control" name="<?php
    echo $name; ?>" value="<?php
    echo $value; ?>">
    <a class="btn btn-primary generate"><?php
        echo Text::_('PLG_SYSTEM_JLSITEMAP_CRON_PARAMS_KEY_GENERATE'); ?></a>
</div>