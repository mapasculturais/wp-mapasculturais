<div class="title-button">
    <h3><?= $instance['title']?></h3>
    <?php if($instance['button_text'] != ''): ?>
        <a href="<?= $instance['button_url']?>" target="<?= $instance['button_target']?>"><?= $instance['button_text']?></a>
    <?php endif ?>
</div>