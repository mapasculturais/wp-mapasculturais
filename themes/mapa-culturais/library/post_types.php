<?php
$cpt_layout_parts = new CPT('layout_parts', new Label('Parte do Layout','Partes de Layout'));
$cpt_opiniao = new CPT('opiniao', new Label('Opiniões','Opinião'));
$cpt_multimidia = new CPT('midia', new Label('Multimídias','Multimídia'));
$cpt_multimidia->supports(['title', 'excerpt','thumbnail']);

$cpt_register = new CPTs();
$cpt_register->add($cpt_layout_parts, $cpt_opiniao, $cpt_multimidia)->hook();
