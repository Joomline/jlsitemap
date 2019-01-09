<?php
/**
 * @package    System - JLSitemap Cron Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;

class JFormFieldDate extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var string
	 *
	 * @since 1.0.1
	 */
	protected $type = 'date';

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var string
	 *
	 * @since 1.0.1
	 */
	protected $layout = 'plugins.system.jlsitemap_cron.fields.date';
}