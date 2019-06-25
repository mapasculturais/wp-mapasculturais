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
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'taxonomies'         => ['post_tag']
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
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'taxonomies'         => ['post_tag']
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
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'taxonomies'         => ['post_tag']
    ];

	register_post_type( 'event', $args_event );    
	


		// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name'              => __( 'Áreas de atuação', 'wp-mapas' ),
		'singular_name'     => __( 'Área de atuação', 'wp-mapas' ),
		'search_items'      => __( 'Procurar áreas de atuação', 'wp-mapas' ),
		'all_items'         => __( 'Todas as áreas de atuação', 'wp-mapas' ),
		'edit_item'         => __( 'Editar área de atuação', 'wp-mapas' ),
		'update_item'       => __( 'Atualizar área de atuação', 'wp-mapas' ),
		'add_new_item'      => __( 'Adicionar nova área de atuação', 'wp-mapas' ),
		'new_item_name'     => __( 'Nome da nova área de atuação', 'wp-mapas' ),
		'menu_name'         => __( 'Área de atuação', 'wp-mapas' ),
	);

	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_tagcloud'     => false,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'area' ),
		'capabilities' => array(
			'manage_terms' => '',
			'edit_terms' => '',
			'delete_terms' => '',
			'assign_terms' => 'edit_posts'
		  ),
	);

	register_taxonomy( 'area', array( 'space', 'agent' ), $args );


	// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name'              => __( 'Linguagens', 'wp-mapas' ),
		'singular_name'     => __( 'Linguagem', 'wp-mapas' ),
		'search_items'      => __( 'Procurar linguagens', 'wp-mapas' ),
		'all_items'         => __( 'Todas as linguagens', 'wp-mapas' ),
		'edit_item'         => __( 'Editar linguagem', 'wp-mapas' ),
		'update_item'       => __( 'Atualizar linguagem', 'wp-mapas' ),
		'add_new_item'      => __( 'Adicionar nova linguagem', 'wp-mapas' ),
		'new_item_name'     => __( 'Nome da nova linguagem', 'wp-mapas' ),
		'menu_name'         => __( 'Linguagem', 'wp-mapas' ),
	);

	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_tagcloud'     => false,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'linguagem' ),
		'capabilities' => array(
			'manage_terms' => '',
			'edit_terms' => '',
			'delete_terms' => '',
			'assign_terms' => 'edit_posts'
		  ),
	);

	register_taxonomy( 'linguagem', array( 'event' ), $args );

	
	foreach(['agent', 'space', 'event'] as $post_type){
		// renomeia o metabox da imagem destacada
		add_action('do_meta_boxes', function () use ($post_type) {
			remove_meta_box( 'postimagediv', $post_type, 'side' );
			add_meta_box('postimagediv', __('Imagem principal', 'wp-mapas'), 'post_thumbnail_meta_box', $post_type, 'normal', 'high');
		});
	}
}

