/*
 * @package    System - JLSitemap Cron Plugin
 * @version    0.0.2
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

(function ($) {
	$(document).ready(function () {
		$('[data-input-link]').each(function () {
			let field = $(this),
				input = $(field).find('input'),
				code = $(field).find('code'),
				baseLink = $(field).data('input-link'),
				group = $(field).closest('.control-group'),
				enabledKey = $('[name="jform[params][key_enabled]"]'),
				enabledUsers = $('[name="jform[params][users_enabled]"]'),
				key = $('[name="jform[params][key]"]');

			// Remove label & remove margin
			$(group).find('.control-label').remove();
			$(group).find('.controls').removeClass('controls');

			// Set link
			function setLink() {
				let link = baseLink,
					enabledKey_val = $(enabledKey).filter(':checked').val() * 1,
					enabledUsers_val = $(enabledUsers).filter(':checked').val() * 1;

				if (enabledKey_val === 1 && enabledUsers_val === 0) {
					link += '&key=' + $(key).val();
				}
				(input).val(link);
				$(code).html(link);
			}

			$(enabledKey).on('change', function () {
				setLink()
			});
			$(enabledUsers).on('change', function () {
				setLink()
			});
			$(key).on('change', function () {
				setLink()
			});
			setLink();
		});
	});
})(jQuery);