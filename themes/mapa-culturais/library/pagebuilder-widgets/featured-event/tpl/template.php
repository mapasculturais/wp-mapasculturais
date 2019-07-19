<?php 
global $post; 

if(strpos($instance['link_content'], 'post:') > -1 ){
    preg_match('/^post: *([0-9]+)/', $instance['link_content'], $matches);
    $link_content_id = $matches[1];
    $post = get_post($link_content_id);
}
?>

<div class="featured-event" style="background-image: url('<?= images\url('full') ?>')">
    <div class="row">
        <div class="column large-12">
            <div class="events--block">
                <a href="<?= get_the_permalink() ?>"><h2 class="card--title"><?php the_title() ?></h2></a>
                <div class="card--taxonomy">
                <?php the_taxonomy('linguagem', ', '); ?>
                </div>
                <?php if(has_excerpt()): ?>
                    <div class="card--excerpt">
                        <a href="<?= get_the_permalink() ?>"><?php the_excerpt(); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>