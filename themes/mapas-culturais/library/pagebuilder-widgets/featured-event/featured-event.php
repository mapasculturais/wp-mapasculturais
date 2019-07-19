<?php

/*
  Widget Name: Evento em Destaque
  Description: Exibição em destaque de um evento
  Author: hacklab/
  Author URI: https://hacklab.com.br
 */

namespace widgets;

class FeaturedEvent extends \SiteOrigin_Widget {

    function __construct() {
        $fields = [
            'event' => [
                'type' => 'link',
                'label' => 'Evento'
            ],
        ];

        parent::__construct('featured-event', 'Evento em Destaque', [
            'panels_groups' => [WIDGETGROUP_MAIN],
            'description' => 'Exibição em destaque de um evento'
        ], [], $fields, plugin_dir_path(__FILE__));
    }

    function get_template_name($instance) {
        return 'template';
    }

    function get_style_name($instance) {
        return 'style';
    }

}

Siteorigin_widget_register('featured-event', __FILE__, 'widgets\FeaturedEvent');
