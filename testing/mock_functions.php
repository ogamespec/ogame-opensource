<?php
// tests/mock_functions.php

// Глобальные переменные для моков
$mockDbResult = null;
$mockUserData = ['lang' => 'en', 'admin' => 0];
$dbQueryCalls = [];
$addDBRowCalls = 0;
$lastAddDBRowData = null;
$locaAddCalls = [];

/**
 * Мок функции dbquery для тестов
 */
function dbquery($query) {
    global $dbQueryCalls, $mockDbResult;
    $dbQueryCalls[] = $query;
    return $mockDbResult;
}

/**
 * Мок функции dbarray для тестов
 */
function dbarray($result) {
    if ($result && is_object($result) && isset($result->data)) {
        if (!$result->fetched) {
            $result->fetched = true;
            return $result->data;
        }
    }
    return false;
}

/**
 * Мок функции LoadUser для тестов
 */
function LoadUser($player_id) {
    global $mockUserData;
    return $mockUserData;
}

/**
 * Мок функции loca_add для тестов
 */
function loca_add($module, $lang) {
    global $locaAddCalls;
    $locaAddCalls[] = $module;
}

/**
 * Мок функции loca для тестов
 */
function loca($key) {
    $translations = [
        'NOTE_NO_SUBJ' => 'No subject',
        'NOTE_NO_TEXT' => 'No text'
    ];
    return $translations[$key] ?? $key;
}

/**
 * Мок функции AddDBRow для тестов
 */
function AddDBRow($data, $table) {
    global $addDBRowCalls, $lastAddDBRowData;
    $addDBRowCalls++;
    $lastAddDBRowData = $data;
    return true;
}