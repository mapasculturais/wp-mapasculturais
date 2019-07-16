<?php
get_header('space');
the_post();
$meta = get_post_meta(get_the_ID());
?>
<p><?php var_dump($meta); ?></p>
<p><?php var_dump(get_object_taxonomies(get_post())); ?></p>

<div class="mc-s mc-s-space">
    <main class="mc-s__main">
        <div class="mc-s__header" style="background-image: url(<?= wp_get_attachment_image_src($meta['space_header-image_thumbnail_id'][0], 'large')[0] ?>)"></div>
        <div class="mc-s__title">
            <div>
                <div class="avatar" style="background-image: url(<?= wp_get_attachment_image_src($meta['MAPAS:entity_avatar_attachment_id'][0], 'thumbnail')[0] ?>)"></div>
            </div>
            <div>
                <div class="type"><?= wp_get_post_terms(get_the_ID(), 'space_type')[0]->name ?></div>
                <div class="title"><?php the_title(); ?></div>
                <div class="subtitle"><?= str_replace(['<p>', '</p>'], ['', ''], get_the_excerpt()) ?></div>
            </div>
        </div>
        <div class="mc-s__content">
            <?php if (!empty(get_the_content())): ?>
                <div class="mc-s__slot">
                    <div class="icon"><span>Descrição</span></div>
                    <div class="text"><?php the_content(); ?></div>
                </div>
            <?php endif;
            $address = mc_format_address($meta);
            if (!empty(mc_array_at($meta, 'endereco'))): ?>
            <div class="mc-s__slot">
                <div class="icon" aria-label="Endereço"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></div>
                <div class="text adress"><?= $address ?></div>
            </div>
            <?php endif;
            if (!empty(mc_array_at($meta, 'horario'))): ?>
            <div class="mc-s__slot">
                <div class="icon" aria-label="Horário de funcionamento"><i class="far fa-clock" aria-hidden="true"></i></div>
                <div class="text"><?= $meta['horario'][0] ?></div>
            </div>
            <?php endif;
            if (!empty(mc_array_at($meta, 'acessibilidade'))): ?>
                <div class="mc-s__slot">
                    <div class="icon" aria-label="Acessibilidade"><i class="fab fa-accessible-icon" aria-hidden="true"></i></div>
                    <div class="text"><?= $meta['acessibilidade'][0] == 'Sim' ? 'Acessível' : 'Não acessível' ?></div>
                </div>
            <?php endif;
            $tags = wp_get_post_terms(get_the_ID(), 'post_tag');
            if (!empty($tags)): ?>
            <div class="mc-s__slot">
                <div class="icon" aria-label="Tags"><i class="fas fa-tags" aria-hidden="true"></i></div>
                <div class="text">
                    <ul class="tags">
                    <?php foreach ($tags as $tag): ?>
                        <li class="tag"><?= $tag->name ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif;
            $areas = wp_get_post_terms(get_the_ID(), 'area');
            if (!empty($areas)): ?>
            <div class="mc-s__slot">
                <div class="icon"><span>Área de atuação</span></div>
                <div class="text">
                    <ul class="tags">
                    <?php foreach ($areas as $area): ?>
                        <li class="tag"><?= $area->name ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <aside class="mc-s__sidebar">
        Sidebar
    </aside>
</div>

<?php get_footer('space'); ?>