<?php
namespace Bolt\Extension\Verraedt\Translate\Storage\Field;

use Bolt\Configuration\ResourceManager;
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
        $app = ResourceManager::getApp();

        // Get current locale
        $locale = $app['config']->get('general/locale');
        $value = $this->get($locale);

        return "$value";
    }
}
