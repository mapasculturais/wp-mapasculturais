<?php

/*
  Widget Name: Slider de Eventos
  Description: Lista de Slider de Eventos
  Author: hacklab/
  Author URI: https://hacklab.com.br/
 */

namespace widgets;

class SliderEvents extends \SiteOrigin_Widget {

    function __construct() {
        $fields = [
            'conteudo' => [
                'type' => 'posts',
                'label' => __('Conteúdo', 'mapas-culturais'),
            ],
        ];

        parent::__construct('slider-events', __('Slider de Eventos', 'mapas-culturais'), [
            'panels_groups' => [WIDGETGROUP_MAIN],
            'description' => 'Lista de Slider de Eventos'
                ], [], $fields, plugin_dir_path(__FILE__)
        );
    }

    function get_template_name($instance) {
        return 'template';
    }

    function get_style_name($instance) {
        return 'style';
    }

    function get_template_variables($instance, $args) {
        $posts_query_args = siteorigin_widget_post_selector_process_query($instance['conteudo']);

        return ['query_args' => $posts_query_args];
    }

}

Siteorigin_widget_register('slider-events', __FILE__, 'widgets\SliderEvents');
