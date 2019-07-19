<?php 
$query = new WP_Query($query_args); 
?>

<div class="row list-spaces">
    <?php if ($query->have_posts()): ?>
        <?php while ($query->have_posts()):
            $query->the_post();
                $extra_info = '
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <strong>' . get_post_meta(get_the_ID(), 'En_Municipio', true) . ' - '. get_post_meta(get_the_ID(), 'En_Estado', true) .'</strong> <br>' 
                      . get_post_meta(get_the_ID(), 'endereco', true) .
                '</div>';

                ?>
                <div class="column large-<?php echo  12 / $instance['columns'] ?>">
                    <?php template_part('card', [ 'extra_info' => $extra_info, 'taxonomy' => get_the_taxonomy('space_type') ]); ?>
                </div>
            <?php endwhile; ?>
    <?php endif; ?>

    <?php if ($instance['vermais_href']): ?>
        <a class="posts-list--ver-mais" href="<?php echo  $instance['vermais_href'] ?>"><?php echo  ('Mostrar mais') ?></a>
    <?php endif; ?>
</div>