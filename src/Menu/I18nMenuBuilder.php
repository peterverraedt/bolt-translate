<?php

namespace Bolt\Extension\Verraedt\Translate\Menu;

use Silex\Application;
use Bolt\Menu\MenuBuilder;
use Bolt\Translation\Translator as Trans;

class I18nMenuBuilder extends MenuBuilder 
{
    private $app;

    public function __construct(Application $app) 
    {
        $this->app = $app;
        parent::__construct($app);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $menu)
    {
        $menu = parent::resolve($menu);
        $menu = $this->translateMenu($menu);
        return $menu;
    }

    private function translateMenu($menu) {
        foreach ($menu as $k => $item) {
            if (isset($item['label'])) {
                $menu[$k]['label'] = Trans::__($item['label']);
            }
            if (isset($item['title'])) {
                $menu[$k]['title'] = Trans::__($item['title']);
            }
            if (isset($item['submenu'])) {
                $menu[$k]['submenu'] = $this->translateMenu($item['submenu']);
            }
        }
        return $menu;
    }
}
