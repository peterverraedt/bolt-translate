<?php

namespace Bolt\Extension\Verraedt\Translate;

use Bolt\Configuration\ResourceManager;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\DatabaseSchemaTrait;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Storage\FieldManager;
use Bolt\Extension\Verraedt\Translate\Controller\I18nFrontend;
use Bolt\Extension\Verraedt\Translate\Controller\I18nRequirement;
use Bolt\Extension\Verraedt\Translate\Storage\Database\Schema\Table\FieldTranslation;
use Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nTextType;
use Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nHtmlType;
use Bolt\Extension\Verraedt\Translate\Routing\I18nUrlGeneratorWrapper;
use Bolt\Extension\Verraedt\Translate\Menu\I18nMenuBuilder;
use Bolt\Menu\MenuEntry;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;

/**
 * ExtensionName extension class.
 *
 * @author Peter Verraedt <peter@verraedt.be>
 */
class TranslateExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;
 
    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        // Add services to serve i18n content
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

        // Override fields with i18n-aware variants
        $app['storage.typemap'] = array_merge(
            $app['storage.typemap'],
            [
                'text' => 'Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nTextType',
                'html' => 'Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nHtmlType',
            ]
        );

        $app['storage.field_manager'] = $app->share(
            $app->extend(
                'storage.field_manager',
                function (FieldManager $manager) use ($app) {
                    // Modify FieldManager
                    $manager->addFieldType('text', new I18nTextType());
                    $manager->addFieldType('html', new I18nHtmlType());

                    return $manager;
                }
            )
        );

        // Add translated fields table and entities/repository
        $this->extendDatabaseSchemaServices();

        $app['storage']->getMapper()->setDefaultAlias('bolt_field_translation', 'Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation');
        $app['storage']->setRepository('Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation', 'Bolt\Extension\Verraedt\Translate\Storage\Repository\FieldTranslationRepository');

        // Support translation of menu items
        $app['menu'] = new I18nMenuBuilder($app);

        // Add menu items for translations in other locales
        $app['menu.admin'] = $app->share(
            $app->extend(
                'menu.admin',
                 function (MenuEntry $menus) use ($app) {
                    $all_locales = $app['config']->get('general/locales', NULL);

                    $translations = $menus->get('translations');

                    foreach (array('tr_messages' => 'messages', 'tr_long_messages' => 'infos', 'tr_contenttypes' => 'contenttypes') as $id => $path) {
                        $submenu = $translations->get($id);

                        foreach ($all_locales as $locale => $info) {
                            $submenu->add((new MenuEntry($locale, '/bolt/tr/' . $path . '/' . $locale))
                                ->setLabel($info['label'])
                                ->setPermission('translation'));
                        }
                    }
                    return $menus; 
                }
            )
        );

        // Register session based locale switcher for backend
        $app->before(function (Request $request) use ($app) 
            {
                switch ($request->get('zone')) {
                    case 'backend':            
                            // Determine current locale
                            if (!is_null($request->query->get('locale'))) {
                                $locale = $request->query->get('locale');
                            }
                            elseif (!is_null($request->getSession()->get('backend_locale'))) {
                                $locale = $request->getSession()->get('backend_locale');
                            }
                            else {
                                $locale = $app['config']->get('general/locale', 'en_GB');
                            }

                            // Save locale in session
                            $request->getSession()->set('backend_locale', $locale);

                            // Change locale for current request
                            static::setLocale($app, $locale);
                        break;

                    default:
                }
            }
        );
    }

    /**
     * Change locale of current application
     */
    public static function setLocale($app, $locale)
    {
        if ($locale != $app['locale']) {
            $app['config']->set('general/locale', $locale);
            $app['locale'] = $locale;

            $app->initLocale();
            
            // Reset translator
            $app['translator'] = new Translator($locale, new MessageSelector());
            $app['translator']->setFallbackLocales($app['locale_fallbacks']);
            foreach ($app['translator.loaders'] as $str => $loader) {
                $app['translator']->addLoader($str, $loader);
            }
            $resources = \Bolt\Provider\TranslationServiceProvider::addResources($app, $locale);
            foreach ($resources as $resource) {
                call_user_func_array(array($app['translator'], 'addResource'), $resource);
            }
            foreach ($app['locale_fallbacks'] as $fallback) {
                if ($fallback !== $locale) {
                    $resources = \Bolt\Provider\TranslationServiceProvider::addResources($app, $fallback);
                    foreach ($resources as $resource) {
                        call_user_func_array(array($app['translator'], 'addResource'), $resource);
                    }
                }
            }
        } 
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        // Ensure _locale is always set in url_generator
        $default_locale = $app['config']->get('general/locale', 'en_GB');
        $all_locales = $app['config']->get('general/locales', NULL);
        
        $slug = 'en';
        if (isset($all_locales[$default_locale]['slug'])) {
            $slug = $all_locales[$default_locale]['slug'];
        }
        elseif ($all_locales) {
            $locale = array_keys($all_locales)[0];
            if (isset($all_locales[$locale]['slug'])) {
                $slug = $all_locales[$locale]['slug'];
            }
        }

        $app['url_generator']->getContext()->setParameters(['_locale' => $slug]);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates' => ['position' => 'prepend', 'namespace' => 'bolt']
            ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables()
    {
        return [
            'field_translation' => FieldTranslation::class,
            ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions() 
    {
        return [
            'lang' => 'getLocaleSlug',
            ];
    }


    public function getLocaleSlug()
    {
        $app = ResourceManager::getApp();

        $all_locales = $app['config']->get('general/locales', NULL);
        $current_locale = $app['config']->get('general/locale', 'en_GB');

        if (!isset($all_locales[$current_locale])) {
            $current_locale = array_keys($all_locales)[0];
        }
        if (isset($all_locales[$current_locale]['slug'])) {
            return $all_locales[$current_locale]['slug'];
        }
        return 'en';
    }
}
