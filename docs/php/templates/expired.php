<?php
/**
 * CARDÁPIO FLORIPA - Página de Plano Expirado
 * 
 * Exibida quando o plano do restaurante expirou.
 * Variável disponível: $restaurant (dados do restaurante)
 */

require_once __DIR__ . '/../config/database.php';

$restaurantName = isset($restaurant['name']) ? htmlspecialchars($restaurant['name']) : 'Este restaurante';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio Indisponível - <?= APP_NAME ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #f97316;
            --secondary: #1f2937;
            --warning: #f59e0b;
            --background: #fafafa;
            --text: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            text-align: center;
            max-width: 450px;
        }
        
        .icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--secondary);
        }
        
        .restaurant-name {
            color: var(--primary);
            font-weight: 600;
        }
        
        p {
            color: var(--text-light);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .notice {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 12px;
            padding: 1rem;
            margin: 1.5rem 0;
        }
        
        .notice p {
            color: #92400e;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #ea580c);
            color: var(--white);
            box-shadow: 0 4px 14px rgba(249, 115, 22, 0.35);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.45);
        }
        
        .btn-secondary {
            background: var(--white);
            color: var(--text);
            border: 2px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .footer {
            margin-top: 3rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }
        
        .footer a {
            color: var(--primary);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⏰</div>
        <h1>Cardápio Temporariamente Indisponível</h1>
        <p>O cardápio de <span class="restaurant-name"><?= $restaurantName ?></span> está temporariamente fora do ar.</p>
        
        <div class="notice">
            <p>⚠️ <strong>Proprietário do restaurante?</strong><br>
            Entre em contato conosco para renovar seu plano e reativar seu cardápio.</p>
        </div>
        
        <div class="buttons">
            <a href="https://wa.me/5548999999999?text=Olá! Preciso renovar meu plano do Cardápio Floripa" class="btn btn-primary">
                📱 Renovar Plano
            </a>
            <a href="/" class="btn btn-secondary">
                🏠 Ir para o Início
            </a>
        </div>
        
        <div class="footer">
            <p>Suporte: <a href="mailto:contato@cardapiofloripa.com.br">contato@cardapiofloripa.com.br</a></p>
        </div>
    </div>
</body>
</html>
