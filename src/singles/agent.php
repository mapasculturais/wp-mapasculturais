<?php
get_header('agent');
the_post();
$meta = get_post_meta(get_the_ID());
$headerMeta = mc_array_at($meta, 'agent_header-image_thumbnail_id');
$avatarMeta = mc_array_at($meta, '_thumbnail_id');
?>

<div id="content" class="mc-s mc-s-space">
    <main class="mc-s__main">
        <div class="mc-s__header"<?= is_array($headerMeta)
            ? 'style="background-image: url('.wp_get_attachment_image_src($headerMeta[0], 'large')[0].')"'
            : '' ?>>
        </div>
        <div class="mc-s__title">
            <div>
                <div class="avatar"<?= is_array($avatarMeta)
                    ? 'style="background-image: url('.wp_get_attachment_image_src($avatarMeta[0], 'thumbnail')[0].')"'
                    : '' ?>>
                </div>
            </div>
            <div>
                <div class="type">Agente <?= wp_get_post_terms(get_the_ID(), 'agent_type')[0]->name ?></div>
                <div class="title"><?php the_title(); ?></div>
                <div class="subtitle"><?= str_replace(['<p>', '</p>'], ['', ''], get_the_excerpt()) ?></div>
            </div>
        </div>
        <div class="mc-s__content">
            <div class="mc-s__tabs-control">
                <div role="button" tabindex="0" class="tab" :class="{ selected: tab === 0 }" @click="tab = 0">
                    Informações
                </div>
                <div role="button" tabindex="0" class="tab" :class="{ selected: tab === 1 }" @click="tab = 1">
                    Fotos
                </div>
                <div role="button" tabindex="0" class="tab" :class="{ selected: tab === 2 }" @click="tab = 2">
                    Agenda
                </div>
            </div>
        </div>
        <div class="mc-s__tab" v-show="tab === 0">
            <?php if (!empty(get_the_content())): ?>
                <div class="mc-s__slot">
                    <div class="icon"><span>Descrição</span></div>
                    <div class="text"><?php the_content(); ?></div>
                </div>
            <?php endif; ?>
            <?php $address = mc_format_address($meta); ?>
            <?php if (!empty($address)): ?>
                <div class="mc-s__slot">
                    <div class="icon" aria-label="Endereço"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></div>
                    <div class="text address">
                        <?php if (!empty(mc_array_at($meta, 'endereco'))): ?>
                            <div class="name"><?= $meta['endereco'][0] ?></div>
                        <?php endif; ?>
                        <div class="location"><?= $address ?></div>
                        <div class="mc-s__slot">
                            <div class="icon" aria-label="Tipo"><i class="fas fa-university" aria-hidden="true"></i></div>
                            <div class="text"><b><i>Agente <?= wp_get_post_terms(get_the_ID(), 'agent_type')[0]->name ?></i></b></div>
                        </div>
                        <?php if (!empty(mc_array_at($meta, 'horario'))): ?>
                            <div class="mc-s__slot">
                                <div class="icon" aria-label="Horário de funcionamento"><i class="far fa-clock" aria-hidden="true"></i></div>
                                <div class="text"><?= $meta['horario'][0] ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php $tags = wp_get_post_terms(get_the_ID(), 'post_tag'); ?>
            <?php if (!empty($tags)): ?>
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
            <?php endif; ?>
            <?php $areas = wp_get_post_terms(get_the_ID(), 'area'); ?>
            <?php if (!empty($areas)): ?>
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
                    <?php endif; ?>
                    <?php if (!empty(mc_array_at($meta, 'telefonePublico'))): ?>
                        <div class="mc-s__slot">
                            <div class="icon" aria-label="Telefone"><i class="fas fa-phone" aria-hidden="true"></i></div>
                            <div class="text"><a href="tel:<?= $meta['telefonePublico'][0] ?>"><?= $meta['telefonePublico'][0] ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty(mc_array_at($meta, 'emailPublico'))): ?>
                        <div class="mc-s__slot">
                            <div class="icon" aria-label="E-mail"><i class="far fa-envelope" aria-hidden="true"></i></div>
                            <div class="text"><a href="mailto:<?= $meta['emailPublico'][0] ?>"><?= $meta['emailPublico'][0] ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty(mc_array_at($meta, 'facebook'))): ?>
                        <div class="mc-s__slot">
                            <div class="icon" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></div>
                            <div class="text"><a href="<?= $meta['facebook'][0] ?>"><?= preg_replace('/(https?:\/\/)?(www\.)?facebook\.com/', '', $meta['facebook'][0]) ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty(mc_array_at($meta, 'twitter'))): ?>
                        <div class="mc-s__slot">
                            <div class="icon" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></div>
                            <div class="text"><a href="<?= $meta['twitter'][0] ?>"><?= preg_replace('/(https?:\/\/)?(www\.)?twitter\.com\//', '@', $meta['twitter'][0]) ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty(mc_array_at($meta, 'instagram'))): ?>
                        <div class="mc-s__slot">
                            <div class="icon" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></div>
                            <div class="text"><?= $meta['instagram'][0] ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="mc-s__tab" v-show="tab === 1">
            <?php $images = get_attached_media('image', get_the_ID());
            include 'includes/_gallery.php'; ?>
        </div>
        <div class="mc-s__tab" v-show="tab === 2">
            <mc-w-list agents="<?= $meta['MAPAS:entity_id'][0] ?>" :show-filters="false"></mc-w-list>
        </div>
    </main>

    <aside class="mc-s__sidebar">
        Sidebar
    </aside>
</div>

<?php get_footer('agent'); ?>