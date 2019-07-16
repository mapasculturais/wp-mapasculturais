<?php
get_header('space');
the_post();
$meta = get_post_meta(get_the_ID());
?>
<p><?php var_dump($meta); ?></p>

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
            if (!empty($address)): ?>
            <div class="mc-s__slot">
                <div class="icon" aria-label="Endereço"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></div>
                <div class="text address">
                    <?php if (!empty(mc_array_at($meta, 'endereco'))): ?>
                    <div class="name"><?= $meta['endereco'][0] ?></div>
                    <?php endif; ?>
                    <div class="location"><?= $address ?></div>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Tipo"><i class="fas fa-university" aria-hidden="true"></i></div>
                        <div class="text"><b><i><?= wp_get_post_terms(get_the_ID(), 'space_type')[0]->name ?></i></b></div>
                    </div>
                    <?php if (!empty(mc_array_at($meta, 'horario'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Horário de funcionamento"><i class="far fa-clock" aria-hidden="true"></i></div>
                        <div class="text"><?= $meta['horario'][0] ?></div>
                    </div>
                    <?php endif; ?>
                </div>
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
            <div class="mc-s__slot contact">
                <div class="icon"><span>Contato</span></div>
                <div class="text">
                    <?php if (!empty(mc_array_at($meta, 'site'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Link"><i class="fas fa-link" aria-hidden="true"></i></div>
                        <div class="text"><a href="<?= $meta['site'][0] ?>"><?= $meta['site'][0] ?></a></div>
                    </div>
                    <?php endif;
                    if (!empty(mc_array_at($meta, 'telefonePublico'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Telefone"><i class="fas fa-phone" aria-hidden="true"></i></div>
                        <div class="text"><a href="tel:<?= $meta['telefonePublico'][0] ?>"><?= $meta['telefonePublico'][0] ?></a></div>
                    </div>
                    <?php endif;
                    if (!empty(mc_array_at($meta, 'emailPublico'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="E-mail"><i class="far fa-envelope" aria-hidden="true"></i></div>
                        <div class="text"><a href="mailto:<?= $meta['emailPublico'][0] ?>"><?= $meta['emailPublico'][0] ?></a></div>
                    </div>
                    <?php endif;
                    if (!empty(mc_array_at($meta, 'facebook'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></div>
                        <div class="text"><a href="<?= $meta['facebook'][0] ?>"><?= $meta['facebook'][0] ?></a></div>
                    </div>
                    <?php endif;
                    if (!empty(mc_array_at($meta, 'twitter'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></div>
                        <div class="text"><a href="<?= $meta['twitter'][0] ?>"><?= $meta['twitter'][0] ?></a></div>
                    </div>
                    <?php endif;
                    if (!empty(mc_array_at($meta, 'instagram'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></div>
                        <div class="text"><?= $meta['instagram'][0] ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <aside class="mc-s__sidebar">
        Sidebar
    </aside>
</div>

<?php get_footer('space'); ?>