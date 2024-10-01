<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Component\JLSitemap\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Uri\Uri;

class SitemapController extends BaseController
{
    /**
     * Method to generate sitemap.
     *
     * @return  bool True on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  1.4.1
     */
    public function generate()
    {
        // Check access
        $this->checkAccess();

        // Check language
        $this->checkLanguage();

        // Prepare variables
        $app      = Factory::getApplication();
        $debug    = (!empty($this->input->get('debug')));
        $model    = $this->getModel();
        $error    = (!$result = $model->generate($debug)) ? $model->getError() : false;
        $all      = (!$error) ? count($result->all) : 0;
        $includes = (!$error) ? count($result->includes) : 0;
        $excludes = (!$error) ? count($result->excludes) : 0;

        // Set messages
        if (!$debug && !empty($this->input->get('messages'))) {
            if ($error) {
                $app->enqueueMessage(
                    Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', Text::_($error)),
                    'error'
                );
            } else {
                $app->enqueueMessage(Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS', $all));
                $app->enqueueMessage(
                    Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS_EXCLUDES', $excludes),
                    'warning'
                );
                $app->enqueueMessage(
                    Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS_INCLUDES', $includes),
                    'notice'
                );
            }
        }

        // Set cookies
        if (!$debug && !empty($this->input->get('cookies'))) {
            $name    = 'jlsitemap_generation';
            $message = (!$error) ? Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS', $all) :
                Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', Text::_($error));
            $value   = new JsonResponse(array('all' => $all, 'includes' => $includes, 'excludes' => $excludes),
                $message,
                $error);
            $expires = Factory::getDate('+1 day')->toUnix();

            $this->input->cookie->set(
                $name,
                $value,
                $expires,
                $app->get('cookie_path', '/'),
                $app->get('cookie_domain'),
                $app->isSSLConnection()
            );
        }

        // Redirect
        if (!$debug && !empty($this->input->get('redirect'))) {
            $url = (!empty($this->input->get('return', null, 'base64'))) ?
                base64_decode($this->input->get('return', null, 'base64')) : Uri::root(true);

            $app->redirect(str_replace('&amp;', '&', $url));

            return (!$error);
        }

        // Json response
        if (!$debug && $this->input->get('response') == 'json') {
            $message = (!$error) ? Text::_('COM_JLSITEMAP_SITEMAP_GENERATION_SUCCESS') :
                Text::sprintf('COM_JLSITEMAP_SITEMAP_GENERATION_FAILURE', Text::_($error));

            echo new JsonResponse($result, $message, $error);;
            $app->close();

            return (!$error);
        }

        // Debug
        if ($debug) {
            if ($error) {
                throw new \Exception($error);
            }

            echo LayoutHelper::render('components.jlsitemap.site.debug', $result);

            $app->close();

            return (!$error);
        }

        return (!$error);
    }

    /**
     * Method to check access
     *
     * @throws  \Exception
     *
     * @since  1.6.0
     */
    protected function checkAccess()
    {
        $app    = Factory::getApplication();
        $access = false;

        // Check access key
        $access_key = ComponentHelper::getComponent('com_jlsitemap')->getParams()->get('access_key');
        if (!empty($access_key) && $access_key == $this->input->get('access_key')) {
            $access = true;
        }

        // Check can manage component
        if (!$access && Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_jlsitemap')) {
            $access = true;
        }

        // Check if server request
        if (!$access && $this->input->server->get('SERVER_ADDR') == $this->input->server->get('REMOTE_ADDR')) {
            $access = true;
        }

        // Trow if don't has access
        if (!$access) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    /**
     * Method to check current language.
     *
     * @throws  \Exception
     *
     * @since  1.5.1
     */
    protected function checkLanguage()
    {
        if (Multilanguage::isEnabled()) {
            $currentTag = Factory::getApplication()->getLanguage()->getTag();
            $defaultTag = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
            $languages  = LanguageHelper::getLanguages('lang_code');
            $defaultSef = (!empty($languages[$defaultTag])) ? $languages[$defaultTag]->sef : false;
            if ($currentTag !== $defaultTag && $defaultSef) {
                $uri = Uri::getInstance();
                $uri->setVar('lang', 'en');
                Factory::getApplication()->redirect($uri->toString());
            }
        }
    }

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
     *
     * @since  1.4.1
     */
    public function getModel($name = 'Sitemap', $prefix = 'Site', $config = [])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Method to generate sitemap.
     *
     * @return  bool True on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  1.4.1
     */
    public function delete()
    {
        $app   = Factory::getApplication();
        $debug = (!empty($this->input->get('debug')));
        $model = $this->getModel();
        $error = ($model->delete()) ? $model->getError() : false;

        // Set messages
        if (!$debug && !empty($this->input->get('messages'))) {
            if ($error) {
                $app->enqueueMessage(Text::sprintf('COM_JLSITEMAP_SITEMAP_DELETE_FAILURE', Text::_($error)), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_JLSITEMAP_SITEMAP_DELETE_SUCCESS'));
            }
        }

        // Set cookies
        if (!$debug && !empty($this->input->get('cookies'))) {
            $name    = 'jlsitemap_delete';
            $message = (!$error) ? Text::_('COM_JLSITEMAP_SITEMAP_DELETE_SUCCESS') :
                Text::sprintf('COM_JLSITEMAP_SITEMAP_DELETE_FAILURE', Text::_($error));
            $value   = new JsonResponse('', $message, $error);
            $expires = Factory::getDate('+1 day')->toUnix();

            $this->input->cookie->set(
                $name,
                $value,
                $expires,
                $app->get('cookie_path', '/'),
                $app->get('cookie_domain'),
                $app->isSSLConnection()
            );
        }

        // Redirect
        if (!$debug && !empty($this->input->get('redirect'))) {
            $url = (!empty($this->input->get('return', null, 'base64'))) ?
                base64_decode($this->input->get('return', null, 'base64')) : Uri::root(true);

            $app->redirect(str_replace('&amp;', '&', $url));

            return (!$error);
        }

        // Json response
        if ($this->input->get('response') == 'json') {
            $message = (!$error) ? Text::_('COM_JLSITEMAP_SITEMAP_DELETE_SUCCESS') :
                Text::sprintf('COM_JLSITEMAP_SITEMAP_DELETE_FAILURE', Text::_($error));

            echo new JsonResponse('', $message, $error);;
            $app->close();

            return (!$error);
        }

        return (!$error);
    }

    /**
     * Method to get Sitemap stylesheet.
     *
     * @throws  \Exception
     *
     * @since      1.9.0
     *
     * @deprecated 1.11
     */
    public function getStylesheet()
    {
        // Set xml response
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/xml; charset=utf-8', true);
        $app->sendHeaders();

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo LayoutHelper::render(
            'components.jlsitemap.xsl.' . $this->input->get('type', 'urlset'),
            array('date' => Factory::getApplication()->input->get('date', false, 'text'))
        );
        $app->close();

        return true;
    }
}