<?php
if (count($images) === 0): ?>
    <p>Nenhuma mídia encontrada.</p>
<?php else: ?>
    <div class="mc-s__gallery">
    <?php foreach($images as $image): ?>
        <a class="mc-s__attached-image" href="<?= wp_get_attachment_image_src($image->ID, 'full')[0] ?>" target="_blank" style="background-image: url(<?= wp_get_attachment_image_src($image->ID, 'small')[0] ?>)"></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
