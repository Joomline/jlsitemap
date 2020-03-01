/*
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

(function ($) {
	$(document).ready(function () {
		$('[data-input-key]').each(function () {
			let field = $(this),
				input = $(field).find('input'),
				keyEnable = $('[name="jform[params][key_enabled]"]'),
				clientEnable = $('[name="jform[params][client_enable]"]'),
				generate = $(field).find('.generate');

			// Set required
			function setRequired() {
				let keyEnable_val = $(keyEnable).filter(':checked').val() * 1,
					clientEnable_val = $(clientEnable).filter(':checked').val() * 1;

				if (keyEnable_val === 1 && clientEnable_val === 0) {
					$(input).attr('required', 'true');
				} else {
					$(input).removeAttr('required');
				}
			}

			$(keyEnable).on('change', function () {
				setRequired();
			});
			$(clientEnable).on('change', function () {
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