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
        $locales = $this->config->get('general/locales');
        $accepted = array();
        foreach ($locales as $locale => $info) {
            $accepted[] = $info['slug'];
            $accepted[] = $locale; // XXX Fallback
        }

        return implode('|', $accepted);
    }
} 
