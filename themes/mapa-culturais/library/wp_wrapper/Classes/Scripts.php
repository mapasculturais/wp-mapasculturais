<?php 
class Scripts
{
	public static $scripts;

	function __construct($args = null)
	{
		if(is_null($args)) return;

		foreach ($args as $arg) {
			$dep = isset($arg['dep']) ? $arg['dep'] : '';
			self::add($arg['name'], $arg['path'], $dep);
		}
	}

	public function add($name, $path, $dep = null)
	{
		array_push(self::$scripts, [$name, $path, $dep]);

		return new self;
	}

	static function addChunk()
	{
		self::$scripts = func_get_args();

		return new self;
	}

	static function register()
	{
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', 'https://code.jquery.com/jquery-2.1.0.min.js' );
		
		foreach (self::$scripts as $script) {
			$on_footer = isset($script[2]) ? $script[2] : false;
			$dependencies = isset($script[3]) ? $script[3] : [];

			if($on_footer)
				wp_enqueue_script($script[0], get_bloginfo('template_url').$script[1], $dependencies, '', true);
			else
				wp_enqueue_style($script[0], get_bloginfo('template_url').$script[1], $dependencies, '', $on_footer);
		}
	}

	static function hook(){
		add_action('wp_enqueue_scripts', ['Scripts', 'register'] );
	}
}