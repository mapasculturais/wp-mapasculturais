<?php

/*
  Widget Name: Siga Nossas Redes
  Description: Seção "Siga Nossas Redes"
  Author: hacklab/
  Author URI: https://hacklab.com.br
 */

namespace widgets;

class FollowUs extends \SiteOrigin_Widget {
    function __construct() {

        parent::__construct('follow-us', 'Siga Nossas Redes', [
            'panels_groups' => [WIDGETGROUP_MAIN],
            'description' => 'Seção "Siga Nossas Redes"'
        ], [], [], plugin_dir_path(__FILE__));
    }

    function get_template_name($instance) {
        return 'template';
    }

    function get_style_name($instance) {
        return 'style';
    }

}

Siteorigin_widget_register('follow-us', __FILE__, 'widgets\FollowUs');
