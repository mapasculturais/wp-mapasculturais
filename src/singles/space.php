<?php 
get_header('space'); 
the_post();
$meta = get_post_meta(get_the_ID());
?>
<h1>Espaço: <?php the_title() ?></h1>
<article><?php the_content() ?></article>
<?php var_dump($meta) ?> 
<?php get_footer('space'); ?>