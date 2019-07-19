<?php
define('WIDGETGROUP_MAIN', 'Widgets OutrasPalavras');
define('WIDGETGROUP_BANNERS', 'Widgets OutrasPalavras - Banners');

add_filter('siteorigin_panels_widget_dialog_tabs', function ($tabs) {

    $tabs[] = [
        'title' => WIDGETGROUP_MAIN,
        'filter' => [
            'groups' => [ WIDGETGROUP_MAIN ]
        ]
    ];

    $tabs[] = [
        'title' => WIDGETGROUP_BANNERS,
        'filter' => [
            'groups' => [ WIDGETGROUP_BANNERS ]
        ]
    ];

    return $tabs;
}, 20);


/** Add SiteOrigin Page Builder custom fields class prefixes */
add_filter('siteorigin_widgets_field_class_prefixes', function($class_prefixes) {
    $class_prefixes[] = 'OutrasPalavras_';
    return $class_prefixes;
});

/** Add SiteOrigin Page Builder custom fields */
add_filter('siteorigin_widgets_field_class_paths', function($class_paths) {
    $class_paths[] = get_template_directory() . '/library/pagebuilder-custom-fields/';
    return $class_paths;
});

/** Add SiteOrigin Page Builder custom widgets */
add_filter('siteorigin_widgets_widget_folders', function($folders) {
    $folders[] = get_template_directory() . '/library/pagebuilder-widgets/';
    return $folders;
});
