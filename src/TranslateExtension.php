<?php

namespace Bolt\Extension\Verraedt\Translate;

use Bolt\Configuration\ResourceManager;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\DatabaseSchemaTrait;
use Silex\Application;
use Bolt\Storage\FieldManager;
use Bolt\Extension\Verraedt\Translate\Controller\I18nFrontend;
use Bolt\Extension\Verraedt\Translate\Controller\I18nRequirement;
use Bolt\Extension\Verraedt\Translate\Storage\Database\Schema\Table\FieldTranslation;
use Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nTextType;
use Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nHtmlType;
use Bolt\Extension\Verraedt\Translate\Routing\I18nUrlGeneratorWrapper;
use Bolt\Extension\Verraedt\Translate\Menu\I18nMenuBuilder;

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
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        // Ensure _locale is always set
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
