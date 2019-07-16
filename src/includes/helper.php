<?php
function mc_implode_if_array ($glue, $pieces) {
    if (is_array($pieces)) {
        return implode($glue, $pieces);
    }
    return '';
}
