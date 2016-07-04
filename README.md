Translate Extension
===================

## Setup

1. Install the extension, e.g. locally under `extensions/local/verraedt/translate`, and do a database update.
2. Add the `locales` block to the main configuration with your locales, the first one is the default locale. Do not remove the setting `locale`.

        locales:
            en_GB:
                label: English
                slug: en
            nl_NL:
                label: Nederlands
                slug: nl

3. Edit `routing.yml` in the following way:
   * Replace all occurances of `controller.frontend` by `i18n_controller.frontend`.
   * Add the parameter `{_locale}` to each path, and add the requirement `_locale: i18n_controller.requirement:anyLocale` to each item:

            [...]
            contentlink:
                path: '/{_locale}/{contenttypeslug}/{slug}'
                defaults:
                    _controller: i18n_controller.frontend:record
                    _locale: nl
                requirements:
                    contenttypeslug: i18n_controller.requirement:anyContentType
                    _locale: i18n_controller.requirement:anyLocale
            [...]

   * Add a new route for `/`, redirecting visitors to the right language: 

            redirect:
                path: /
                defaults:
                    _controller: i18n_controller.frontend:redirectLanguage

    Example configuration after these changes:

        redirect:
            path: /
            defaults:
                _controller: i18n_controller.frontend:redirectLanguage
                
        homepage:
            path: /{_locale}
            defaults:
                _controller: i18n_controller.frontend:homepage
                _locale: nl
            requirements:
                _locale: i18n_controller.requirement:anyLocale
     
        search:
            path: /{_locale}/search
            defaults:
                _controller: i18n_controller.frontend:search
                _locale: nl
            requirements:
                _locale: i18n_controller.requirement:anyLocale
     
        preview:
            path: /{_locale}/preview/{contenttypeslug}
            defaults:
                _controller: i18n_controller.frontend:preview
                _locale: nl
            requirements:
                contenttypeslug: i18n_controller.requirement:anyContentType
                _locale: i18n_controller.requirement:anyLocale
                
        contentlink:
            path: '/{_locale}/{contenttypeslug}/{slug}'
            defaults:
                _controller: i18n_controller.frontend:record
                _locale: nl
            requirements:
                contenttypeslug: i18n_controller.requirement:anyContentType
                _locale: i18n_controller.requirement:anyLocale
     
        taxonomylink:
            path: /{_locale}/{taxonomytype}/{slug}
            defaults:
                _controller: i18n_controller.frontend:taxonomy
                _locale: nl
            requirements:
                taxonomytype: i18n_controller.requirement:anyTaxonomyType
                _locale: i18n_controller.requirement:anyLocale
     
        contentlisting:
            path: /{_locale}/{contenttypeslug}
            defaults:
                _controller: i18n_controller.frontend:listing
                _locale: nl
            requirements:
                contenttypeslug: i18n_controller.requirement:pluralContentTypes
                _locale: i18n_controller.requirement:anyLocale

4. For each contenttype that needs (partial) translation, add `class: \Bolt\Extension\Verraedt\Translate\Legacy\I18nContent # To be removed in Bolt v4.0` to the contenttype and add `i18n: true` to each field that should be translated. See below for currently supported fields.

    Example configuration:
 
        pages:
            name: Pages
            singular_name: Page
            class: \Bolt\Extension\Verraedt\Translate\Legacy\I18nContent # To be removed in Bolt v4.0 
            fields:
                title:
                    type: text
                    i18n: true
                    class: large
                    group: content
                slug:
                    type: slug
                    uses: title
                image:
                    type: image
                teaser:
                    type: html
                    i18n: true
                    height: 150px
                body:
                    type: html
                    i18n: true
                    height: 300px
                template:
                    type: templateselect
                    filter: '*.twig'

5. Change the paths used in the theme: Find all occurances of `{{ paths.root }}` and change them to `{{ paths.root }}{{ lang() }}/`.

6. Using the administration interface, you can add several translation strings under `http://<website>/bolt/tr` for the default locale (the one specified under `locale` in your configuration). To change translations for the other locales, you can currently manually change the default locale in the configuration file to open the administration interface in the other locales.

7. To translate the menus, you can change the items in the menu to use `route:` instead of `path:`, e.g.:

        main:
            - label: Home
              title: This is the first menu item.
              route: homepage
              class: first
            - label: File
              route: contentlink
              param:
                  contenttypeslug: page
                  slug: entry-1
              submenu:
                  - label: Sub 1
                    route: contentlink
                    param:
                        contenttypeslug: page
                        slug: entry-2
                  - label: Sub 2
                    class: menu-item-class
                    route: contentlink
                    param:
                        contenttypeslug: page
                        slug: entry-3
                  - label: Sub 3
                    route: contentlink
                    param:
                        contenttypeslug: page
                        slug: entry-4
                  - label: Sub 4
                    class: sub-class
                    route: contentlink
                    param:
                        contenttypeslug: page
                        slug: entry-5
            - label: All pages
              route: contentlisting
              param:
                  contenttypeslug: pages
            - label: The Bolt site
              link: http://bolt.cm
              class: last

   Translation for `label` and `title` is supported, just add those items in the translation files.

## Currently supported fields 

   * `text`
   * `html`
