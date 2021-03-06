<?php
namespace Bolt\Extension\Verraedt\Translate\Storage\Field\Type;

use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class I18nHtmlType extends I18nTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'html';
    }

    /**
     * Returns the name of the Doctrine storage type to use for a field.
     *
     * @return Type
     */
    public function getStorageType()
    {
        return Type::getType('text');
    }
}
