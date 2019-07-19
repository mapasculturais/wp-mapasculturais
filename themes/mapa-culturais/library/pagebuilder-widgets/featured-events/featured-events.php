<?php

/*
  Widget Name: Eventos em Destaque
  Description: Lista de eventos em Destaque
  Author: hacklab/
  Author URI: https://hacklab.com.br/
 */

namespace widgets;

class FeaturedEvents extends \SiteOrigin_Widget {

    function __construct() {
        $fields = [
            'conteudo' => [
                'type' => 'posts',
                'label' => __('Conteúdo', 'lula'),
            ],
        ];

        parent::__construct('featured-events', __('Eventos em Destaque', 'lula'), [
            'panels_groups' => [WIDGETGROUP_MAIN],
            'description' => 'Lista de eventos em Destaque'
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

Siteorigin_widget_register('featured-events', __FILE__, 'widgets\FeaturedEvents');
