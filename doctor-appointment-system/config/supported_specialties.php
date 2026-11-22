<?php

function edoc_supported_specialty_map()
{
    static $map = null;
    if (is_array($map)) {
        return $map;
    }
    $path = __DIR__ . '/specialty_model_mapping.json';
    if (!is_readable($path)) {
        $map = array();
        return $map;
    }
    $raw = file_get_contents($path);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || empty($decoded['id_to_name']) || !is_array($decoded['id_to_name'])) {
        $map = array();
        return $map;
    }
    $out = array();
    foreach ($decoded['id_to_name'] as $id => $name) {
        $out[(int) $id] = (string) $name;
    }
    ksort($out);
    $map = $out;
    return $map;
}

function edoc_supported_specialty_ids()
{
    return array_keys(edoc_supported_specialty_map());
}

function edoc_is_supported_specialty($id)
{
    return array_key_exists((int) $id, edoc_supported_specialty_map());
}
