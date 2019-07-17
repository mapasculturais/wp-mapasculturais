<?php 
include 'Classes/Label.php';
include 'Classes/Scripts.php';
include 'Classes/Taxonomy.php';
include 'Classes/Image.php';
include 'Classes/CPT.php';

function unset_by_value(&$arr, $value){
	if (($key = array_search($value, $arr)) !== false) {
		unset($arr[$key]);
	}
}
