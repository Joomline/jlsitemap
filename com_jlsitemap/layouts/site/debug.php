<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;

if (!$displayData) return;

$result   = $displayData;
$includes = (is_array($result->includes)) ? $result->includes : array();
$excludes = (is_array($result->excludes)) ? $result->excludes : array();

$multilanguage = Multilanguage::isEnabled();
?>
<!DOCTYPE html>
<html lang="<?php echo Factory::getLanguage()->getTag(); ?>">
<head>
	<meta charset="UTF-8">
	<title><?php echo Text::_('COM_JLSITEMAP') . ': ' . Text::_('COM_JLSITEMAP_SITEMAP_DEBUG'); ?></title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			font-size: 16px;
			font-weight: 400;
			line-height: 1.5;
			-webkit-text-size-adjust: 100%;
			color: #666;
		}

		.container {
			box-sizing: content-box;
			max-width: 1380px;
			margin: 0 auto;
			padding: 0 30px;
		}

		.center {
			text-align: center;
		}

		h1 {
			margin-bottom: 0;
		}

		table {
			border-collapse: collapse;
			border-spacing: 0;
			width: 100%;
			margin-bottom: 20px;
			border-left: 1px solid #e5e5e5;
			border-top: 1px solid #e5e5e5;
		}

		table th {
			padding: 16px 12px;
			text-align: left;
			vertical-align: bottom;
			font-size: 0.875rem;
			font-weight: normal;
			color: #999;
			text-transform: uppercase;
			white-space: nowrap;
		}

		table td {
			padding: 16px 12px;
			vertical-align: top;
			box-sizing: border-box;
		}

		table td,
		table th {
			border-right: 1px solid #e5e5e5;
		}

		table td > :last-child {
			margin-bottom: 0;
		}

		table tbody tr:nth-of-type(odd) {
			background: #f8f8f8;
			border-top: 1px solid #e5e5e5;
			border-bottom: 1px solid #e5e5e5;
		}

		table tbody tr:last-child {
			border-bottom: 1px solid #e5e5e5;
		}

		table tbody tr:hover {
			background: #ffd;
		}

		a {
			color: #1e87f0;
			text-decoration: none;
			cursor: pointer;
		}

		a:active,
		a:hover,
		a:focus {
			outline: none;
		}

		a:hover,
		a:focus {
			color: #0f6ecd;
			text-decoration: underline;
		}

		#excludes {
			margin-top: 30px;
		}

		table tbody .type {
			white-space: nowrap;
		}

		#includes .alternate,
		#excludes .reason {
			margin-bottom: 6px;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1><?php echo Text::_('COM_JLSITEMAP') . ': ' . Text::_('COM_JLSITEMAP_SITEMAP_DEBUG'); ?></h1>
		<div class="description">
			<div>
				<?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_DESCRIPTION'); ?>
			</div>
			<div>
				<?php echo Text::sprintf('COM_JLSITEMAP_SITEMAP_DEBUG_INCLUDES',
					'<a href="#includes">' . count($includes) . '</a>'); ?>
			</div>
			<div>
				<?php echo Text::sprintf('COM_JLSITEMAP_SITEMAP_DEBUG_EXCLUDES',
					'<a href="#excludes">' . count($excludes) . '</a>'); ?>
			</div>
		</div>
		<div id="includes">
			<h2><?php echo Text::sprintf('COM_JLSITEMAP_SITEMAP_DEBUG_INCLUDES', count($includes)); ?></h2>
			<?php if (!empty($includes)): ?>
				<table>
					<thead>
						<tr>
							<th class="center" width="1%">#</th>
							<th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
							<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_TYPE'); ?></th>
							<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_CHANGEFREQ'); ?></th>
							<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_PRIORITY'); ?></th>
							<?php if ($multilanguage): ?>
								<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_ALTERNATES'); ?></th>
							<?php endif; ?>
							<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_LINK'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php $i = 1;
					foreach ($includes as $include): ?>
						<tr>
							<td><?php echo $i; ?></td>
							<td><?php echo $include->get('title', ''); ?></td>
							<td class="type"><?php echo implode('<br>', $include->get('type', array())); ?></td>
							<td><?php echo $include->get('changefreq', ''); ?></td>
							<td><?php echo $include->get('priority', ''); ?></td>
							<?php if ($multilanguage): ?>
								<td>
									<?php if (is_array($include->get('alternates', false))): ?>
										<?php foreach ($include->get('alternates') as $lang => $loc): ?>
											<div class="alternate">
												<a href="<?php echo $loc; ?>" target="_blank">
													<?php echo $lang; ?>
												</a>
											</div>
										<?php endforeach; ?>
									<?php endif; ?>
								</td>
							<?php endif; ?>
							<td>
								<?php if ($include->get('link', false)): ?>
									<a href="<?php echo $include->get('loc', '/'); ?>" target="_blank">
										<?php echo $include->get('link', '/'); ?>
									</a>
								<?php endif; ?>
							</td>
						</tr>
						<?php $i++;
					endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<div id="excludes">
			<h2><?php echo Text::sprintf('COM_JLSITEMAP_SITEMAP_DEBUG_EXCLUDES', count($excludes)); ?></h2>
			<?php if (!empty($excludes)): ?>
				<table>
					<thead>
						<tr>
							<th class="center" width="1%">#</th>
							<th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
							<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_TYPE'); ?></th>
							<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_EXCLUDE'); ?></th>
							<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_LINK'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php $i = 1;
					foreach ($excludes as $exclude): ?>
						<tr>
							<td><?php echo $i; ?></td>
							<td><?php echo $exclude->get('title', ''); ?></td>
							<td class="type"><?php echo implode('<br>', $exclude->get('type', array())); ?></td>
							<td>
								<?php foreach ($exclude->get('exclude', array()) as $reason): ?>
									<div class="reason">
										<?php if ($reason->get('type')): ?>
											<div>
												<strong>
													<?php echo $reason->get('type'); ?>
												</strong>
											</div>
										<?php endif; ?>
										<div>
											<?php echo $reason->get('msg'); ?>
										</div>
									</div>
								<?php endforeach; ?>
							</td>
							<td>
								<?php if ($exclude->get('link', false)): ?>
									<a href="<?php echo $exclude->get('loc', '/'); ?>" target="_blank">
										<?php echo $exclude->get('link', '/'); ?>
									</a>
								<?php endif; ?>
							</td>
						</tr>
						<?php $i++;
					endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>