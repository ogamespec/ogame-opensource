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
function dbquery(string $query) : mixed {
    global $dbQueryCalls, $mockDbResult;
    $dbQueryCalls[] = $query;
    return $mockDbResult;
}

/**
 * Мок функции dbarray для тестов
 */
function dbarray(mixed $result) : mixed {
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
function LoadUser(int $player_id) : array {
    global $mockUserData;
    return $mockUserData;
}

/**
 * Мок функции loca_add для тестов
 */
function loca_add(string $module, string $lang) : void {
    global $locaAddCalls;
    $locaAddCalls[] = $module;
}

/**
 * Мок функции loca для тестов
 */
function loca(string $key) : string {
    $translations = [
        'NOTE_NO_SUBJ' => 'No subject',
        'NOTE_NO_TEXT' => 'No text'
    ];
    return $translations[$key] ?? $key;
}

/**
 * Мок функции AddDBRow для тестов
 */
function AddDBRow(array $data, string $table) : int {
    global $addDBRowCalls, $lastAddDBRowData;
    $addDBRowCalls++;
    $lastAddDBRowData = $data;
    return $addDBRowCalls;
}