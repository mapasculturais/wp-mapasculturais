<?php
namespace WPMapasCulturais;
$plugin = Plugin::instance();
?>
<div class="wrap">
<h1><?php _e('Configuração do Mapas Culturais', 'wp-mapas') ?></h1>
<form method="post" action="options.php">
    <?php settings_fields( 'mapasculturais' ); ?>
    <?php do_settings_sections( 'mapasculturais' ); ?>
    <hr>
    <h2><?php _e('Conexão com a instalação do Culturais', 'wp-mapas') ?></h2>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e('URL da instação do Mapas Culturais', 'wp-mapas') ?></th>
            <td><input type="text" name="MAPAS:url" value="<?php echo esc_attr( $plugin->getOption('url') ); ?>"/></td>
        </tr>

        <tr valign="top">
            <th scope="row"><?php _e('Chave Pública', 'wp-mapas') ?></th>
            <td><input type="text" name="MAPAS:public_key" value="<?php echo esc_attr( $plugin->getOption('public_key') ); ?>"/></td>
        </tr>

        <tr valign="top">
            <th scope="row"><?php _e('Chave Privada ', 'wp-mapas') ?></th>
            <td><input type="password" name="MAPAS:private_key" value="<?php echo esc_attr( $plugin->getOption('private_key') ); ?>"/></td>
        </tr>

        <tr valign="top" style="display:none">
            <th scope="row"><?php _e('Intervalo mínimo entre cada importação das entidades ', 'wp-mapas') ?></th>
            <td><input type="numeric" step="1" name="MAPAS:import-entities-interval" value="<?php echo esc_attr( $plugin->getOption('import-entities-interval', 60) ); ?>"/> <?php _e("segundos", 'wp-mapas') ?></td>
        </tr>
    </table>

    <?php submit_button(); ?>
    <button type="button" class="button js-mapas--import-new-entities"><?php _e("Importar novas entidades", 'wp-mapas') ?></button>
    <p>
        <pre class="js-mapas--api-output"></pre>
    </p>
</form>
</div>