<?php
/**
 * Template Name: Pagebuilder sem título
 */
get_header();
the_post();
?>

<div class="row">
    <?php the_content(); ?>
</div>

<?php get_footer(); ?>
