<?php

function edoc_is_demo_database()
{
    if (!function_exists('edoc_env')) {
        return false;
    }
    $name = edoc_env('DB_NAME');
    if ($name === false || $name === '') {
        $name = 'SQL_Database_edoc';
    }
    return strcasecmp((string) $name, 'SQL_Database_edoc_demo') === 0;
}

function edoc_password_is_hash($stored)
{
    if (!is_string($stored) || $stored === '') {
        return false;
    }
    return (bool) preg_match('/^\$(2[ayb]|argon2id|argon2i)\$/', $stored);
}

function edoc_store_password($plain)
{
    $plain = (string) $plain;
    if ($plain === '') {
        error_log('Refusing to hash an empty password');
        return false;
    }
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    if ($hash === false) {
        error_log('password_hash failed');
        return false;
    }
    return $hash;
}

function edoc_password_ok($plain, $stored)
{
    if (!is_string($stored) || $stored === '') {
        return false;
    }
    $plain = (string) $plain;
    if (edoc_password_is_hash($stored)) {
        return password_verify($plain, $stored);
    }
    if (function_exists('hash_equals')) {
        return hash_equals($stored, $plain);
    }
    return $stored === $plain;
}

function edoc_password_needs_storage_upgrade($stored)
{
    if (!is_string($stored) || $stored === '') {
        return false;
    }
    if (!edoc_password_is_hash($stored)) {
        return true;
    }
    return password_needs_rehash($stored, PASSWORD_DEFAULT);
}

function edoc_password_persist_hash($database, $table, $column, $emailColumn, $email, $plain)
{
    if (!($database instanceof mysqli) || $database->connect_error) {
        return false;
    }
    $allowed = array(
        'patient' => array('ppassword', 'pemail'),
        'admin' => array('apassword', 'aemail'),
        'doctor' => array('docpassword', 'docemail'),
    );
    if (!isset($allowed[$table])) {
        return false;
    }
    if ($allowed[$table][0] !== $column || $allowed[$table][1] !== $emailColumn) {
        return false;
    }
    $hash = edoc_store_password($plain);
    if ($hash === false) {
        return false;
    }
    $sql = 'UPDATE `' . $table . '` SET `' . $column . '` = ? WHERE `' . $emailColumn . '` = ? LIMIT 1';
    $stmt = $database->prepare($sql);
    if ($stmt === false) {
        error_log('Password upgrade prepare failed');
        return false;
    }
    $stmt->bind_param('ss', $hash, $email);
    $ok = $stmt->execute();
    if ($ok !== true) {
        error_log('Password upgrade execute failed');
    }
    $stmt->close();
    return $ok === true;
}

function edoc_password_upgrade_after_success($database, $plain, $stored, $table, $column, $emailColumn, $email)
{
    if (!edoc_password_ok($plain, $stored)) {
        return false;
    }
    if (!edoc_password_needs_storage_upgrade($stored)) {
        return true;
    }
    return edoc_password_persist_hash($database, $table, $column, $emailColumn, $email, $plain);
}
