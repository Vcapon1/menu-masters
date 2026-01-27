<?php
/**
 * CARDÁPIO FLORIPA - Logout do Master Admin
 * 
 * Encerra a sessão do administrador master.
 */

session_start();
session_destroy();

header('Location: login.php');
exit;
