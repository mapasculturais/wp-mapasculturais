<?php
function mc_array_at ($array, $i, $fallback = NULL) {
    if (is_array($array) && array_key_exists($i, $array)) {
        return $array[$i];
    }
    return $fallback;
}

function mc_not_empty ($x) {
    return !empty($x);
}

function mc_format_address ($meta) {
    $addr1 = implode(', ', array_filter([
        implode('', mc_array_at($meta, 'En_Nome_Logradouro', [])),
        implode('', mc_array_at($meta, 'En_Num', [])),
        implode('', mc_array_at($meta, 'En_Complemento', [])),
        implode('', mc_array_at($meta, 'En_Bairro', [])),
        implode('', mc_array_at($meta, 'En_CEP', []))
    ], 'mc_not_empty'));
    $addr2 = implode(', ', array_filter([
        implode('', mc_array_at($meta, 'En_Municipio', [])),
        implode('', mc_array_at($meta, 'En_Estado', []))
    ], 'mc_not_empty'));
    return implode(' - ', array_filter([$addr1, $addr2], 'mc_not_empty'));
}
