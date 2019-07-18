<?php
namespace WPMapasCulturais;
$plugin = Plugin::instance();
$area_terms = $plugin->api->getTaxonomyTerms('area');
$agent_types = $plugin->api->mapasApi->getEntityTypes('agent');

?>
<div class="wrap">
<h1><?php _e('Configuração de sincronização e filtros de agentes', 'wp-mapas') ?></h1>
<form method="post" action="options.php"> 
    <?php settings_fields( 'mapasculturais_agents' ); ?>
    <?php do_settings_sections( 'mapasculturais_agents' ); ?>
    
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
    <?php submit_button(); ?>
</form>
</div>