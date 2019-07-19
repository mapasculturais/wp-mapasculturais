</div>
<?php wp_reset_postdata() ?>
<div class="page--share">
    <a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo  get_the_permalink() ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
    <a class="messenger" href="http://www.facebook.com/dialog/send?app_id=346307852736540&link=<?php echo  get_the_permalink() ?>&redirect_uri=<?php echo  get_the_permalink() ?>" target="_blank"><i class="fab fa-facebook-messenger"></i></a>
    
    <a class="twitter" href="https://twitter.com/intent/tweet?text=<?php echo  urlencode(get_the_title()) ?>&url=<?php echo  get_the_permalink() ?>" target="_blank"><i class="fab fa-twitter"></i></a>
    <a class="whatsapp hide-for-large" href="whatsapp://send?text=<?php echo  (get_the_title().' - '.get_the_permalink()) ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
    <a class="whatsapp show-for-large" href="https://api.whatsapp.com/send?text=<?php echo  (get_the_title().' - '.get_the_permalink()) ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
    <a class="telegram" href="https://telegram.me/share/url?url=<?php echo  get_the_title().' - '.get_the_permalink() ?>" target="_blank"><i class="fab fa-telegram"></i></a>
    <a class="mail" href="mailto:?subject=<?php echo  the_title() ?>&body=<?php echo  get_the_permalink() ?>" target="_blank"><i class="far fa-envelope"></i></a>
</div>

<footer class="main-footer">
    <div class="row justify-content-between">
        <div class="column large-4">
            <a href="/"><img src="<?php echo  get_theme_logo()  ?>" alt="<?php echo  get_bloginfo('name') ?>" width="215"></a>
        </div>

        <div class="column large-5">
			<?php echo wp_nav_menu(['theme_location' => 'footer-menu', 'container' => 'nav', 'menu_id' => 'footer-menu', 'menu_class' => 'footer-menu'])?>
        </div>

        <div class="column large-3 social-networks">
            <i class="fz-12">redes sociais</i>
            <?php the_social_networks_menu() ?>
        </div>
    </div>
</footer>
<?php wp_footer() ?>

</body>
</html>