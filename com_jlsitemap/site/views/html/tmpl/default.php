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

use Joomla\CMS\Language\Text;

?>
<div id="JLSitemap" class="html">
	<?php if ($this->params->get('show_page_heading')) : ?>
		<div class="page-header">
			<h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
		</div>
	<?php else: ?>
		<h2>
			<?php echo Text::_('COM_JLSITEMAP_SITEMAP'); ?>
		</h2>
	<?php endif; ?>
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>
		<ul class="unstyled">
			<?php foreach ($this->items as $item): ?>
				<li>
					<a href="<?php echo $item->get('link'); ?>">
						<?php echo str_repeat('-', ($item->get('level', 1) - 1)) . ' '
							. $item->get('title', Text::_('COM_JLSITEMAP_TYPES_UNKNOWN')); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="pagination">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	<?php endif; ?>
</div>