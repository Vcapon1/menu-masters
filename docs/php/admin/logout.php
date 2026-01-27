<?php
/**
 * CARDÁPIO FLORIPA - Logout do Admin
 * 
 * Encerra a sessão do restaurante.
 */

session_start();
session_destroy();

header('Location: login.php');
exit;
