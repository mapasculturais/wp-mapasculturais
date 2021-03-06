
<?php $query = new WP_Query($query_args); $i = 0; ?>

<div class="row posts-list">
    <?php if ($query->have_posts()): ?>
        <?php while ($query->have_posts()): $i++;
            $query->the_post();
            global $post; 
            
            $card_config = [ 'show_excerpt' => true, 'show_image' => $i == 1 ? true : false ];

            if($instance['exibicao'] == 'archive'){
                $card_config = [ 'show_excerpt' => true, 'show_image' => true ];
            }

            ?>
            <div class="column large-<?php echo  12 / $instance['columns'] ?> <?php echo  ($instance['columns'] == '1' ? 'p-0':'') ?> mb20 <?php echo  $i == $query->post_count ? 'last-post':'' ?>">
                <?php template_part('card', $card_config) ?>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <?php if ($instance['vermais_href']): ?>
        <a class="posts-list--ver-mais" href="<?php echo  $instance['vermais_href'] ?>"><?php echo  pll_e('Ver mais') ?></a>
    <?php endif; ?>
</div>