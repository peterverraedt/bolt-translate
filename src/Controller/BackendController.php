<?php

namespace Bolt\Extension\Verraedt\Translate\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * The controller for redirecting to language subsites.
 *
 * @author Peter Verraedt <peter@verraedt.be>
 */
class BackendController implements ControllerProviderInterface
{
    /** @var Application */
    protected $app;

    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $this->app = $app;

        /** @var ControllerCollection $ctr */
        $ctr = $app['controllers_factory'];

        #$ctr->match('/', [$this, 'callbackRoot']);
        //$ctr->match('/koala/{type}', [$this, 'callbackKoalaCatching']);

        $requirement = new I18nRequirement($app['config']);

        $ctr->match('/{_locale}', [$this, 'callbackLocalized'])
            ->assert('_locale', $requirement->anyLocale());

        $ctr->match('/{_locale}/{subpath}', [$this, 'callbackLocalized'])
            ->assert('_locale', $requirement->anyLocale())
            ->assert('subpath', '.*');

        return $ctr;
    }

    /**
     * @param Request $request
     */
    public function callbackRoot(Request $request)
    {
        #if ($type === 'dropbear') {
        #    return new Response('Drop bear sighted!', Response::HTTP_OK);
        #}

        #return new Response('Koala in a tree!', Response::HTTP_OK);

        $all_locales = $this->app['config']->get('general/locales', NULL);

        $locales = array();
        foreach ($all_locales as $id => $info) {
            $locales[] = $id;
            $locales[] = $info['slug'];
        }

        if ($locales) {
            return $this->app->redirect($this->app['paths']['root'] . $request->getPreferredLanguage($locales));
        }
        
        return new Response('No languages configured!', Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param string  $locale
     */
    public function callbackLocalized(Request $request, $_locale, $subpath = '') 
    {
        dump($locale);
        dump($subpath);

        $uri = $request->getUriForPath('/') . 'bolt/' . $subpath;
        
        $params = ($request->getMethod() == 'POST')
            ? $request->request->all()
            : $request->query->all();

        $subRequest = Request::create(
            $uri,
            $request->getMethod(),
            $params,
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all()
            );

        if ($request->getSession()) {
            $subRequest->setSession($request->getSession());
        }

        return $this->app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    }
}
