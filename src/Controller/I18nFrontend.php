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
    protected function setLanguage($_locale) {
        //dump($this->app['locale']);

        if ($_locale) {
            $all_locales = $this->getOption('general/locales', NULL);

            $found = FALSE;
            foreach ($all_locales as $id => $info) {
                if ($info['slug'] == $_locale) {
                    $this->app['config']->set('general/locale', $id);
                    $found = TRUE;
                }
            }

            if (!$found && isset($all_locales[$_locale])) {
                $this->app['config']->set('general/locale', $_locale);
                $found = TRUE;
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
        $this->setLanguage($_locale);
        return parent::homepage($request);
    }

    /**
     * {@inheritdoc}
     */
    public function record(Request $request, $contenttypeslug, $slug = '', $_locale = '')
    {
        $this->setLanguage($_locale);
        return parent::record($request, $contenttypeslug, $slug);
    }
    
    /**
     * {@inheritdoc}
     */
    public function preview(Request $request, $contenttypeslug, $_locale = '')
    {
        $this->setLanguage($_locale);
        return parent::preview($request, $contenttypeslug);
    }
     
    /**
     * {@inheritdoc}
     */
    public function listing(Request $request, $contenttypeslug, $_locale = '')
    {
        $this->setLanguage($_locale);
        return parent::listing($request, $contenttypeslug);
    }
     
    /**
     * {@inheritdoc}
     */
    public function taxonomy(Request $request, $taxonomytype, $slug, $_locale = '')
    {
        $this->setLanguage($_locale);
        return parent::taxonomy($request, $taxonomytype, $slug);
    }
     
    /**
     * {@inheritdoc}
     */
    public function search(Request $request, array $contenttypes = null, $_locale = '')
    {
        $this->setLanguage($_locale);
        return parent::search($request, $contenttypes);
    }
}
