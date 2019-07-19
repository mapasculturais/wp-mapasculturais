<?php

/*
  Widget Name: Lista de Posts
  Description: Lista de posts
  Author: hacklab/
  Author URI: https://hacklab.com.br/
 */

namespace widgets;

class PostsList extends \SiteOrigin_Widget {

    function __construct() {
        $fields = [
            'exibicao' => [
                'type' => 'select',
                'label' => __('Modo de exibição', 'mapas-culturais'),
                'options' => [
                    'home' => 'Para home',
                    'archive' => 'Listagem simples'
                ]
            ],
            'columns' => [
                'type' => 'number',
                'label' => __('Cards por linha', 'mapas-culturais'),
                'default' => '1'
            ],
            'conteudo' => [
                'type' => 'posts',
                'label' => __('Conteúdo', 'mapas-culturais'),
            ],
        ];

        parent::__construct('posts-list', __('Lista de Posts', 'mapas-culturais'), [
            'panels_groups' => [WIDGETGROUP_MAIN],
            'description' => 'Lista de posts'
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

Siteorigin_widget_register('posts-list', __FILE__, 'widgets\PostsList');
