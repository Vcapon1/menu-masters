<?php
/**
 * CARDÁPIO FLORIPA - Landing Page
 * 
 * Página inicial do sistema quando acessado sem slug de restaurante.
 * Redireciona para o diretório ou mostra informações sobre o serviço.
 */

require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Cardápio Digital para Restaurantes</title>
    <meta name="description" content="Cardápio digital moderno para restaurantes em Florianópolis. QR Code, personalização completa e gestão fácil.">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= APP_NAME ?>">
    <meta property="og:description" content="Cardápio digital moderno para restaurantes">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/favicon.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --secondary: #1f2937;
            --accent: #f59e0b;
            --background: #fafafa;
            --text: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .header {
            background: var(--white);
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text);
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.25rem;
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .header-nav {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .header-nav a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .header-nav a:hover {
            color: var(--primary);
        }
        
        /* Hero */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, var(--white) 0%, #fff7ed 100%);
        }
        
        .hero-content {
            max-width: 600px;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--secondary);
        }
        
        .hero h1 span {
            color: var(--primary);
        }
        
        .hero p {
            font-size: 1.125rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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
        
        /* Features */
        .features {
            padding: 4rem 2rem;
            background: var(--white);
        }
        
        .features-grid {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            padding: 1.5rem;
            border-radius: 16px;
            background: var(--background);
            text-align: center;
        }
        
        .feature-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .feature-card h3 {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }
        
        .feature-card p {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        /* Footer */
        .footer {
            background: var(--secondary);
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }
        
        .footer p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .footer a {
            color: var(--accent);
            text-decoration: none;
        }
        
        /* Mobile */
        @media (max-width: 640px) {
            .header {
                padding: 1rem;
            }
            
            .header-nav {
                display: none;
            }
            
            .hero h1 {
                font-size: 1.75rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="/" class="logo">
            <div class="logo-icon">🍽️</div>
            <span class="logo-text"><?= APP_NAME ?></span>
        </a>
        <nav class="header-nav">
            <a href="/diretorio">Restaurantes</a>
            <a href="/admin">Área do Cliente</a>
            <a href="https://wa.me/5548999999999" target="_blank">Contato</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Cardápio Digital <span>Moderno</span> para seu Restaurante</h1>
            <p>Crie seu cardápio online em minutos. QR Code, personalização completa e atualizações em tempo real.</p>
            <div class="hero-buttons">
                <a href="https://wa.me/5548999999999?text=Olá! Gostaria de saber mais sobre o Cardápio Floripa" class="btn btn-primary">
                    📱 Começar Agora
                </a>
                <a href="/diretorio" class="btn btn-secondary">
                    🔍 Ver Restaurantes
                </a>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>QR Code Exclusivo</h3>
                <p>Seus clientes acessam o cardápio escaneando um código único.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3>100% Personalizável</h3>
                <p>Cores, logo, imagens e layout do seu jeito.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Atualização Instantânea</h3>
                <p>Altere preços e produtos em tempo real.</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Feito com ❤️ em Florianópolis.</p>
        <p><a href="/admin">Área Administrativa</a> | <a href="/master">Master Admin</a></p>
    </footer>
</body>
</html>
