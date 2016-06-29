<?php

namespace Bolt\Extension\Verraedt\Translate;

use Bolt\Extension\SimpleExtension;
use Bolt\Extension\DatabaseSchemaTrait;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Storage\Field\Base;
use Bolt\Extension\Verraedt\Translate\Controller\I18nFrontend;
use Bolt\Extension\Verraedt\Translate\Controller\I18nRequirement;
use Bolt\Extension\Verraedt\Translate\Controller\BackendController;
use Bolt\Extension\Verraedt\Translate\Storage\Database\Schema\Table\FieldTranslation;

use Bolt\Storage\FieldManager;
use Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nType;

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

        $app['storage.typemap'] = array_merge(
            $app['storage.typemap'],
            [
                'i18n' => 'Bolt\Extension\Verraedt\Translate\Storage\Field\Type\I18nType',
            ]
        );

        $app['storage.field_manager'] = $app->share(
            $app->extend(
                'storage.field_manager',
                function (FieldManager $manager) use ($app) {
                    // Modify FieldManager
                    $manager->addFieldType('i18n', new I18nType());

                    // Modify Field\Manager
                    $field = new Base('i18n', '@bolt/_i18n.twig');
                    //$app['config']->getFields()->addField($field);
//                    $app['config']->getFields()->addDummyField('i18n');
                    
                    return $manager;
                }
            )
        );

        $this->extendDatabaseSchemaServices();

        $app['storage']->setRepository('Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation', 'Bolt\Extension\Verraedt\Translate\Storage\Repository\FieldTranslationRepository');
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
}
