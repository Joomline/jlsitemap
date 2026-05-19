<?php
/**
 * @package    JLSitemap Component
 * @version    @version@
 * @author     Joomline - joomline.ru
 * @copyright  Copyright (c) 2010 - 2022 Joomline. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://joomline.ru/
 */

namespace Joomla\Component\JLSitemap\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\JLSitemap\Administrator\Helper\SecretsHelper;
use Joomla\Registry\Registry;

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
        $app   = Factory::getApplication();
        $debug = (!empty($this->input->get('debug', '')));
        $json  = $this->input->getCmd('response') === 'json';

        if ($debug) {
            $redirect = [
                'option'     => 'com_jlsitemap',
                'task'       => 'sitemap.generate',
                'access_key' => $this->getAccessKey(),
                'messages'   => 0,
                'cookies'    => 0,
                'redirect'   => 0,
                'debug'      => 1,
            ];

            $app->redirect($this->getSiteEntryPointUrl($redirect));

            return true;
        }

        $redirect = [
            'option'     => 'com_jlsitemap',
            'task'       => 'sitemap.generate',
            'access_key' => $this->getAccessKey(),
            'messages'   => $json ? 0 : 1,
            'cookies'    => $json ? 0 : 1,
            'redirect'   => $json ? 0 : 1,
        ];

        if ($json) {
            $redirect['response'] = 'json';
        } else {
            $redirect['return'] = base64_encode(Route::_('index.php?option=com_jlsitemap', false));
        }

        $app->redirect($this->getSiteEntryPointUrl($redirect));

        return true;
    }

    /**
     * Method to get component access key.
     *
     * @return  string Access key.
     *
     * @since  1.4.1
     */
    protected function getAccessKey()
    {
        return SecretsHelper::getAccessKey();
    }

    /**
     * Method to build a site entry point URL from the administrator application.
     *
     * @param   array  $query  Query parameters.
     *
     * @return  string
     *
     * @since  2.1.1
     */
    protected function getSiteEntryPointUrl(array $query): string
    {
        return rtrim(Uri::root(true), '/') . '/index.php?' . http_build_query($query);
    }

    /**
     * Method to get a non-empty cookie path.
     *
     * @return  string
     *
     * @since  2.1.1
     */
    protected function getCookiePath(): string
    {
        $path = (string) Factory::getApplication()->get('cookie_path', '/');

        return ($path !== '') ? $path : '/';
    }

    /**
     * Method to get cookie domain.
     *
     * @return  string
     *
     * @since  2.1.1
     */
    protected function getCookieDomain(): string
    {
        return (string) Factory::getApplication()->get('cookie_domain', '');
    }

    /**
     * Method to get cookie options.
     *
     * @param   int  $expires  Cookie expiration timestamp.
     *
     * @return  array
     *
     * @since  2.1.1
     */
    protected function getCookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path'     => $this->getCookiePath(),
            'domain'   => $this->getCookieDomain(),
            'secure'   => Factory::getApplication()->isSSLConnection(),
        ];
    }

    /**
     * Method to delete sitemap.
     *
     * @return  bool True on success, False on failure.
     *
     * @throws  \Exception
     *
     * @since  1.4.1
     */
    public function delete()
    {
        $app    = Factory::getApplication();
        $cookie = 'jlsitemap_delete';
        $result = $this->input->cookie->get($cookie, false, 'raw');
        $json   = $this->input->getCmd('response') === 'json';

        if ($json) {
            $model = $app
                ->bootComponent('com_jlsitemap')
                ->getMVCFactory()
                ->createModel('Sitemap', 'Site', ['ignore_request' => true]);

            $success = $model->delete();
            $message = $success ? Text::_('COM_JLSITEMAP_SITEMAP_DELETE_SUCCESS') : Text::_('COM_JLSITEMAP_SITEMAP_DELETE_FAILURE');

            if (!$success) {
                echo new JsonResponse(null, $message, true);
                $app->close();
            }

            echo new JsonResponse(
                [
                    'messages' => [
                        ['type' => 'message', 'text' => $message],
                    ],
                ]
            );
            $app->close();
        }

        // Redirect to site controller
        if (!$result) {
            // Prepare redirect
            $redirect = array(
                'option'     => 'com_jlsitemap',
                'task'       => 'sitemap.delete',
                'access_key' => $this->getAccessKey(),
                'messages'   => 0,
                'cookies'    => 1,
                'redirect'   => 1,
                'return'     => base64_encode(Route::_('index.php?option=com_jlsitemap&task=sitemap.delete'))
            );

            $app->redirect($this->getSiteEntryPointUrl($redirect));

            return true;
        }

        // Get Response
        $response = new Registry($result);
        $message  = ($response->get('success')) ? Text::_('COM_JLSITEMAP_SITEMAP_DELETE_SUCCESS') :
            Text::_('COM_JLSITEMAP_SITEMAP_DELETE_FAILURE');

        // Remove cookie
        $this->input->cookie->set(
            $cookie,
            '',
            $this->getCookieOptions(Factory::getDate('-1 day')->toUnix())
        );

        // Set error
        if (!$response->get('success')) {
            $this->setError($message);
            $this->setMessage($message, 'error');
            $this->setRedirect('index.php?option=com_jlsitemap');

            return false;
        }

        // Set success
        $this->setMessage($message);
        $this->setRedirect('index.php?option=com_jlsitemap');

        return true;
    }
}
