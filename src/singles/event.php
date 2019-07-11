<?php 
get_header('event'); 
the_post();
$meta = get_post_meta(get_the_ID());
?>

<h1>Evento: <?php the_title() ?></h1>
<article><?php the_content() ?></article>
<?php var_dump($meta) ?> 
<?php get_footer('event'); ?>