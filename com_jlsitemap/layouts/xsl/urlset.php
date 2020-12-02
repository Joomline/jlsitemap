<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2020 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  string $date Generation date.
 */


$stylesheet = Uri::getInstance()->toString(array('scheme', 'host', 'port')) .
	HTMLHelper::stylesheet('com_jlsitemap/sitemap.min.css', array('version' => 'auto', 'relative' => true, 'pathOnly' => true));
$sitename   = htmlspecialchars(Factory::getConfig()->get('sitename'));
?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
				xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
				xmlns:xhtml="http://www.w3.org/1999/xhtml">
	<xsl:output method="html" indent="yes" encoding="UTF-8"/>
	<xsl:template match="/">
		<html lang="<?php echo Factory::getLanguage()->getTag(); ?>">
		<head>
			<meta charset="UTF-8"/>
			<title><?php echo Text::sprintf('COM_JLSITEMAP_XSL_TITLE', $sitename); ?></title>
			<link rel="stylesheet" href="<?php echo $stylesheet; ?>"/>
		</head>
		<body>
		<div class="container">
			<h1>
				<?php echo Text::sprintf('COM_JLSITEMAP_XSL_TITLE', $sitename); ?>
			</h1>
			<p class="description">
				<?php echo Text::sprintf('COM_JLSITEMAP_XSL_DESCRIPTION', '<xsl:value-of select="count(sitemap:urlset/sitemap:url)"/>'); ?>
			</p>
			<xsl:apply-templates/>
			<div class="center muted">
				<?php echo Text::_('COM_JLSITEMAP_XSL_COPYRIGHT'); ?>
			</div>
			<?php if ($date): ?>
				<div class="center muted">
					<?php echo $date; ?>
				</div>
			<?php endif; ?>
		</div>
		</body>
		</html>
	</xsl:template>
	<xsl:template match="sitemap:urlset">
		<table>
			<thead>
			<tr>
				<th class="center" width="1%">#</th>
				<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_LINK'); ?></th>
				<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_CHANGEFREQ'); ?></th>
				<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_PRIORITY'); ?></th>
				<th><?php echo Text::_('COM_JLSITEMAP_SITEMAP_DEBUG_LAST_MODIFIED'); ?></th>
			</tr>
			</thead>
			<tbody>
			<xsl:for-each select="sitemap:url">
				<xsl:variable name="loc">
					<xsl:value-of select="sitemap:loc"/>
				</xsl:variable>
				<tr>
					<td>
						<xsl:value-of select="position()"/>
					</td>
					<td>
						<div>
							<a href="{$loc}">
								<xsl:value-of select="sitemap:loc"/>
							</a>
						</div>
						<div class="additions">
							<xsl:if test="xhtml:link">
								<xsl:apply-templates select="xhtml:link"/>
							</xsl:if>
							<xsl:if test="image:image">
								<xsl:apply-templates select="image:image"/>
							</xsl:if>
						</div>
					</td>
					<td>
						<xsl:value-of select="sitemap:changefreq"/>
					</td>
					<td>
						<xsl:value-of select="sitemap:priority"/>
					</td>
					<td class="nowrap">
						<xsl:value-of select="sitemap:lastmod"/>
					</td>
				</tr>
			</xsl:for-each>
			</tbody>
		</table>
	</xsl:template>
	<xsl:template match="xhtml:link">
		<xsl:variable name="altloc">
			<xsl:value-of select="@href"/>
		</xsl:variable>
		<xsl:if test="@hreflang">
			<div class="item">
				<a href="{$altloc}" class="alternate" target="_blank">
					<xsl:value-of select="@hreflang"/>
				</a>
			</div>
		</xsl:if>
		<xsl:apply-templates/>
	</xsl:template>
	<xsl:template match="image:image">
		<xsl:variable name="loc">
			<xsl:value-of select="image:loc"/>
		</xsl:variable>
		<div class="item">
			<a href="{$loc}" class="image" title="{$loc}" target="_blank">
				<svg width="20" height="20" viewBox="0 0 20 20"
					 xmlns="http://www.w3.org/2000/svg" data-svg="image">
					<rect fill="none" stroke="#1e87f0" x=".5" y="2.5" width="19"
						  height="15"></rect>
					<polyline fill="none" stroke="#1e87f0" stroke-width="1.01"
							  points="4,13 8,9 13,14"></polyline>
					<polyline fill="none" stroke="#1e87f0" stroke-width="1.01"
							  points="11,12 12.5,10.5 16,14"></polyline>
				</svg>
			</a>
		</div>
	</xsl:template>
</xsl:stylesheet>