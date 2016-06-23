<?php

namespace Bolt\Extension\Verraedt\Translate;

use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Extension\Verraedt\Translate\Controller\I18nFrontend;

/**
 * ExtensionName extension class.
 *
 * @author Your Name <you@example.com>
 */
class TranslateExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $app['controller.i18n_frontend'] = $app->share(
            function ($app) {
                $frontend = new I18nFrontend();
                $frontend->connect($app);
                return $frontend;
            }
        );
    }
}
