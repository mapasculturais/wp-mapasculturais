<div class="wrap">
<h1><?php _e('Configuração do Mapas Culturais', 'wp-mapasculturais') ?></h1>
<form method="post" action="options.php"> 
    <?php settings_fields( 'mapasculturais' ); ?>
    <?php do_settings_sections( 'mapasculturais' ); ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e('URL da instação do Mapas Culturais', 'wp-mapasculturais') ?></th>
            <td><input type="text" name="mapasculturais_url" value="<?php echo esc_attr( get_option('mapasculturais_url') ); ?>" autocomplete="off"/></td>
        </tr>
         
        <tr valign="top">
            <th scope="row"><?php _e('Chave Privada ', 'wp-mapasculturais') ?></th>
            <td><input type="password" name="mapasculturais_private_key" value="<?php echo esc_attr( get_option('mapasculturais_private_key') ); ?>"  autocomplete="off"/></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><?php _e('Chave Pública', 'wp-mapasculturais') ?></th>
            <td><input type="text" name="mapasculturais_public_key" value="<?php echo esc_attr( get_option('mapasculturais_public_key') ); ?>"  autocomplete="off"/></td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>
</div>