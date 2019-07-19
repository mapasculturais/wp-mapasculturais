<?php
/**
 * Template Name: Pagebuilder sem título
 */
get_header();
the_post();
?>

<div class="row pt-40" id="content">
    <?php the_content(); ?>
</div>

<?php get_footer(); ?>
