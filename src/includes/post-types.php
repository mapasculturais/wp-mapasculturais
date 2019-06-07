<?php
add_action( 'init', 'mapasculturais_register_post_types' );

function mapasculturais_register_post_types() {
	$labels_agent = [
		'name'               => __( 'Agentes', 'wp-mapasculturais' ),
		'singular_name'      => __( 'Agente', 'wp-mapasculturais' ),
		'menu_name'          => __( 'Agentes', 'wp-mapasculturais' ),
		'name_admin_bar'     => __( 'Agente', 'wp-mapasculturais' ),
		'add_new'            => __( 'Adicionar Novo', 'wp-mapasculturais' ),
		'add_new_item'       => __( 'Adicionar Novo Agente', 'wp-mapasculturais' ),
		'new_item'           => __( 'Novo Agente', 'wp-mapasculturais' ),
		'edit_item'          => __( 'Editar Agente', 'wp-mapasculturais' ),
		'view_item'          => __( 'Ver Agente', 'wp-mapasculturais' ),
		'all_items'          => __( 'Todos os Agentes', 'wp-mapasculturais' ),
		'search_items'       => __( 'Buscar Agentes', 'wp-mapasculturais' ),
		'parent_item_colon'  => __( 'Agentes Pais:', 'wp-mapasculturais' ),
		'not_found'          => __( 'Nenhum agente encontrado.', 'wp-mapasculturais' ),
		'not_found_in_trash' => __( 'Nenhum agente encontrado na lixeira.', 'wp-mapasculturais' )
    ];

	$args_agent = [
		'labels'             => $labels_agent,
		'description'        => __( 'Description.', 'wp-mapasculturais' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => [ 'slug' => 'agent' ],
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => true,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-id-alt',
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ]
    ];

    register_post_type( 'agent', $args_agent );


	$labels_space = [
		'name'               => __( 'Espaços', 'wp-mapasculturais' ),
		'singular_name'      => __( 'Espaço', 'wp-mapasculturais' ),
		'menu_name'          => __( 'Espaços', 'wp-mapasculturais' ),
		'name_admin_bar'     => __( 'Espaço', 'wp-mapasculturais' ),
		'add_new'            => __( 'Adicionar Novo', 'wp-mapasculturais' ),
		'add_new_item'       => __( 'Adicionar Novo Espaço', 'wp-mapasculturais' ),
		'new_item'           => __( 'Novo Espaço', 'wp-mapasculturais' ),
		'edit_item'          => __( 'Editar Espaço', 'wp-mapasculturais' ),
		'view_item'          => __( 'Ver Espaço', 'wp-mapasculturais' ),
		'all_items'          => __( 'Todos os Espaços', 'wp-mapasculturais' ),
		'search_items'       => __( 'Buscar Espaços', 'wp-mapasculturais' ),
		'parent_item_colon'  => __( 'Espaços Pais:', 'wp-mapasculturais' ),
		'not_found'          => __( 'Nenhum espaço encontrado.', 'wp-mapasculturais' ),
		'not_found_in_trash' => __( 'Nenhum espaço encontrado na lixeira.', 'wp-mapasculturais' )
    ];

	$args_space = [
		'labels'             => $labels_space,
		'description'        => __( 'Description.', 'wp-mapasculturais' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => [ 'slug' => 'space' ],
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => true,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-building',
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ]
    ];

    register_post_type( 'space', $args_space );


	$labels_event = [
		'name'               => __( 'Eventos', 'wp-mapasculturais' ),
		'singular_name'      => __( 'Evento', 'wp-mapasculturais' ),
		'menu_name'          => __( 'Eventos', 'wp-mapasculturais' ),
		'name_admin_bar'     => __( 'Evento', 'wp-mapasculturais' ),
		'add_new'            => __( 'Adicionar Novo', 'wp-mapasculturais' ),
		'add_new_item'       => __( 'Adicionar Novo Evento', 'wp-mapasculturais' ),
		'new_item'           => __( 'Novo Evento', 'wp-mapasculturais' ),
		'edit_item'          => __( 'Editar Evento', 'wp-mapasculturais' ),
		'view_item'          => __( 'Ver Evento', 'wp-mapasculturais' ),
		'all_items'          => __( 'Todos os Eventos', 'wp-mapasculturais' ),
		'search_items'       => __( 'Buscar Eventos', 'wp-mapasculturais' ),
		'parent_item_colon'  => __( 'Eventos Pais:', 'wp-mapasculturais' ),
		'not_found'          => __( 'Nenhum evento encontrado.', 'wp-mapasculturais' ),
		'not_found_in_trash' => __( 'Nenhum evento encontrado na lixeira.', 'wp-mapasculturais' )
    ];

	$args_event = [
		'labels'             => $labels_event,
		'description'        => __( 'Description.', 'wp-mapasculturais' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => [ 'slug' => 'event' ],
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-calendar-alt',
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ]
    ];

    register_post_type( 'event', $args_event );    
}

