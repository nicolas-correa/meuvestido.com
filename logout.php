<?php
require_once 'includes/config.php';

// Destruir sessão
session_destroy();

// Redirecionar para a página inicial
header("Location: index.php");
exit;
?>
