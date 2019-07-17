<?php
namespace WPMapasCulturais;

$plugin = Plugin::instance();

$languages_terms = $plugin->api->getTaxonomyTerms('linguagem');
$area_terms = $plugin->api->getTaxonomyTerms('area');

$space_types = $plugin->api->mapasApi->getEntityTypes('space');
$agent_types = $plugin->api->mapasApi->getEntityTypes('agent');

$event_metadata = $plugin->getEntityMetadataDescription('event');

$event_age_ratings = (array) $event_metadata['classificacaoEtaria']->options;
sort($event_age_ratings);
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

    
    <hr><h2><?php _e('Sincronização e filtros de agentes', 'wp-mapas') ?></h2>
    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = $plugin->getOption('agent:import') ?: 'mine'; ?>
                <strong><?php _e('Importar', 'wp-mapas') ?></strong><br>
                <label><input type="radio" name="MAPAS:agent:import" value="mine" <?php if($sync == 'mine') echo 'checked="checked"' ?>/> <?php _e('Somente os meus agentes', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:agent:import" value="control" <?php if($sync == 'control') echo 'checked="checked"' ?>/> <?php _e('Somente os agentes que tenho permissão para editar', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:agent:import" value="all" <?php if($sync == 'all') echo 'checked="checked"' ?>/> <?php _e('Todos os agentes', 'wp-mapas') ?> (<?php _e('não recomentado se não combinado com outros filtros', 'wp-mapas') ?>)</label>
            </td>
        </tr>
    </table>

    <?php /* @todo enquanto não implementar a atribuição de selos às entidades não é possível utilizar o filtro abaixo
    <?php $verified = $plugin->getOption('agent:verified') ?>
    <label><input type="checkbox" name="MAPAS:agent:verified" <?php if($verified) echo 'checked' ?> /> <strong><?php _e("Somente agentes com selos verificadores") ?></strong> </label>
    */ ?>
    <div class="taxonomy-selector">
        <?php $types = $plugin->getOption('agent:types') ?: []; ?>
        <strong><?php _e('Selecione os tipos de agente que deseja utilizar em seu site. Para utilizar todos, não selecione nenhuma.', 'wp-mapas') ?></strong><br>
        <?php foreach($plugin->api->mapasApi->getEntityTypes('agent') as $type): ?>
            <label class="checkbox-selector"><input type="checkbox" name="MAPAS:agent:types[]" value="<?php echo $type->id ?>" <?php if(in_array($type->id, $types)) echo 'checked' ?> ><?php echo $type->name ?></label>
        <?php endforeach; ?>
    </div>

    <div class="taxonomy-selector">
        <?php $areas = $plugin->getOption('agent:areas') ?: []; ?>
        <strong><?php _e('Selecione as áreas de atuação que deseja utilizar em seu site. Para utilizar todas, não selecione nenhuma.', 'wp-mapas') ?></strong><br>
        <?php foreach($area_terms as $area): ?>
            <label class="checkbox-selector"><input type="checkbox" name="MAPAS:agent:areas[]" value="<?php echo $area ?>" <?php if(in_array($area, $areas)) echo 'checked' ?> ><?php echo $area ?></label>
        <?php endforeach; ?>
    </div>


    <hr><h2><?php _e('Sincronização e filtros de espaços', 'wp-mapas') ?></h2>
    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = $plugin->getOption('space:import') ?: 'mine'; ?>
                <strong><?php _e('Importar', 'wp-mapas') ?></strong><br>
                <label><input type="radio" name="MAPAS:space:import" value="mine" <?php if($sync == 'mine') echo 'checked="checked"' ?>/> <?php _e('Somente os meus espaços', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="control" <?php if($sync == 'control') echo 'checked="checked"' ?>/> <?php _e('Somente os espaços que tenho permissão para editar', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="agents" <?php if($sync == 'agents') echo 'checked="checked"' ?>/> <?php _e('Somente os espaços publicados pelos agentes importados', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="all" <?php if($sync == 'all') echo 'checked="checked"' ?>/> <?php _e('Todos os espaços', 'wp-mapas') ?> (<?php _e('não recomentado se não combinado com outros filtros', 'wp-mapas') ?>)</label>
            </td>
        </tr>
    </table>

    <?php /* @todo enquanto não implementar a atribuição de selos às entidades não é possível utilizar o filtro abaixo
    <?php $verified = $plugin->getOption('space:verified') ?>
    <label><input type="checkbox" name="MAPAS:space:verified" <?php if($verified) echo 'checked' ?> /> <strong><?php _e("Somente espaços com selos verificadores") ?></strong> </label>
    */ ?>

    <div class="taxonomy-selector">
        <?php $types = $plugin->getOption('space:types') ?: []; ?>
        <strong><?php _e('Selecione os tipos de espaço que deseja utilizar em seu site. Para utilizar todos, não selecione nenhuma.', 'wp-mapas') ?></strong><br>
        <?php foreach($space_types as $type): ?>
            <label class="checkbox-selector"><input type="checkbox" name="MAPAS:space:types[]" value="<?php echo $type->id ?>" <?php if(in_array($type->id, $types)) echo 'checked' ?> ><?php echo $type->name ?></label>
        <?php endforeach; ?>
    </div>

    <div class="taxonomy-selector">
        <?php $areas = $plugin->getOption('space:areas') ?: []; ?>
        <strong><?php _e('Selecione as áreas de atuação que deseja utilizar em seu site. Para utilizar todas, não selecione nenhuma.', 'wp-mapas') ?></strong><br>
        <?php foreach($area_terms as $area): ?>
            <label class="checkbox-selector"><input type="checkbox" name="MAPAS:space:areas[]" value="<?php echo $area ?>" <?php if(in_array($area, $areas)) echo 'checked' ?> ><?php echo $area ?></label>
        <?php endforeach; ?>
    </div>


    <hr><h2><?php _e('Sincronização e filtros de eventos', 'wp-mapas') ?></h2>
    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = $plugin->getOption('event:import') ?: 'mine' ; ?>
                <strong><?php _e('Importar', 'wp-mapas') ?></strong><br>
                <label><input type="radio" name="MAPAS:event:import" value="mine" <?php if($sync == 'mine') echo 'checked="checked"' ?>/> <?php _e('Somente os meus eventos', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="control" <?php if($sync == 'control') echo 'checked="checked"' ?>/> <?php _e('Somente os eventos que tenho permissão para editar', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="agents" <?php if($sync == 'agents') echo 'checked="checked"' ?>/> <?php _e('Somente os eventos publicados pelos agentes importados', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:event:import" value="all" <?php if($sync == 'all') echo 'checked="checked"' ?>/> <?php _e('Todos os eventos', 'wp-mapas') ?> (<?php _e('não recomentado se não combinado com outros filtros', 'wp-mapas') ?>)</label>
            </td>
        </tr>
    </table>

    <?php /* @todo enquanto não implementar a atribuição de selos às entidades não é possível utilizar o filtro abaixo
    <?php $verified = $plugin->getOption('event:verified') ?>
    <label><input type="checkbox" name="MAPAS:event:verified" <?php if($verified) echo 'checked' ?> /> <strong><?php _e("Somente eventos com selos verificadores") ?></strong> </label>
    */ ?>
    <div class="taxonomy-selector">
        <?php $age_ratings = $plugin->getOption('event:age_ratings') ?: []; ?>
        <strong><?php _e('Selecione as classificações etárias que deseja utilizar em seu site. Para utilizar todas, não selecione nenhuma.', 'wp-mapas') ?></strong><br>
        <?php foreach($event_age_ratings as $rating): ?>
            <label class="checkbox-selector"><input type="checkbox" name="MAPAS:event:age_ratings[]" value="<?php echo $rating ?>" <?php if(in_array($rating, $age_ratings)) echo 'checked' ?> ><?php echo $rating ?></label>
        <?php endforeach; ?>
    </div>

    <div class="taxonomy-selector">
        <?php $languages = $plugin->getOption('event:languages') ?: []; ?>
        <strong><?php _e('Selecione as linguagens deseja utilizar em seu site. Para utilizar todas, não selecione nenhuma.', 'wp-mapas') ?></strong><br>
        <?php foreach($languages_terms as $area): ?>
            <label class="checkbox-selector"><input type="checkbox" name="MAPAS:event:languages[]" value="<?php echo $area ?>" <?php if(in_array($area, $languages)) echo 'checked' ?> ><?php echo $area ?></label>
        <?php endforeach; ?>
    </div>

    <?php submit_button(); ?>
    <button type="button" class="js-mapas--import-new-entities"><?php _e("Importar novas entidades", 'wp-mapas') ?></button>
    <p>
        <pre class="js-mapas--api-output"></pre>
    </p>
</form>
</div>