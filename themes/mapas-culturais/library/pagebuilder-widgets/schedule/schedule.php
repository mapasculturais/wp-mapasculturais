<?php

/*
  Widget Name: Programação Geral
  Description: Lista geral de próximos eventos
  Author: hacklab/
  Author URI: https://hacklab.com.br
 */

namespace widgets;

class Schedule extends \SiteOrigin_Widget {

    function __construct() {
        $fields = [
            'title' => [
                'type' => 'text',
                'label' => 'Título',
                'default' => 'Programação Geral'
            ],
        ];

        parent::__construct('schedule', 'Programação Geral', [
            'panels_groups' => [WIDGETGROUP_BANNERS],
            'description' => 'Lista geral de próximos eventos'
        ], [], $fields, plugin_dir_path(__FILE__));
    }

    function get_template_name($instance) {
        return 'template';
    }

    function get_style_name($instance) {
        return 'style';
    }

}

Siteorigin_widget_register('schedule', __FILE__, 'widgets\Schedule');
