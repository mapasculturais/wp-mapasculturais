<?php
namespace WPMapasCulturais;
$plugin = Plugin::instance();

$languages_terms = $plugin->api->getTaxonomyTerms('linguagem');
$event_metadata = $plugin->getEntityMetadataDescription('event');
$event_age_ratings = (array) $event_metadata['classificacaoEtaria']->options;
sort($event_age_ratings);
?>
<div class="wrap">
<h1><?php _e('Configuração de sincronização e filtros de eventos', 'wp-mapas') ?></h1>
<form method="post" action="options.php"> 
    <?php settings_fields( 'mapasculturais_events' ); ?>
    <?php do_settings_sections( 'mapasculturais_events' ); ?>
    
    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = $plugin->getOption('event:import', 'mine'); ?>
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
</form>
</div>