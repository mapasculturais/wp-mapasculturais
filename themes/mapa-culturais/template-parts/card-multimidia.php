<?php 
$attachment_url = get_post_meta(get_the_ID(), 'file', true);
?>
<div class="card card-multimidia <?= $multimidia ?>">
    <?php if($multimidia == 'imagem' && images\tag('card-small', 'card--image') != ''): ?>
        <a tabindex="-1" href="<?= get_the_permalink() ?>">
            <?php echo images\tag('full', 'card--image mb-0') ?>
            <a href="<?= images\url('full') ?>" class="button dark video" download><i class="fas fa-file-download text-white"></i>Baixar Arquivo</a>
        </a>    
    <?php elseif( $multimidia == 'videos' ): ?>
        <div class="card--video">
            <video src="<?= $attachment_url ?>" width="100%" controls></video>
            <a href="<?= $attachment_url ?>" class="button dark video" download><i class="fas fa-file-download text-white"></i>Baixar Vídeo</a>
        </div>
    <?php else: ?>
        <div class="card--no-image">
            <a href="<?= $attachment_url ?>" class="button dark" download><i class="fas fa-file-download text-white"></i>Baixar Arquivo</a>
        </div>
    <?php endif ?>
    
    <h4 class="card--title mb-0 mt-10"><?php the_title() ?></h4>
    <p><?= images\size( $attachment_url)  ?></p>
</div>