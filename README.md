Translate Extension
===================

## Setup

1. Install the extension under `extensions/local/verraedt/translate`.
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
            requirements:
                _locale: i18n_controller.requirement:anyLocale
     
        search:
            path: /{_locale}/search
            defaults:
                _controller: i18n_controller.frontend:search
            requirements:
                _locale: i18n_controller.requirement:anyLocale
     
        preview:
            path: /{_locale}/preview/{contenttypeslug}
            defaults:
                _controller: i18n_controller.frontend:preview
            requirements:
                contenttypeslug: i18n_controller.requirement:anyContentType
                _locale: i18n_controller.requirement:anyLocale
                
        contentlink:
            path: '/{_locale}/{contenttypeslug}/{slug}'
            defaults:
                _controller: i18n_controller.frontend:record
            requirements:
                contenttypeslug: i18n_controller.requirement:anyContentType
                _locale: i18n_controller.requirement:anyLocale
     
        taxonomylink:
            path: /{_locale}/{taxonomytype}/{slug}
            defaults:
                _controller: i18n_controller.frontend:taxonomy
            requirements:
                taxonomytype: i18n_controller.requirement:anyTaxonomyType
                _locale: i18n_controller.requirement:anyLocale
     
        contentlisting:
            path: /{_locale}/{contenttypeslug}
            defaults:
                _controller: i18n_controller.frontend:listing
            requirements:
                contenttypeslug: i18n_controller.requirement:pluralContentTypes
                _locale: i18n_controller.requirement:anyLocale

4. Change the field types for translated fields in `contenttypes.yml` into `i18n` and put the original fieldtype under the key `subtype`. For supported field types, see below.

5. Do a database update.
