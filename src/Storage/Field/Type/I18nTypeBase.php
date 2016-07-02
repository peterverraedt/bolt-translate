<?php
namespace Bolt\Extension\Verraedt\Translate\Storage\Field\Type;

use Bolt\Configuration\ResourceManager;
use Bolt\Exception\FieldConfigurationException;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\NamingStrategy;
use Bolt\Storage\QuerySet;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\Field\Type\FieldTypeBase;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation;
use Bolt\Extension\Verraedt\Translate\Storage\Field\TranslatedFieldCollection;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Peter Verraedt <peter@verraedt.be>
 */
abstract class I18nTypeBase extends FieldTypeBase
{
    /**
     * For translated fields, the load method adds extra joins and selects to the query that
     * fetches the related records from the field and field value tables in the same query as the content fetch.
     *
     * @param QueryBuilder  $query
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        // If entity
        $i18n_enabled = isset($this->mapping['data']) && isset($this->mapping['data']['i18n']) && $this->mapping['data']['i18n'];

        if ($i18n_enabled) {
            $field = $this->mapping['fieldname'];
            $boltname = $metadata->getBoltName();

            $from = $query->getQueryPart('from');

            if (isset($from[0]['alias'])) {
                $alias = $from[0]['alias'];
            } else {
                $alias = $from[0]['table'];
            }

            $dummy = 'f_' . $field;

            $namingStrategy = new NamingStrategy();
            
            $query->addSelect($this->getPlatformGroupConcat($field . '_translations', $query))
                ->leftJoin(
                    $alias,
                    $namingStrategy->classToTableName('field_translation'),
                    $dummy,
                    $dummy . ".content_id = $alias.id AND " . $dummy . ".contenttype='$boltname' AND " . $dummy . ".fieldname = '$field'"
                );
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
    }

    public function persist(QuerySet $queries, $entity, EntityManager $em = null)
    {
        // If entity
        $i18n_enabled = isset($this->mapping['data']) && isset($this->mapping['data']['i18n']) && $this->mapping['data']['i18n'];
        
        if ($i18n_enabled) {
            // Get value from form
            $key = $this->mapping['fieldname'];

            if ($entity->get($key) instanceof TranslatedFieldCollection) {
                $values = $entity->get($key)->toArray();
            }
            else {
                $values = $entity->get($key . '_translations');
                $values['default'] = $entity->get($key);
            }

            // Find out about the storage type of the field
            $type = $this->getStorageType();

            // Convert value to database value or default value
            foreach ($values as $locale => $value) {
                if (null !== $value) {
                    $values[$locale] = $type->convertToDatabaseValue($value, $this->getPlatform());
                } elseif (isset($this->mapping['default'])) {
                    $values[$locale] = $this->mapping['default'];
                }
            }

            // Save default locale value
            $qb = &$queries[0];
            $qb->setValue($key, ':' . $key);
            $qb->set($key, ':' . $key);
            $qb->setParameter($key, $values['default']);
            unset($values['default']);

            // Save translated values in the repository
            $repo = $this->em->getRepository('Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation');
            $existing_fields = $repo->getExistingFields($entity->getId(), $entity->getContenttype(), $key);

            $queries->onResult(
                function ($query, $result, $id) use ($repo, $existing_fields, $values, $type, $entity, $key) {
                    foreach (array_intersect_key($existing_fields, $values) as $locale => $existing_field_id) {
                        $existing_field = $repo->find($existing_field_id);

                        $existing_field->setValue($values[$locale], $type->getName());
                        $repo->save($existing_field);

                        unset($values[$locale]);
                        unset($existing_fields[$locale]);
                    }

                    foreach ($values as $locale => $new_value) {
                        $field = new FieldTranslation();
                        $field->setLocale($locale);
                        $field->setFieldname($key);
                        $field->setFieldtype($type->getName());
                        $field->setContenttype((string) $entity->getContenttype());
                        $field->setContent_id($id);
                        $field->setValue($new_value, $type->getName());
                        $repo->save($field);
                    }

                    foreach ($existing_fields as $old_field_id) {
                        $repo->delete($repo->find($old_field_id));
                    }
                }
            );
        }
        else {
            parent::persist($queries, $entity, $em);
        }
    }

    /**
     * Translation of comma-separated values 'locale_id' into a TranslatedFieldCollection
     */
    public function hydrate($data, $entity)
    {
        $app = ResourceManager::getApp();

        $key = $this->mapping['fieldname'];

        // If entity
        $i18n_enabled = isset($this->mapping['data']) && isset($this->mapping['data']['i18n']) && $this->mapping['data']['i18n'];

        if (isset($data[$key . '_translations'])) {
            $i18n_enabled = TRUE; // All Fine
        }
        elseif (is_null($entity->getContenttype())) {
            // XXX Vuile hack (TM) XXX
            $backtrace = debug_backtrace()[1];
            if ($backtrace['class'] == 'Bolt\Legacy\Content' && $backtrace['function'] == 'setValue') {
                $object = $backtrace['object'];

                $entity->setContenttype($object->contenttype['tablename']);
                $entity->setId($object->id);
            }
            // XXX Vuile hack (TM) XXX
        }

        // If mapping is missing, but contenntype is present, find out i18n_enabled using contenttype and global configuration
        if (!$i18n_enabled && !isset($this->mapping['data']) && !is_null($entity->getContenttype())) {
            $contenttype_info = $app['config']->get('contenttypes/' . $entity->getContenttype(), NULL);
            $i18n_enabled = isset($contenttype_info['fields'][$key]['i18n']) && $contenttype_info['fields'][$key]['i18n'];
        }

        if ($i18n_enabled) {
            // Find out about the storage type of the field
            $type = $this->getStorageType();

            // Find entries in repository
            $repo = $this->em->getRepository('Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation');

            // Try to lookup from data
            $ids = array();

            if (isset($data[$key . '_translations'])) {
                $parse = (string) $data[$key . '_translations'];
                if (substr($parse, 0, 1) == '{') {
                    $parse = substr($parse, 1, -1);
                }
                $ids_pairs = explode(',', $parse);

                foreach ($ids_pairs as $fieldKey) {
                    if ($fieldKey != 'Array') {
                        $split = explode('_', $fieldKey);
                        $id = array_pop($split);
                        $locale = join('_', $split);
                        
                        if (is_numeric($id)) {
                            $ids[$locale] = (int) $id;
                        }
                    }
                }
            }
            else {
                // Try to lookup from repo
                $ids = $repo->getExistingFields($entity->getId(), $entity->getContenttype(), $key);
            }
        
            $values = [];
            
            foreach ($ids as $locale => $id) {
                $field = $repo->find($id);
                if ($field) {
                    $val = $field->getValue(); //$field->getValue($type->getName());
                    if ($val !== null) {
                        $values[$locale] = $type->convertToPHPValue($val, $this->getPlatform());
                    }
                }
            }

            // Find out default locale
            $locales = array_keys($app['config']->get('general/locales'));
            $default_locale = $locales[0];
    
            // Insert default locale value
            $val = isset($data[$key]) ? $data[$key] : null;
            if ($val !== null) {
                $value = $type->convertToPHPValue($val, $this->getPlatform());
                $values[$default_locale] = $value;
            }
           
            $collection = new TranslatedFieldCollection($values);

            $this->set($entity, $collection);
        }
        else {
            // Fallback if not translated field or extended options are not available
            parent::hydrate($data, $entity);
        }
    }

    /**
     * Get platform specific group_concat token for provided column
     *
     * @param string       $alias
     * @param QueryBuilder $query
     *
     * @return string
     */
    protected function getPlatformGroupConcat($alias, QueryBuilder $query)
    {
        $platform = $query->getConnection()->getDatabasePlatform()->getName();
        
        $field = $this->mapping['fieldname'];
        $dummy = 'f_' . $field;

        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT(DISTINCT CONCAT_WS('_', " . $dummy . '.locale, ' . $dummy . ".id)) as $alias";
            case 'sqlite':
                return 'GROUP_CONCAT(DISTINCT ' . $dummy . ".locale||'_'||" . $dummy . ".id) as $alias";
            case 'postgresql':
                return 'array_agg(DISTINCT ' . $dummy . ".locale||'_'||" . $dummy . ".id) as $alias";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate() 
    {
        return '@bolt/editcontent/fields/_i18n.twig';
    }
}
