<?php
/**
 * Template Name: Pagebuilder sem título
 */
get_header();
the_post();
?>

<div class="row" id="content">
    <?php the_content(); ?>
</div>

<?php get_footer(); ?>
