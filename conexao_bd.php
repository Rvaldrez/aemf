<?php
require_once 'config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

$mysqli->set_charset("utf8");
