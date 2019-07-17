<?php 

function wp_enqueue_script($a, $b, $c, $d, $e){
	var_dump($a, $b, $c);
}

function get_bloginfo($a){
	return '';
}

function add_action($a, $b)
{
	call_user_func($b);
	return ''; 
}

function add_image_size($a,$b,$c,$d){
	var_dump($a,$b,$c);
}

function register_post_type($a, $b){
	var_dump($a, $b);
}
