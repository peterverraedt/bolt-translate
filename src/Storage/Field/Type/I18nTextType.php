<?php
namespace Bolt\Extension\Verraedt\Translate\Storage\Field\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class I18nTextType extends I18nTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'text';
    }
}
