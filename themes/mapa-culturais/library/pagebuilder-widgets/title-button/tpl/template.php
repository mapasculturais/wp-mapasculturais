<div class="title-button mb-40 <?= $instance['align'] ?>">
    <h3><?= $instance['title']?></h3>
    <?php if($instance['button_text'] != ''): ?>
        <a href="<?= $instance['button_url']?>" target="<?= $instance['button_target']?>" class="small-link mt-5 d-block"><?= $instance['button_text']?></a>
    <?php endif ?>
</div>