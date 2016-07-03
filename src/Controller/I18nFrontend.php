<?php

namespace Bolt\Extension\Verraedt\Translate\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Controller\Frontend;

class I18nFrontend extends Frontend 
{
    /** @var Application */
    protected $app;

    /**
     * Controller for language redirect page
     *
     * @param Request $request
     *
     * @return BoltResponse
     */
    public function redirectLanguage(Request $request)
    {
        $default_locale = $this->getOption('general/locale', 'en_GB');
        
        $locales = array(
            $default_locale => substr($default_locale, 0, 2),
        );

        $all_locales = $this->getOption('general/locales', NULL);
        foreach ($all_locales as $id => $info) {
            $locales[$id] = $info['slug'];
            $locales[$info['slug']] = $info['slug'];
        }

        $redirect = $this->app['paths']['root'] . $locales[$request->getPreferredLanguage(array_keys($locales))];
            
        return $this->app->redirect($redirect);
    }

    /**
     * Change language
     *
     * @param string $_locale
     */
    protected function setLanguage($_locale, $request) {
        if ($_locale) {
            $all_locales = $this->getOption('general/locales', NULL);

            $found = FALSE;
            foreach ($all_locales as $id => $info) {
                if ($info['slug'] == $_locale) {
                    $this->app['config']->set('general/locale', $id);
                    $this->app['locale'] = $id;
                    $found = TRUE;
                }
            }

            // Fallback if locale instead of slug is used
            if (!$found && isset($all_locales[$_locale])) {
                $this->app['config']->set('general/locale', $_locale);
                if ($request->getMethod() == 'GET') {
                    $uri = str_replace('/' . $_locale . '/', '/' . $all_locales[$_locale]['slug'] . '/', $request->getRequestUri());
                    if ($uri != $request->getRequestUri()) {
                        return $this->app->redirect($uri);
                    }
                }
            }

            if ($found) {
                $this->app->initLocale();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function homepage(Request $request, $_locale = '') 
    {
        $redirect = $this->setLanguage($_locale, $request);
        if ($redirect) {
            return $redirect;
        }
        return parent::homepage($request);
    }

    /**
     * {@inheritdoc}
     */
    public function record(Request $request, $contenttypeslug, $slug = '', $_locale = '')
    {
        $redirect = $this->setLanguage($_locale, $request);
        if ($redirect) {
            return $redirect;
        }
        return parent::record($request, $contenttypeslug, $slug);
    }
    
    /**
     * {@inheritdoc}
     */
    public function preview(Request $request, $contenttypeslug, $_locale = '')
    {
        $redirect = $this->setLanguage($_locale, $request);
        if ($redirect) {
            return $redirect;
        }
        return parent::preview($request, $contenttypeslug);
    }
     
    /**
     * {@inheritdoc}
     */
    public function listing(Request $request, $contenttypeslug, $_locale = '')
    {
        $redirect = $this->setLanguage($_locale, $request);
        if ($redirect) {
            return $redirect;
        }
        return parent::listing($request, $contenttypeslug);
    }
     
    /**
     * {@inheritdoc}
     */
    public function taxonomy(Request $request, $taxonomytype, $slug, $_locale = '')
    {
        $redirect = $this->setLanguage($_locale, $request);
        if ($redirect) {
            return $redirect;
        }
        return parent::taxonomy($request, $taxonomytype, $slug);
    }
     
    /**
     * {@inheritdoc}
     */
    public function search(Request $request, array $contenttypes = null, $_locale = '')
    {
        $redirect = $this->setLanguage($_locale, $request);
        if ($redirect) {
            return $redirect;
        }
        return parent::search($request, $contenttypes);
    }
}
