<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// If file exists → serve normally
if ($uri !== '/' && file_exists($file)) {
    return false;
}

// Otherwise → send everything to index.php
require_once __DIR__ . '/public/index.php';
