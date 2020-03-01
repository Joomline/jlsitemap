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
		$('[data-input-link]').each(function () {
			let field = $(this),
				input = $(field).find('input'),
				code = $(field).find('code'),
				baseLink = $(field).data('input-link'),
				group = $(field).closest('.control-group'),
				keyEnable = $('[name="jform[params][key_enabled]"]'),
				clientEnable = $('[name="jform[params][client_enable]"]'),
				key = $('[name="jform[params][key]"]');

			// Remove label & remove margin
			$(group).find('.control-label').remove();
			$(group).find('.controls').removeClass('controls');

			// Set link
			function setLink() {
				let link = baseLink,
					keyEnable_val = $(keyEnable).filter(':checked').val() * 1,
					clientEnable_val = $(clientEnable).filter(':checked').val() * 1;

				if (keyEnable_val === 1 && clientEnable_val === 0) {
					link += '&key=' + $(key).val();
				}
				(input).val(link);
				$(code).html(link);
			}

			$(keyEnable).on('change', function () {
				setLink()
			});
			$(clientEnable).on('change', function () {
				setLink()
			});
			$(key).on('change', function () {
				setLink()
			});
			setLink();
		});
	});
})(jQuery);