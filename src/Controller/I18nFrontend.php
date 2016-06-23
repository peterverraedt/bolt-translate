<?php

namespace Bolt\Extension\Verraedt\Translate\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
    public function redirect(Request $request)
    {
        $default_locale = $this->getOption('general/locale', 'en_GB');
        
        $locales = array(
            $default_locale,
        );
        
        $all_locales = $this->getOption('general/locales', NULL);
        foreach ($all_locales as $id => $info) {
            $locales[] = $id;
            $locales[] = $info['slug'];
        }

        $redirect = $this->app['paths']['root'] . $request->getPreferredLanguage($locales);
            
        return $this->app->redirect($redirect);
    }
}
