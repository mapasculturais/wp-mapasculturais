<?php
add_action('cmb2_admin_init', function () {
    
    $cmb = new_cmb2_box(array(
        'id' => 'midia_metabox',
        'title' => "Vídeos",
        'object_types' => array('midia'), // Post type
        'context' => 'normal',
        'priority' => 'high',
        'show_names' => true,
    ));

    $cmb->add_field(array(
        'name' => 'Arquivo de Multimidia',
        'description' => 'Caso seja uma imagem, não a suba neste campo, suba como imagem destacada.',
        'id' => 'file',
        'type' => 'file'
    ));

    $cmb_post = new_cmb2_box(array(
        'id' => 'post_metabox',
        'title' => "Informações Adicionais",
        'object_types' => array('post'), // Post type
        'context' => 'normal',
        'priority' => 'high',
        'show_names' => true,
    ));

    $cmb_post->add_field(array(
        'name' => 'Autores do post',
        'id' => 'author_names',
        'type' => 'text'
    ));
    

});
