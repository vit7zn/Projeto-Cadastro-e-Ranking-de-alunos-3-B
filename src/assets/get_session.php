<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'nome'          => $_SESSION['nome'] ?? null,
    'autenticado'   => isset($_SESSION['usuario_id'])
]);
