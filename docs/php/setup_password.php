<?php
/**
 * SCRIPT DE CONFIGURAÇÃO DE SENHA
 * 
 * Execute este arquivo UMA VEZ para gerar a hash correta.
 * DEPOIS DELETE ESTE ARQUIVO do servidor!
 */

require_once __DIR__ . '/config/database.php';

$senha = 'admin123';
$hash = password_hash($senha, PASSWORD_BCRYPT);

echo "<h2>Gerando Hash BCrypt para: {$senha}</h2>";
echo "<p><strong>Hash gerada:</strong> {$hash}</p>";

// Tentar atualizar o banco
try {
    $sql = "UPDATE master_admins SET password_hash = :hash WHERE email = 'admin@cardapiofloripa.com.br'";
    $stmt = db()->prepare($sql);
    $result = $stmt->execute(['hash' => $hash]);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✓ Senha atualizada com sucesso!</p>";
        echo "<p>Agora acesse: <a href='/master/login.php'>/master/login.php</a></p>";
        echo "<p>Email: <strong>admin@cardapiofloripa.com.br</strong></p>";
        echo "<p>Senha: <strong>admin123</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Erro ao atualizar.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p style='color: orange;'><strong>⚠️ IMPORTANTE: Delete este arquivo após usar!</strong></p>";
