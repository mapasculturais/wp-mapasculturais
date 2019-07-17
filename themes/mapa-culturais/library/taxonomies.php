<?php
$tax_featured = new Taxonomy('is_featured', ['post'], new Label('Destaque','Destaques'));
$tax_multimidia = new Taxonomy('multimidia', ['midia'], new Label('Multimídia','Multimídias'));
$tax_estados = new Taxonomy('estados', ['post', 'midia', 'opiniao'], new Label('Estado','Estados'));

$tax_register = new Taxonomies();
$tax_register->add($tax_featured, $tax_multimidia, $tax_estados)->hook();