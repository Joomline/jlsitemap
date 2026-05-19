<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Component\JLSitemap\Administrator\Service;

\defined('_JEXEC') or die;

interface SitemapGeneratorAdapterInterface
{
    public function getConfiguration();

    public function setRuntimeContext(array $runtimeContext): void;

    public function getUrls();

    public function generateSingleXML($rows = []);

    public function generateMultiXML($rows = [], $xmlLimit = 50000);

    public function generateXSL($type = null);

    public function generateJSON($rows = []);
}
