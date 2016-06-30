<?php
namespace Bolt\Extension\Verraedt\Translate\Storage\Field;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A collection of translated fields, indexed by the locale
 */
class TranslatedFieldCollection extends ArrayCollection
{
    public function __construct(array $array) {
        parent::__construct($array);
    }

    public function __toString() {
        global $app;

        // Get current locale
        $locale = $app['config']->get('general/locale');
        $value = $this->get($locale);

        return "$value";
    }
}
