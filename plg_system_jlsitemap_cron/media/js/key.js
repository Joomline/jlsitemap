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
		$('[data-input-key]').each(function () {
			let field = $(this),
				input = $(field).find('input'),
				enabledKey = $('[name="jform[params][key_enabled]"]'),
				enabledUsers = $('[name="jform[params][users_enabled]"]'),
				generate = $(field).find('.generate');

			// Set required
			function setRequired() {
				let enabledKey_val = $(enabledKey).filter(':checked').val() * 1,
					enabledUsers_val = $(enabledUsers).filter(':checked').val() * 1;

				if (enabledKey_val === 1 && enabledUsers_val === 0) {
					$(input).attr('required', 'true');
				}
				else {
					$(input).removeAttr('required');
				}
			}

			$(enabledKey).on('change', function () {
				setRequired();
			});
			$(enabledUsers).on('change', function () {
				setRequired();
			});
			setRequired();

			// Generate token
			$(generate).on('click', function () {
				let a = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'.split(''),
					b = [];
				for (let i = 0; i < 15; i++) {
					let j = (Math.random() * (a.length - 1)).toFixed(0);
					b[i] = a[j];
				}
				$(input).val(b.join(''));
				$(input).trigger('change');
			});
		});
	});
})(jQuery);
