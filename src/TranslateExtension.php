<?php

namespace Bolt\Extension\Verraedt\Translate;

use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Extension\Verraedt\Translate\Controller\I18nFrontend;
use Bolt\Extension\Verraedt\Translate\Controller\I18nRequirement;
use Bolt\Extension\Verraedt\Translate\Controller\BackendController;

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
        $app['i18n_controller.frontend'] = $app->share(
            function ($app) {
                $frontend = new I18nFrontend();
                $frontend->connect($app);
                return $frontend;
            }
        );

        $app['i18n_controller.requirement'] = $app->share(
            function ($app) {
                $requirement = new I18nRequirement($app['config']);
                return $requirement;
            }
        );

    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
        #    '/' => new BackendController(),
        ];
    }
}
