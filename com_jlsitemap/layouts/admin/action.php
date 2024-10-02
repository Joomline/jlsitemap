<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  string  $url       Action url
 * @var  string  $class     Action class
 * @var  string  $title     Action title
 * @var  string  $icon      Action icon
 * @var  boolean $newWindow Open in new window
 * @var  string  $badge     Badge text
 */

// Prepare variables
$url       = (!empty($url)) ? Route::_($url) : false;
$class     = (!empty($class)) ? 'action ' . $class : 'action';
$title     = (!empty($title)) ? Text::_($title) : '';
$newWindow = (isset($newWindow)) ? $newWindow : false;
$icon      = (isset($icon) && \is_file(JPATH_ROOT . '/media/com_jlsitemap/icons/' . $icon . '.svg')) ?
    file_get_contents(JPATH_ROOT . '/media/com_jlsitemap/icons/' . $icon . '.svg') : false;
$badge     = (!empty($badge)) ? Text::_($badge) : false;
?>

<div class="card rounded-0 shadow-hover h-100 position-relative <?php
echo $class; ?>">
    <?php
    if ($badge): ?>
        <div class="position-absolute end-0 top-0">
            <span class="badge bg-primary rounded-0 ms-auto"><?php
                echo $badge; ?></span>
        </div>
    <?php
    endif; ?>
    <?php
    if ($icon): ?>
        <div class="card-body text-center card-text">
            <?php
            echo $icon; ?>
        </div>
    <?php
    endif; ?>
    <div class="card-footer text-center card-text bg-light-subtle">
        <?php
        if ($url) {
            $link_attribs = [
                'class' => 'stretched-link link-body-emphasis'
            ];
            if ($newWindow) {
                $link_attribs['target'] = '_blank';
            }
            echo HTMLHelper::link($url, $title, $link_attribs);
        } else {
            echo '<span class="text-secondary-emphasis">' . $title . '</span>';
        }
        ?>

    </div>
</div>
