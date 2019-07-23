<?php 
namespace WPMapasCulturais;
$plugin = Plugin::instance();
$area_terms = $plugin->api->getTaxonomyTerms('area');
$space_types = $plugin->api->mapasApi->getEntityTypes('space');

?>
<div class="wrap">
<h1><?php _e('Configuração de sincronização e filtros de espaços', 'wp-mapas') ?></h1>
<form method="post" action="options.php"> 
    <?php settings_fields( 'mapasculturais_spaces' ); ?>
    <?php do_settings_sections( 'mapasculturais_spaces' ); ?>

    <table class="form-table">
        <tr valign="top">
            <td colspan='2'>
                <?php $sync = $plugin->getOption('space:import', 'mine'); ?>
                <strong><?php _e('Importar', 'wp-mapas') ?></strong><br>
                <label><input type="radio" name="MAPAS:space:import" value="mine" <?php if($sync == 'mine') echo 'checked="checked"' ?>/> <?php _e('Somente os meus espaços', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="control" <?php if($sync == 'control') echo 'checked="checked"' ?>/> <?php _e('Somente os espaços que tenho permissão para editar', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="agents" <?php if($sync == 'agents') echo 'checked="checked"' ?>/> <?php _e('Somente os espaços publicados pelos agentes importados', 'wp-mapas') ?></label><br>
                <label><input type="radio" name="MAPAS:space:import" value="all" <?php if($sync == 'all') echo 'checked="checked"' ?>/> <?php _e('Todos os espaços', 'wp-mapas') ?> (<?php _e('não recomentado se não combinado com outros filtros', 'wp-mapas') ?>)</label>
            </td>
        </tr>
    </table>
    
    <?php $auto_import = $plugin->getOption('space:auto_import') ?>
    <label><input type="checkbox" name="MAPAS:space:auto_import" <?php if($auto_import) echo 'checked' ?> /> <strong><?php _e("Importar espaços automaticamente") ?></strong> </label>

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
    <?php submit_button(); ?>
</form>
</div>