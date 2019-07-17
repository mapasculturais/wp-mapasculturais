</div>
<?php wp_reset_postdata() ?>
<div class="page--share">
    <a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?= get_the_permalink() ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
    <a class="messenger" href="http://www.facebook.com/dialog/send?app_id=346307852736540&link=<?= get_the_permalink() ?>&redirect_uri=<?= get_the_permalink() ?>" target="_blank"><i class="fab fa-facebook-messenger"></i></a>
    
    <a class="twitter" href="https://twitter.com/intent/tweet?text=<?= urlencode(get_the_title()) ?>&url=<?= get_the_permalink() ?>" target="_blank"><i class="fab fa-twitter"></i></a>
    <a class="whatsapp hide-for-large" href="whatsapp://send?text=<?= (get_the_title().' - '.get_the_permalink()) ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
    <a class="whatsapp show-for-large" href="https://api.whatsapp.com/send?text=<?= (get_the_title().' - '.get_the_permalink()) ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
    <a class="telegram" href="https://telegram.me/share/url?url=<?= get_the_title().' - '.get_the_permalink() ?>" target="_blank"><i class="fab fa-telegram"></i></a>
    <a class="mail" href="mailto:?subject=<?= the_title() ?>&body=<?= get_the_permalink() ?>" target="_blank"><i class="far fa-envelope"></i></a>
</div>

<footer class="main-footer">
    <div class="row justify-content-between">
        <div class="column large-7">
            <a href="/"><img src="<?= get_theme_logo()  ?>" alt="<?= get_bloginfo('name') ?>"></a>
        </div>

        <div class="column large-3">
            <div class="footer-title">Siga-nos nas redes sociais</div>
            <div class="d-flex justify-content-between mt-20 social-networks--wrapper">
                <?php the_social_networks_menu(true) ?>
            </div>
        </div>

        <div class="column large-12 mt-40">
			<?=wp_nav_menu(['theme_location' => 'footer-menu', 'container' => 'nav', 'menu_id' => 'footer-menu', 'menu_class' => 'footer-menu'])?>
        </div>
    </div>
</footer>
<?php wp_footer() ?>

</body>
</html>