<?php
/**
 * @package    JLSitemap - VirtueMart Plugin
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2019 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

class plgJLSitemapVirtueMart extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var boolean
	 *
	 * @since 1.6.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to get urls array
	 *
	 * @param array    $urls   Urls array
	 * @param Registry $config Component config
	 *
	 * @return array Urls array with attributes
	 *
	 * @since 1.6.0
	 */
	public function onGetUrls(&$urls, $config)
	{
		if (!$this->params->get('products_enable')
			&& !$this->params->get('categories_enable')
			&& !$this->params->get('manufacturers_enable')
			&& !$this->params->get('vendors_enable'))
		{
			return $urls;
		}

		// Add config
		JLoader::register('VmConfig', JPATH_ROOT . '/administrator/components/com_virtuemart/helpers/config.php');

		$db                 = Factory::getDbo();
		$defaultLanguage    = VmConfig::get('vmDefLang');
		$defaultLanguageKey = str_replace('-', '_', strtolower($defaultLanguage));
		$activeLanguages    = VmConfig::get('active_languages');
		$multilanguage      = ($config->get('multilanguage') && !empty($activeLanguages));
		$languages          = array($defaultLanguageKey => $defaultLanguage);
		if ($multilanguage)
		{
			foreach ($activeLanguages as $language)
			{
				$languages[str_replace('-', '_', strtolower($language))] = $language;
			}
		}

		// Get products
		if ($this->params->get('products_enable'))
		{
			$query = $db->getQuery(true)
				->select(array('p.virtuemart_product_id as id', 'p.published', 'p.metarobot'))
				->from($db->quoteName('#__virtuemart_products', 'p'));

			foreach ($languages as $key => $code)
			{
				$query->select(array($key . '.product_name as ' . 'product_name_' . $key))
					->leftJoin($db->quoteName('#__virtuemart_products_' . $key, $key)
						. '  ON ' . $key . '.virtuemart_product_id = p.virtuemart_product_id');
			}

			$rows       = $db->setQuery($query)->loadObjectList();
			$changefreq = $this->params->get('products_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('products_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare default title
				$selector     = 'product_name_' . $defaultLanguageKey;
				$defaultTitle = $row->$selector;

				// Prepare default loc attribute
				$defaultLoc = 'index.php?option=com_virtuemart&view=productdetails&Itemid=0&virtuemart_product_id='
					. $row->id;

				// Prepare exclude attribute
				$exclude = array();
				if (preg_match('/noindex/', $row->metarobot))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_PRODUCT'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_PRODUCT_ROBOTS'));
				}
				if (!$row->published)
				{
					$exclude[] = array(
						'type' => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_PRODUCT'),
						'msg'  => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_PRODUCT_UNPUBLISH')
					);
				}

				foreach ($languages as $key => $code)
				{
					$selector = 'product_name_' . $key;
					$title    = (!empty($row->$selector)) ? $row->$selector : $defaultTitle;

					$loc = $defaultLoc;
					if ($multilanguage)
					{
						$loc .= '&lang=' . $code;
					}

					// Prepare product object
					$product             = new stdClass();
					$product->type       = Text::_('PLG_JLSITEMAP_VIRTUEMART_TYPES_PRODUCT');
					$product->title      = $title;
					$product->loc        = $loc;
					$product->changefreq = $changefreq;
					$product->priority   = $priority;
					$product->exclude    = (!empty($exclude)) ? $exclude : false;
					$product->alternates = ($multilanguage) ? array() : false;
					if ($multilanguage)
					{
						foreach ($languages as $alternate)
						{
							$product->alternates[$alternate] = $defaultLoc . '&lang=' . $alternate;
						}
					}

					// Add product to urls
					$urls[] = $product;
				}
			}
		}

		// Get categories
		if ($this->params->get('categories_enable'))
		{
			$query = $db->getQuery(true)
				->select(array('c.virtuemart_category_id as id', 'c.published', 'c.metarobot'))
				->from($db->quoteName('#__virtuemart_categories', 'c'));

			foreach ($languages as $key => $code)
			{
				$query->select(array($key . '.category_name as ' . 'category_name_' . $key))
					->leftJoin($db->quoteName('#__virtuemart_categories_' . $key, $key)
						. '  ON ' . $key . '.virtuemart_category_id = c.virtuemart_category_id');
			}

			$rows       = $db->setQuery($query)->loadObjectList();
			$changefreq = $this->params->get('categories_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('categories_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare default title
				$selector     = 'category_name_' . $defaultLanguageKey;
				$defaultTitle = $row->$selector;

				// Prepare default loc attribute
				$defaultLoc = 'index.php?option=com_virtuemart&view=category&virtuemart_manufacturer_id=0&Itemid=0&virtuemart_category_id='
					. $row->id;

				// Prepare exclude attribute
				$exclude = array();
				if (preg_match('/noindex/', $row->metarobot))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_CATEGORY'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_CATEGORY_ROBOTS'));
				}
				if (!$row->published)
				{
					$exclude[] = array(
						'type' => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_CATEGORY'),
						'msg'  => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_CATEGORY_UNPUBLISH')
					);
				}

				foreach ($languages as $key => $code)
				{
					$selector = 'category_name_' . $key;
					$title    = (!empty($row->$selector)) ? $row->$selector : $defaultTitle;

					$loc = $defaultLoc;
					if ($multilanguage)
					{
						$loc .= '&lang=' . $code;
					}

					// Prepare category object
					$category             = new stdClass();
					$category->type       = Text::_('PLG_JLSITEMAP_VIRTUEMART_TYPES_CATEGORY');
					$category->title      = $title;
					$category->loc        = $loc;
					$category->changefreq = $changefreq;
					$category->priority   = $priority;
					$category->exclude    = (!empty($exclude)) ? $exclude : false;
					$category->alternates = ($multilanguage) ? array() : false;
					if ($multilanguage)
					{
						foreach ($languages as $alternate)
						{
							$category->alternates[$alternate] = $defaultLoc . '&lang=' . $alternate;
						}
					}

					// Add category to urls
					$urls[] = $category;
				}
			}
		}

		// Get manufacturers
		if ($this->params->get('manufacturers_enable'))
		{
			$query = $db->getQuery(true)
				->select(array('m.virtuemart_manufacturer_id as id', 'm.published', 'm.metarobot'))
				->from($db->quoteName('#__virtuemart_manufacturers', 'm'));

			foreach ($languages as $key => $code)
			{
				$query->select(array($key . '.mf_name as ' . 'manufacturer_name_' . $key))
					->leftJoin($db->quoteName('#__virtuemart_manufacturers_' . $key, $key)
						. '  ON ' . $key . '.virtuemart_manufacturer_id = m.virtuemart_manufacturer_id');
			}

			$rows       = $db->setQuery($query)->loadObjectList();
			$changefreq = $this->params->get('manufacturers_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('manufacturers_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare default title
				$selector     = 'manufacturer_name_' . $defaultLanguageKey;
				$defaultTitle = $row->$selector;

				// Prepare default loc attribute
				$defaultLoc = 'index.php?option=com_virtuemart&view=manufacturer&layout=details&Itemid=0&virtuemart_manufacturer_id='
					. $row->id;

				// Prepare exclude attribute
				$exclude = array();
				if (preg_match('/noindex/', $row->metarobot))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_MANUFACTURER'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_MANUFACTURER_ROBOTS'));
				}
				if (!$row->published)
				{
					$exclude[] = array(
						'type' => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_MANUFACTURER'),
						'msg'  => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_MANUFACTURER_UNPUBLISH')
					);
				}

				foreach ($languages as $key => $code)
				{
					$selector = 'category_name_' . $key;
					$title    = (!empty($row->$selector)) ? $row->$selector : $defaultTitle;

					$loc = $defaultLoc;
					if ($multilanguage)
					{
						$loc .= '&lang=' . $code;
					}

					// Prepare manufacturer object
					$manufacturer             = new stdClass();
					$manufacturer->type       = Text::_('PLG_JLSITEMAP_VIRTUEMART_TYPES_MANUFACTURER');
					$manufacturer->title      = $title;
					$manufacturer->loc        = $loc;
					$manufacturer->changefreq = $changefreq;
					$manufacturer->priority   = $priority;
					$manufacturer->exclude    = (!empty($exclude)) ? $exclude : false;
					$manufacturer->alternates = ($multilanguage) ? array() : false;
					if ($multilanguage)
					{
						foreach ($languages as $alternate)
						{
							$manufacturer->alternates[$alternate] = $defaultLoc . '&lang=' . $alternate;
						}
					}

					// Add category to urls
					$urls[] = $category;
				}
			}
		}

		// Get vendors
		if ($this->params->get('vendors_enable'))
		{
			$query = $db->getQuery(true)
				->select(array('m.virtuemart_vendor_id as id', 'm.metarobot'))
				->from($db->quoteName('#__virtuemart_vendors', 'm'));

			foreach ($languages as $key => $code)
			{
				$query->select(array($key . '.vendor_store_name as ' . 'vendor_name_' . $key))
					->leftJoin($db->quoteName('#__virtuemart_vendors_' . $key, $key)
						. '  ON ' . $key . '.virtuemart_vendor_id = m.virtuemart_vendor_id');
			}

			$rows       = $db->setQuery($query)->loadObjectList();
			$changefreq = $this->params->get('vendors_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('vendors_priority', $config->get('priority', '0.5'));

			foreach ($rows as $row)
			{
				// Prepare default title
				$selector     = 'vendor_name_' . $defaultLanguageKey;
				$defaultTitle = $row->$selector;

				// Prepare default loc attribute
				$defaultLoc = 'index.php?option=com_virtuemart&view=vendor&layout=tos&Itemid=&virtuemart_vendor_id='
					. $row->id;

				// Prepare exclude attribute
				$exclude = array();
				if (preg_match('/noindex/', $row->metarobot))
				{
					$exclude[] = array('type' => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_VENDOR'),
					                   'msg'  => Text::_('PLG_JLSITEMAP_VIRTUEMART_EXCLUDE_VENDOR_ROBOTS'));
				}

				foreach ($languages as $key => $code)
				{
					$selector = 'vendor_name_' . $key;
					$title    = (!empty($row->$selector)) ? $row->$selector : $defaultTitle;

					$loc = $defaultLoc;
					if ($multilanguage)
					{
						$loc .= '&lang=' . $code;
					}

					// Prepare vendor object
					$vendor             = new stdClass();
					$vendor->type       = Text::_('PLG_JLSITEMAP_VIRTUEMART_TYPES_VENDOR');
					$vendor->title      = $title;
					$vendor->loc        = $loc;
					$vendor->changefreq = $changefreq;
					$vendor->priority   = $priority;
					$vendor->exclude    = (!empty($exclude)) ? $exclude : false;
					$vendor->alternates = ($multilanguage) ? array() : false;
					if ($multilanguage)
					{
						foreach ($languages as $alternate)
						{
							$vendor->alternates[$alternate] = $defaultLoc . '&lang=' . $alternate;
						}
					}

					// Add vendor to urls
					$urls[] = $vendor;
				}
			}
		}

		return $urls;
	}
}