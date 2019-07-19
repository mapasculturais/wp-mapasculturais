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

                $featured_linguagens = [];
                foreach( get_the_terms(get_the_ID(), 'linguagem') as $linguagem ){
                    $featured_linguagens[] = '<a href="'. get_term_link($linguagem->term_id, 'linguagem') .'">'. $linguagem->name .'</a>';
                }
                ?>
                <div class="column large-<?= 12 / $instance['columns'] ?>">
                    <?php template_part('card', [ 'extra_info' => $extra_info, 'taxonomy' => implode(', ', $featured_linguagens) ]); ?>
                </div>
            <?php endwhile; ?>
    <?php endif; ?>

    <?php if ($instance['vermais_href']): ?>
        <a class="posts-list--ver-mais" href="<?= $instance['vermais_href'] ?>"><?= ('Mostrar mais') ?></a>
    <?php endif; ?>
</div>