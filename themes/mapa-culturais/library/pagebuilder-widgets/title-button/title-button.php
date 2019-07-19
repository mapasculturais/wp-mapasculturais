<?php

/*
  Widget Name: Título com Botão
  Description: Um título em destaque com um botão call-to-action
  Author: hacklab/
  Author URI: https://hacklab.com.br
 */

namespace widgets;

class TitleButton extends \SiteOrigin_Widget {

    function __construct() {
        $fields = [
            'title' => [
                'type' => 'text',
                'label' => 'Título'
            ],
            'align' => [
                'type' => 'select',
                'label' => 'Alinhar',
                'options' => [
                    'left' => 'à esquerda',
                    'center' => 'ao centro'
                ]
            ],
            'button_text' => [
                'type' => 'text',
                'label' => 'Texto do Botão',
                'description' => 'Pode ser vazio'
            ],
            'button_url' => [
                'type' => 'text',
                'label' => 'Link do Botão',
                'description' => 'Pode ser vazio'
            ],
            'button_target' => [
                'type' => 'select',
                'label' => 'Abrir o link em',
                'options' => [ '_self' => 'Na mesma janela', '_blank' => 'Numa nova janela' ],
                'description' => 'Pode ser vazio'
            ],
        ];

        parent::__construct('title-button', 'Título com Botão', [
            'panels_groups' => [WIDGETGROUP_BANNERS],
            'description' => 'Um título em destaque com um botão call-to-action'
        ], [], $fields, plugin_dir_path(__FILE__));
    }

    function get_template_name($instance) {
        return 'template';
    }

    function get_style_name($instance) {
        return 'style';
    }

}

Siteorigin_widget_register('title-button', __FILE__, 'widgets\TitleButton');
