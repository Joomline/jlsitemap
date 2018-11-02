/*
 * @package    System - JLSitemap Cron Plugin
 * @version    0.0.2
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2018 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

try {
	document.addEventListener('DOMContentLoaded', function () {
		let xhr = new XMLHttpRequest();
		xhr.open('GET', 'index.php?option=com_ajax&plugin=jlsitemap_cron&group=system&format=json', false);
		xhr.send();
		if (xhr.status !== 200) {
			console.error('Sitemap cron error:' + xhr.status + ': ' + xhr.statusText);
		}
		else {
			console.debug('Sitemap cron success');
		}
	});
} catch (e) {
	console.error('Sitemap cron error: ', e);
}