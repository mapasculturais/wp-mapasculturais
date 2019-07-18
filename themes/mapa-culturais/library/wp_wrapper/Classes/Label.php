<?php 
class Label{
	public $name;
	public $singular_name;
	public $menu_name;
	public $parent_item_colon;
	public $all_items;
	public $view_item;
	public $add_new_item;
	public $update_item;
	public $search_items;
	public $add_new = 'Adicionar Novo';
	public $edit_item = 'Editar';
	public $not_found = 'Não Encontrado';
	public $not_found_in_trash = 'Não encontrado na lixeira';

	public function __construct($singular = '', $plural = '', $genero = 'M')
	{
		$this->singular_name = $singular;
		$this->name = $plural;
		$this->menu_name = $plural;
		$this->parent_item_colon = $plural . 'Pai';
		$this->all_items = $genero == 'M' ? 'Todos os '.$plural : 'Todas as '.$plural;
		$this->add_new_item = $genero == 'M' ? 'Adicionar Novo '.$singular : 'Adicionar Nova '.$singular;
		$this->view_item = 'Ver '.$singular;
		$this->update_item = 'Atualizar '.$singular;
		$this->search_items = 'Procurar '.$singular;
	}

	public function __set($name, $value)
	{
		$this->$name = $value;
	}
}
