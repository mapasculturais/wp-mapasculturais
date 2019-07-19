<div class="title-button mb-40 <?php echo  $instance['align'] ?>">
    <h3><?php echo  $instance['title']?></h3>
    <?php if($instance['button_text'] != ''): ?>
        <a href="<?php echo  $instance['button_url']?>" target="<?php echo  $instance['button_target']?>" class="small-link mt-5 d-block"><?php echo  $instance['button_text']?></a>
    <?php endif ?>
</div>