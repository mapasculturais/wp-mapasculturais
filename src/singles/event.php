<?php
get_header('event');
the_post();
$meta = get_post_meta(get_the_ID());
$headerMeta = mc_array_at($meta, 'event_header-image_thumbnail_id');
$avatarMeta = mc_array_at($meta, 'MAPAS:entity_avatar_attachment_id');
?>

<div id="content" class="mc-s mc-s-event">
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
                <div class="type"><?= wp_get_post_terms(get_the_ID(), 'linguagem')[0]->name ?></div>
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
            </div>
            <div class="mc-s__tab" v-show="tab === 0">
                <?php if (!empty(get_the_content())): ?>
                    <div class="mc-s__slot">
                        <div class="icon"><span>Descrição</span></div>
                        <div class="text"><?php the_content(); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty(mc_array_at($meta, 'classificacaoEtaria'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Classificação Etária"><i class="fas fa-child" aria-hidden="true"></i></div>
                        <div class="text"><?= $meta['classificacaoEtaria'][0] ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty(mc_array_at($meta, 'descricaoSonora'))): ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Audiodescrição"><i class="fas fa-audio-description" aria-hidden="true"></i></div>
                        <div class="text"><?= $meta['descricaoSonora'][0] == 'Sim' ? 'Audiodescrição' : 'Sem audiodescrição' ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty(mc_array_at($meta, 'traducaoLibras'))) : ?>
                    <div class="mc-s__slot">
                        <div class="icon" aria-label="Tradução em LIBRAS"><i class="fas fa-sign-language" aria-hidden="true"></i></div>
                        <div class="text"><?= $meta['traducaoLibras'][0] == 'Sim' ? 'Tradução em LIBRAS' : 'Sem tradução em LIBRAS' ?></div>
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
                <div class="mc-s__slot contact">
                    <div class="icon"><span>Contato</span></div>
                    <div class="text">
                        <?php if (!empty(mc_array_at($meta, 'site'))): ?>
                            <div class="mc-s__slot">
                                <div class="icon" aria-label="Link"><i class="fas fa-link" aria-hidden="true"></i></div>
                                <div class="text"><a href="<?= $meta['site'][0] ?>"><?= $meta['site'][0] ?></a></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty(mc_array_at($meta, 'facebook'))): ?>
                            <div class="mc-s__slot">
                                <div class="icon" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></div>
                                <div class="text"><a href="<?= $meta['facebook'][0] ?>"><?= $meta['facebook'][0] ?></a></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty(mc_array_at($meta, 'twitter'))): ?>
                            <div class="mc-s__slot">
                                <div class="icon" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></div>
                                <div class="text"><a href="<?= $meta['twitter'][0] ?>"><?= $meta['twitter'][0] ?></a></div>
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
        </div>
    </main>
</div>

<?php get_footer('event'); ?>