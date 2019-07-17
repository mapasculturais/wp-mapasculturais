<mc-w-now
    :show-filters="<?= $filters ? 'true' : 'false' ?>"
    <?= empty($agents) ? '' : ' agents="'.$agents.'"' ?>
    <?= empty($spaces) ? '' : ' spaces="'.$spaces.'"' ?>
></mc-w-now>