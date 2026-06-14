<?php
session_start();

// Destrói todos os dados da sessão
$_SESSION = [];

// Remove o cookie de sessão do navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Redireciona para o login com confirmação visual
header("Location: index.HTML?msg=logout_ok");
exit();
?>
