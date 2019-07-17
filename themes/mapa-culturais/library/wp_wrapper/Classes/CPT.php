<?php 
class CPT
{
	public $id;
	public $args;
	public $supports = [
	'title',
	'editor',
	'author',
	'thumbnail',
	'excerpt',
	'page-attributes',
	'post-formats'
	];

	function __construct($id, $labels, $slug = '', $supports = null)
	{
		$rewrite = ['slug' => $slug ];

		if($slug == null)
			$rewrite = null;
		elseif($slug == '')
			$rewrite = ['slug' => $id ];

		$this->supports = !is_null($supports) ? $supports : $this->supports;
		$this->id = $id;	
		$this->args = [
		'label' => $id,
		'description' => '',
		'labels' => (array)$labels,
		'hierarchical' => false,
		'public' => true,
		'taxonomies' => array('post_tag', 'category'),
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_admin_bar' => true,
		'menu_position' => 4,
		'can_export' => true,
		'has_archive' => true,
		'exclude_from_search' => false,
		'publicly_queryable' => true,
		'rewrite' => $rewrite,
		'show_in_rest' => true,
		'supports' => $this->supports
		];
	}

	public function supports($ar){
		$this->args['supports'] = $ar;

		return $this;
	}

	public function highlightOnly(){
		$this->supports = [
		'title',
		'author',
		'thumbnail',
		'excerpt'
		];

		$this->args['supports'] = $this->supports;

		return $this;
	}

	public function __set($name, $value)
	{
		$this->args[$name] = $value;
	}
}

class CPTs
{
	public static $cpts;
	
	static function add()
	{
		self::$cpts = func_get_args();

		return new self;
	}

	static public function register()
	{
		foreach (self::$cpts as $cpt) 
			register_post_type($cpt->id, $cpt->args);  
	}

	static public function hook()
	{
		add_action('init', ['CPTs', 'register'], 0);
	}

	static public function getList($index = 'id') {
		if(is_null(self::$cpts)) return [];
		return array_map(function($k) use($index) {
			if($index == 'id')
				return $k->id;

			return $k->args[$index];
		},self::$cpts);
	}
}