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
            <td><input type="text" name="MAPAS:url" value="<?php echo esc_attr( get_option('MAPAS:url') ); ?>"/></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><?php _e('Chave Pública', 'wp-mapas') ?></th>
            <td><input type="text" name="MAPAS:public_key" value="<?php echo esc_attr( get_option('MAPAS:public_key') ); ?>"/></td>
        </tr>
         
        <tr valign="top">
            <th scope="row"><?php _e('Chave Privada ', 'wp-mapas') ?></th>
            <td><input type="password" name="MAPAS:private_key" value="<?php echo esc_attr( get_option('MAPAS:private_key') ); ?>"/></td>
        </tr>
    </table>

    
    <hr><h2><?php _e('Sincronização e filtros de agentes', 'wp-mapas') ?></h2>
    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = get_option('MAPAS:agent:import') ?: 'mine'; ?>
                <strong><?php _e('Importar', 'wp-mapas') ?></strong><br>
                <label><input type="radio" name="MAPAS:agent:import" value="mine" <?php if($sync == 'mine') echo 'checked="checked"' ?>/> <?php _e('Somente os meus agentes', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:agent:import" value="control" <?php if($sync == 'control') echo 'checked="checked"' ?>/> <?php _e('Somente os agentes que tenho permissão para editar', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:agent:import" value="all" <?php if($sync == 'all') echo 'checked="checked"' ?>/> <?php _e('Todos os agentes', 'wp-mapas') ?> (<?php _e('não recomentado se não combinado com outros filtros', 'wp-mapas') ?>)</label>
            </td>
        </tr>
    </table>


    <hr><h2><?php _e('Sincronização e filtros de espaços', 'wp-mapas') ?></h2>
    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = get_option('MAPAS:space:import') ?: 'mine'; ?>
                <strong><?php _e('Importar', 'wp-mapas') ?></strong><br>
                <label><input type="radio" name="MAPAS:space:import" value="mine" <?php if($sync == 'mine') echo 'checked="checked"' ?>/> <?php _e('Somente os meus espaços', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="control" <?php if($sync == 'control') echo 'checked="checked"' ?>/> <?php _e('Somente os espaços que tenho permissão para editar', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="agents" <?php if($sync == 'agents') echo 'checked="checked"' ?> disabled/> <?php _e('Somente os espaços publicados pelos agentes importados', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="all" <?php if($sync == 'all') echo 'checked="checked"' ?>/> <?php _e('Todos os espaços', 'wp-mapas') ?> (<?php _e('não recomentado se não combinado com outros filtros', 'wp-mapas') ?>)</label>
            </td>
        </tr>
    </table>


    <hr><h2><?php _e('Sincronização e filtros de eventos', 'wp-mapas') ?></h2>
    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = get_option('MAPAS:event:import') ?: 'mine' ; ?>
                <strong><?php _e('Importar', 'wp-mapas') ?></strong><br>
                <label><input type="radio" name="MAPAS:event:import" value="mine" <?php if($sync == 'mine') echo 'checked="checked"' ?>/> <?php _e('Somente os meus eventos', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="control" <?php if($sync == 'control') echo 'checked="checked"' ?>/> <?php _e('Somente os eventos que tenho permissão para editar', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="agents" <?php if($sync == 'agents') echo 'checked="checked"' ?> disabled/> <?php _e('Somente os eventos publicados pelos agentes importados', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="spaces" <?php if($sync == 'spaces') echo 'checked="checked"' ?> disabled/> <?php _e('Somente os eventos publicados pelos agentes importados e que acontecem nos espaços importados', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="agents-spaces" <?php if($sync == 'agents-spaces') echo 'checked="checked"' ?> disabled/> <?php _e('Somente os eventos que acontecem nos espaços importados', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="all" <?php if($sync == 'all') echo 'checked="checked"' ?>/> <?php _e('Todos os eventos', 'wp-mapas') ?> (<?php _e('não recomentado se não combinado com outros filtros', 'wp-mapas') ?>)</label>
            </td>
        </tr>
    </table>



    <?php submit_button(); ?>
    <button type="button" class="js-mapas--import-new-entities"><?php _e("Importar novas entidades", 'wp-mapas') ?></button>
    <p>
        <pre class="js-mapas--api-output"></pre>
    </p>
</form>
</div>