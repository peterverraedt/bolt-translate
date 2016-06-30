<?php
namespace Bolt\Extension\Verraedt\Translate\Storage\Field\Type;

use Bolt\Exception\FieldConfigurationException;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\NamingStrategy;
use Bolt\Storage\QuerySet;
use Bolt\Storage\Field\Type\FieldTypeBase;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\Common\Collections\ArrayCollection;
use Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Peter Verraedt <peter@verraedt.be>
 */
class I18nType extends FieldTypeBase
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
        
        $query->addSelect($this->getPlatformGroupConcat($field, $query))
            ->leftJoin(
                $alias,
                $namingStrategy->classToTableName('field_translation'),
                $dummy,
                $dummy . ".content_id = $alias.id AND " . $dummy . ".contenttype='$boltname' AND " . $dummy . ".fieldname = '$field'"
            );
    }

    public function persist(QuerySet $queries, $entity, EntityManager $em = null)
    {
        global $app;

        // Get value from form
        $key = $this->mapping['fieldname'];
        $values = $entity->get($key);

        if ($values instanceof ArrayCollection) {
            $values = $values->toArray();
        }

        // Lookup actual type of field
        $typename = $this->mapping['data']['subtype'];

        // Lookup handler for field
        $mapping = $this->mapping;
        $mapping['data']['type'] = $mapping['data']['subtype'];
        $handler = NULL;
        foreach ($app['storage.typemap'] as $handler_class) {
            $possible_handler = new $handler_class($mapping, $this->em);
            if ($possible_handler->getName() == $typename) {
                $handler = $possible_handler;
            }
        }

        // Find out about the storage type of the field
        if (!is_null($handler)) 
        {
            $type = $handler->getStorageType();
        }
        else {
            $type = 'string'; // Good guess
        }

        // Convert value to database value or default value
        foreach ($values as $locale => $value) {
            if (null !== $value) {
                $values[$locale] = $type->convertToDatabaseValue($value, $this->getPlatform());
            } elseif (isset($this->mapping['default'])) {
                $values[$locale] = $this->mapping['default'];
            }
        }

        // Save values in the repository
        $repo = $this->em->getRepository('Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation');

        $existing_fields = $repo->getExistingFields( $entity->getId(), $entity->getContenttype(), $key);

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
            $field->setContent_id($entity->getId());
            $field->setValue($new_value, $type->getName());
            $repo->save($field);
        }

        foreach ($existing_fields as $old_field_id) {
            $repo->delete($repo->find($old_field_id));
        }
    }

    /**
     * Translation of comma-separated values 'locale_id' into a TranslatedFieldCollection
     */
    public function hydrate($data, $entity)
    {
        global $app;

        $key = $this->mapping['fieldname'];

        // Lookup actual type of field
        $typename = $this->mapping['data']['subtype'];

        // Lookup handler for field
        $mapping = $this->mapping;
        $mapping['data']['type'] = $mapping['data']['subtype'];
        $handler = NULL;
        foreach ($app['storage.typemap'] as $handler_class) {
            $possible_handler = new $handler_class($mapping, $this->em);
            if ($possible_handler->getName() == $typename) {
                $handler = $possible_handler;
            }
        }

        // Find out about the storage type of the field
        if (!is_null($handler)) 
        {
            $type = $handler->getStorageType();
        }
        else {
            $type = 'string'; // Good guess
        }

        // Find entries in repository
        $repo = $this->em->getRepository('Bolt\Extension\Verraedt\Translate\Storage\Entity\FieldTranslation');

        $parse = (string) $data[$key];
        if (substr($parse, 0, 1) == '{') {
            $parse = substr($parse, 1, -1);
        }
        
        $values = [];
        foreach (explode(',', $parse) as $fieldKey) {
            if ($fieldKey != 'Array') {
                $split = explode('_', $fieldKey);
                $id = array_pop($split);
                $locale = join('_', $split);
                
                if (is_numeric($id)) {
                    $id = (int) $id;
                
                    $field = $repo->find($id);
                    if ($field) {
                        $val = $field->getValue($type->getName());
                        if ($val !== null) {
                            $values[$locale] = $type->convertToPHPValue($val, $this->getPlatform());
                        }
                    }
                }
            }
        }
        
        $collection = new ArrayCollection($values);

        $this->set($entity, $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'i18n';
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType() 
    {
        // Dummy field, is actually unused, but provide it anyway to fix some trouble.
//        return Type::getType('text');
        return 'text';
    }

    public function getStorageOptions()
    {
        return [
            'default' => '',
        ];
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
     * Query to insert new field values.
     *
     * @param QuerySet $queries
     * @param array    $changes
     * @param $entity
     */
    protected function addToInsertQuery(QuerySet $queries, $changes, $entity)
    {
        die(__METHOD__ . ' called, unimplemented');
        foreach ($changes as $fieldValue) {
            $repo = $this->em->getRepository(get_class($fieldValue));
            $field = $this->getFieldType($fieldValue->getFieldname());
            $type = $field->getStorageType();
            $typeCol = 'value_' . $type->getName();

            $fieldValue->$typeCol = $fieldValue->getValue();
            $fieldValue->setFieldtype($this->getFieldTypeName($fieldValue->getFieldname()));
            $fieldValue->setContenttype((string) $entity->getContenttype());

            // This takes care of instances where an entity might be inserted, and thus not
            // have an id. This registers a callback to set the id parameter when available.
            $queries->onResult(
                function ($query, $result, $id) use ($repo, $fieldValue) {
                    if ($result === 1 && $id) {
                        $fieldValue->setContent_id($id);
                        $repo->save($fieldValue);
                    }
                }
            );
        }
    }

    /**
     * Query to delete existing field values.
     *
     * @param QuerySet $queries
     * @param $changes
     */
    protected function addToDeleteQuery(QuerySet $queries, $changes)
    {
    }

    /**
     * Query to insert new field values.
     *
     * @param QuerySet $queries
     * @param array    $changes
     * @param $entity
     */
    protected function addToUpdateQuery(QuerySet $queries, $changes, $entity)
    {
        die(__METHOD__ . ' called, unimplemented');

        foreach ($changes as $fieldValue) {
            $repo = $this->em->getRepository(get_class($fieldValue));
            $field = $this->getFieldType($fieldValue->getFieldname());
            $type = $field->getStorageType();
            $typeCol = 'value_' . $type->getName();
            $fieldValue->$typeCol = $fieldValue->getValue();

            // This takes care of instances where an entity might be inserted, and thus not
            // have an id. This registers a callback to set the id parameter when available.
            $queries->onResult(
                function ($query, $result, $id) use ($repo, $fieldValue) {
                    if ($result === 1) {
                        $repo->save($fieldValue);
                    }
                }
            );
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
