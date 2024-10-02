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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$layout = 'components.jlsitemap.admin.action';
$wa     = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle(
    'com_jlsitemap.admin.css',
    'com_jlsitemap/admin.min.css',
    ['version' => 'auto', 'relative' => true]
);
?>

<div id="controlPanel" class="container">
    <div class="row">
        <div class="col-12 col-md-7">
            <div class="row row-cols-2">
                <div class="col mb-3 p-0 p-md-3">
                    <?php
                    // Generation
                    echo LayoutHelper::render($layout, [
                        'class'     => 'generation text-bg-success',
                        'url'       => $this->generate,
                        'title'     => 'COM_JLSITEMAP_GENERATION',
                        'icon'      => 'generation',
                        'newWindow' => false
                    ]); ?>

                </div>
                <div class="col mb-3 p-0 p-md-3">
                    <?php
                    // Sitemap
                    if ($this->sitemap)
                    {
                    if (!$this->sitemap->unidentified) {
                        echo LayoutHelper::render($layout, [
                            'class'     => 'sitemap bg-body-secondary',
                            'url'       => $this->sitemap->url,
                            'title'     => 'COM_JLSITEMAP_SITEMAP',
                            'icon'      => 'sitemap',
                            'newWindow' => true,
                            'badge'     => HTMLHelper::_('date', $this->sitemap->date, Text::_('DATE_FORMAT_LC6'))
                        ]);
                    } else {
                        echo LayoutHelper::render($layout, [
                            'class'     => 'unidentified-sitemap text-bg-danger',
                            'url'       => $this->sitemap->url,
                            'title'     => 'COM_JLSITEMAP_ERROR_SITEMAP_UNIDENTIFIED',
                            'icon'      => 'sitemap',
                            'newWindow' => true,
                            'badge'     => HTMLHelper::_('date', $this->sitemap->date, Text::_('DATE_FORMAT_LC6'))
                        ]);
                    }
                    ?>

                </div>
                <div class="col mb-3  p-0 p-md-3">
                    <?php
                    echo LayoutHelper::render($layout, [
                        'class' => 'delete bg-body-secondary',
                        'url'   => $this->delete,
                        'title' => 'COM_JLSITEMAP_SITEMAP_DELETE',
                        'icon'  => 'delete'
                    ]);
                    }
                    else {
                        echo LayoutHelper::render($layout, [
                            'class' => 'no-sitemap not-active error text-bg-danger',
                            'title' => 'COM_JLSITEMAP_ERROR_SITEMAP_NOT_FOUND',
                            'icon'  => 'sitemap',
                        ]);
                    }
                    ?>

                </div>
                <div class="col mb-3  p-0 p-md-3">
                    <?php
                    // Plugins
                    echo LayoutHelper::render($layout, [
                        'class'     => 'plugins bg-body-secondary',
                        'url'       => $this->plugins,
                        'title'     => 'COM_JLSITEMAP_PLUGINS',
                        'icon'      => 'plugins',
                        'newWindow' => true
                    ]);
                    ?>

                </div>
                <div class="col mb-3  p-0 p-md-3">
                    <?php
                    // Cron
                    if ($this->cron) {
                        echo LayoutHelper::render($layout, [
                            'class'     => 'cron bg-body-secondary',
                            'url'       => $this->cron->url,
                            'title'     => 'COM_JLSITEMAP_CRON',
                            'icon'      => 'cron',
                            'newWindow' => true,
                            'badge'     => ($this->cron->last_run) ?
                                HTMLHelper::_('date', $this->cron->last_run, Text::_('DATE_FORMAT_LC6')) : false
                        ]);
                    }
                    ?>

                </div>
                <div class="col mb-3  p-0 p-md-3">
                    <?php
                    // Debug
                    echo LayoutHelper::render($layout, [
                        'class'     => 'debug bg-body-secondary',
                        'url'       => $this->debug,
                        'title'     => 'COM_JLSITEMAP_DEBUG',
                        'icon'      => 'debug',
                        'newWindow' => true
                    ]);
                    ?>
                </div>
                <div class="col mb-3  p-0 p-md-3">
                    <?php
                    // Config
                    if ($this->config) {
                        echo LayoutHelper::render($layout, [
                            'class' => 'config bg-body-secondary',
                            'url'   => $this->config,
                            'title' => 'COM_JLSITEMAP_CONFIG',
                            'icon'  => 'config'
                        ]);
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card shadow rounded-0">
                <div class="card-body text-center">
                    <?php
                    echo Text::_('COM_JLSITEMAP_ADMIN_TEXT'); ?>
                </div>
            </div>
        </div>
    </div>
</div>