<?php

class Taxonomy
{
	public $id;
	public $args;
	public $post_type; 

	function __construct($id, $post_type, $labels)
	{
		$this->id = $id;
		$this->post_type = $post_type;
		$this->args = [
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => $id ),
		'show_in_rest' => true
		];
	}

	public function __set($name, $value)
	{
		$this->args[$name] = $value;
	}
}

class Taxonomies
{
	public static $taxonomies;
	
	static function add()
	{
		self::$taxonomies = func_get_args();

		return new self;
	}

	static public function register()
	{
		foreach (self::$taxonomies as $taxonomy) 
			register_taxonomy($taxonomy->id, $taxonomy->post_type , $taxonomy->args);  
	}

	static public function hook()
	{
		add_action('init', ['Taxonomies', 'register'], 0);
	}

	static public function getList($index = 'id') {
		return array_map(function($k) use($index) {
			if($index == 'id')
				return $k->id;

			return $k->args[$index];
		},self::$taxonomies);
	}
}

