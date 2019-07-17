<?php 

class Shortcode
{
	public $name;
	public $defaults;
	public $view;

	function __construct($name, $view, $defaults)
	{
		$this->name = $name;
		$this->defaults = $defaults;
		$this->view = $view;
	}

	public function render($attr, $content = null)
	{
		return $blade->view()
		->make($this->view,['attr'=>shortcode_atts($this->defaults, $attr, $this->name), 'content' => do_shortcode($content)])
		->render();
	}

	public function register($attr, $content){
		add_shortcode($this->name, [$this, 'render', $attr, $content]);
	}

	public function hook()
	{
		add_action('init', [$this, 'register']);
	}
}

//http://qnimate.com/adding-buttons-to-wordpress-visual-editor/
