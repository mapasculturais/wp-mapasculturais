<div class="card card-spaces">
    <div class="card--image" style="background-image: url( <?php echo images\url('card-small') ?> )">
        <i class="card--icon fas fa-bookmark"></i>
        <div class="card--block">
            <a tabindex="-1" href="<?= get_the_permalink() ?>">
                <div class="card--title"><?php the_title() ?></div>
            </a>

            <?php if(isset($taxonomy) && !empty($taxonomy)): ?>
                <div class="card--taxonomy"><?= $taxonomy ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($extra_info) && !empty($extra_info)): ?>
        <div class="card--footer">
            <div class="card--info fz-12"><?= $extra_info ?></div>
        </div>
    <?php endif; ?>
</div>
