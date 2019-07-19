<?php

/*
  Widget Name: Lista de Espaços
  Description: Lista de Espaços
  Author: hacklab/
  Author URI: https://hacklab.com.br/
 */

namespace widgets;

class ListSpaces extends \SiteOrigin_Widget {

    function __construct() {
        $fields = [
            'columns' => [
                'type' => 'number',
                'label' => __('Cards por linha', 'mapas-culturais'),
                'default' => '2'
            ],
            'conteudo' => [
                'type' => 'posts',
                'label' => __('Conteúdo', 'mapas-culturais'),
            ],
        ];

        parent::__construct('list-spaces', __('Lista de Espaços', 'mapas-culturais'), [
            'panels_groups' => [WIDGETGROUP_MAIN],
            'description' => 'Lista de Espaços'
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

Siteorigin_widget_register('list-spaces', __FILE__, 'widgets\ListSpaces');
