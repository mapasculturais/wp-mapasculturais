<?php
get_header('space');
the_post();
$meta = get_post_meta(get_the_ID());
?>
<article><?php the_content() ?></article>
<p><?php var_dump($meta) ?></p>

<div class="mc-s mc-s-space">
    <main class="mc-s__main">
        <div class="mc-s__header" style="background-image: url(<?= wp_get_attachment_image_src($meta['space_header-image_thumbnail_id'][0], 'large')[0] ?>)"></div>
        <div class="mc-s__title">
            <div>
                <div class="avatar" style="background-image: url(<?= wp_get_attachment_image_src($meta['MAPAS:entity_avatar_attachment_id'][0], 'thumbnail')[0] ?>)"></div>
            </div>
            <div>
                <div class="type">Tipo do espaço</div>
                <div class="title"><?php the_title(); ?></div>
                <div class="subtitle">Subtítulo do espaço</div>
            </div>
        </div>
        <div class="mc-s__content">
            <?php if (!empty($meta['shortDescription'])): ?>
                <div class="mc-s__slot">
                    <div class="icon"><span>Descrição</span></div>
                    <div class="text"><?= $meta['shortDescription'][0] ?></div>
                </div>
            <?php endif; ?>
            <div class="mc-s__slot">
                <div class="icon"></div>
                <div class="text"></div>
            </div>
            <?php if (!empty($meta['horario'])): ?>
            <div class="mc-s__slot">
                <div class="icon" aria-label="Horário de funcionamento"><i class="far fa-clock" aria-hidden="true"></i></div>
                <div class="text"><?= $meta['horario'][0] ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($meta['acessibilidade'])): ?>
                <div class="mc-s__slot">
                    <div class="icon" aria-label="Acessibilidade"><i class="fab fa-accessible-icon" aria-hidden="true"></i></div>
                    <div class="text"><?= $meta['acessibilidade'][0] == 'Sim' ? 'Acessível' : 'Não acessível' ?></div>
                </div>
            <?php endif; ?>
            <div class="mc-s__slot">
                <div class="icon"></div>
                <div class="text"></div>
            </div>
            <div class="mc-s__slot">
                <div class="icon"></div>
                <div class="text"></div>
            </div>
        </div>
    </main>

    <aside class="mc-s__sidebar">
        Sidebar
    </aside>
</div>

<?php get_footer('space'); ?>