<?php

namespace Bolt\Extension\Verraedt\Translate\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Controller\Requirement;

class I18nRequirement extends Requirement 
{
    /** @var Application */
    protected $app;

    /**
     * Return locales.
     *
     * @return string
     */
    public function anyLocale()
    {
        return $this->configAssert('general/locales', false);
    }
} 
