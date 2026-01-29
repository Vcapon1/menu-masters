<?php
/**
 * CARDÁPIO FLORIPA - Landing Page Principal
 * 
 * Página inicial do sistema - design moderno e profissional.
 */

require_once __DIR__ . '/config/database.php';

// Estatísticas para exibição (opcional)
$stats = [
    'restaurants' => 0,
    'views' => 0
];

try {
    $stmt = db()->query("SELECT COUNT(*) as total FROM restaurants WHERE status = 'active'");
    $stats['restaurants'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = db()->query("SELECT COUNT(*) as total FROM access_stats WHERE accessed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['views'] = $stmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
    // Silently fail
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Cardápio Digital Profissional para Restaurantes</title>
    <meta name="description" content="Transforme seu cardápio em uma máquina de vendas. Cardápio digital moderno e elegante para restaurantes em Florianópolis.">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= APP_NAME ?> - Cardápio Digital Profissional">
    <meta property="og:description" content="Cardápio digital moderno para restaurantes. QR Code, personalização completa e gestão fácil.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --primary-glow: #fb923c;
            --secondary: #1c1917;
            --background: #0c0a09;
            --surface: #1c1917;
            --surface-light: #292524;
            --text: #fafaf9;
            --text-muted: #a8a29e;
            --border: #292524;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 2rem;
            background: rgba(12, 10, 9, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            transition: all 0.3s;
        }
        
        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        
        .logo img {
            height: 56px;
            width: auto;
        }
        
        .nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        
        .nav a:hover {
            color: var(--text);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 20px rgba(249, 115, 22, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(249, 115, 22, 0.5);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 12px;
        }
        
        /* Hero */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 8rem 2rem 4rem;
            overflow: hidden;
        }
        
        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1920&q=80') center/cover;
        }
        
        .hero-bg::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, var(--background) 0%, rgba(12, 10, 9, 0.8) 50%, rgba(12, 10, 9, 0.4) 100%);
        }
        
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, var(--background) 0%, transparent 30%, rgba(12, 10, 9, 0.3) 100%);
            z-index: 1;
        }
        
        .hero-glow {
            position: absolute;
            top: 50%;
            left: 25%;
            width: 600px;
            height: 600px;
            background: rgba(249, 115, 22, 0.2);
            border-radius: 50%;
            filter: blur(120px);
            transform: translateY(-50%);
            pointer-events: none;
        }
        
        .hero-inner {
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
            position: relative;
            z-index: 2;
        }
        
        .hero-content {
            max-width: 750px;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(249, 115, 22, 0.15);
            border: 1px solid rgba(249, 115, 22, 0.3);
            border-radius: 50px;
            color: var(--primary);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 2rem;
        }
        
        .hero-badge svg {
            width: 16px;
            height: 16px;
        }
        
        .hero h1 {
            font-family: 'Sora', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }
        
        .hero h1 span {
            color: var(--primary);
        }
        
        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            line-height: 1.7;
            max-width: 600px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .hero-features {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .hero-feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .hero-feature svg {
            color: var(--primary);
            width: 20px;
            height: 20px;
        }
        
        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
        }
        
        .scroll-indicator-inner {
            width: 24px;
            height: 40px;
            border: 2px solid rgba(168, 162, 158, 0.3);
            border-radius: 20px;
            display: flex;
            justify-content: center;
            padding-top: 8px;
        }
        
        .scroll-indicator-dot {
            width: 6px;
            height: 10px;
            background: var(--primary);
            border-radius: 3px;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            40% {
                transform: translateX(-50%) translateY(-10px);
            }
            60% {
                transform: translateX(-50%) translateY(-5px);
            }
        }
        
        /* Features */
        .features {
            padding: 6rem 2rem;
            background: var(--surface);
        }
        
        .features-inner {
            max-width: 1280px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 4rem;
        }
        
        .section-header h2 {
            font-family: 'Sora', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .section-header h2 span {
            color: var(--primary);
        }
        
        .section-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .feature-card {
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.1);
        }
        
        .feature-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .feature-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        /* How it Works */
        .how-it-works {
            padding: 6rem 2rem;
            background: var(--background);
        }
        
        .how-it-works-inner {
            max-width: 1280px;
            margin: 0 auto;
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-top: 4rem;
        }
        
        .step {
            text-align: center;
            position: relative;
        }
        
        .step::after {
            content: '→';
            position: absolute;
            right: -1.5rem;
            top: 40px;
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .step-number {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Sora', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }
        
        .step h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .step p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        /* Plans */
        .plans {
            padding: 6rem 2rem;
            background: var(--surface);
        }
        
        .plans-inner {
            max-width: 1280px;
            margin: 0 auto;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 4rem;
            align-items: start;
        }
        
        .plan-card {
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            position: relative;
            transition: all 0.5s;
            overflow: hidden;
        }
        
        .plan-card:hover {
            transform: translateY(-8px);
            border-color: rgba(249, 115, 22, 0.3);
        }
        
        .plan-card.featured {
            border: 2px solid rgba(249, 115, 22, 0.5);
            transform: scale(1.05);
            z-index: 10;
            box-shadow: 0 0 40px rgba(249, 115, 22, 0.2);
        }
        
        .plan-card.featured::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }
        
        .plan-badge {
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.375rem 1rem;
            border-radius: 0 0 8px 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .plan-name {
            font-family: 'Sora', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .plan-description {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .plan-price {
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }
        
        .plan-price-value {
            font-family: 'Sora', sans-serif;
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .plan-price-period {
            font-size: 1rem;
            color: var(--text-muted);
        }
        
        .plan-annual {
            color: var(--primary);
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }
        
        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .plan-features li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .plan-features li.included {
            color: var(--text-light);
        }
        
        .plan-features li.excluded {
            color: var(--text-muted);
            opacity: 0.5;
        }
        
        .feature-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .feature-icon.check {
            background: rgba(249, 115, 22, 0.2);
            color: var(--primary);
        }
        
        .feature-icon.x {
            background: var(--surface);
            color: var(--text-muted);
        }
        
        .feature-icon svg {
            width: 12px;
            height: 12px;
        }
        
        /* Contact */
        .contact {
            padding: 6rem 2rem;
            background: var(--background);
        }
        
        .contact-inner {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .contact h2 {
            font-family: 'Sora', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .contact p {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .contact-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        /* Footer */
        .footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 3rem 2rem;
        }
        
        .footer-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-text {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .footer-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        /* WhatsApp Float */
        .whatsapp-float {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .whatsapp-float:hover {
            transform: scale(1.1);
        }
        
        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .hero-content {
                max-width: 100%;
            }
            
            .hero h1 {
                font-size: 3rem;
            }
            
            .features-grid,
            .plans-grid {
                grid-template-columns: 1fr;
            }
            
            .steps {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .step::after {
                display: none;
            }
            
            .plan-card.featured {
                transform: none;
            }
        }
        
        @media (max-width: 768px) {
            .nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
            }
            
            .hero-features {
                flex-direction: column;
                gap: 1rem;
            }
            
            .steps {
                grid-template-columns: 1fr;
            }
            
            .section-header h2 {
                font-size: 1.75rem;
            }
            
            .footer-inner {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }
            
            .contact-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-inner">
            <a href="/" class="logo">
                <img src="<?= APP_URL ?>/assets/logo-cardapio-floripa.png" alt="<?= APP_NAME ?>">
            </a>
            <nav class="nav">
                <a href="#vantagens">Vantagens</a>
                <a href="#como-funciona">Como Funciona</a>
                <a href="#planos">Planos</a>
                <a href="#contato">Contato</a>
                <a href="<?= APP_URL ?>/admin/login.php" class="btn btn-primary">Comece Agora</a>
            </nav>
            <button class="mobile-menu-btn">☰</button>
        </div>
    </header>
    
    <!-- Hero -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-glow"></div>
        <div class="hero-inner">
            <div class="hero-content">
                <div class="hero-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.912 5.813L20 10l-4.796 3.396L17 19l-5-3.333L7 19l1.796-5.604L4 10l6.088-1.187L12 3z"/></svg>
                    Cardápio Digital Profissional
                </div>
                <h1>Transforme seu <span>cardápio</span><br>em uma <span>máquina de vendas</span></h1>
                <p>Cardápio digital moderno e elegante. Seus clientes escaneiam o QR Code e têm acesso instantâneo ao menu completo do seu restaurante.</p>
                <div class="hero-buttons">
                    <a href="https://wa.me/5548999999999?text=Olá! Gostaria de saber mais sobre o Cardápio Floripa" class="btn btn-primary btn-lg">
                        Começar Agora →
                    </a>
                    <a href="#como-funciona" class="btn btn-outline btn-lg">
                        Ver Demonstração
                    </a>
                </div>
                <div class="hero-features">
                    <div class="hero-feature">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Sem fidelidade
                    </div>
                    <div class="hero-feature">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Setup em 24h
                    </div>
                    <div class="hero-feature">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Suporte dedicado
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator">
            <div class="scroll-indicator-inner">
                <div class="scroll-indicator-dot"></div>
            </div>
        </div>
    </section>
    
    <!-- Features -->
    <section id="vantagens" class="features">
        <div class="features-inner">
            <div class="section-header">
                <h2>Por que escolher o <span>Cardápio Floripa</span>?</h2>
                <p>Tudo que você precisa para modernizar o atendimento do seu restaurante.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>QR Code Exclusivo</h3>
                    <p>Seus clientes acessam o cardápio escaneando um código único. Prático e higiênico.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>100% Personalizável</h3>
                    <p>Cores, logo, imagens e layout do seu jeito. Sua marca em destaque.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Atualização Instantânea</h3>
                    <p>Altere preços e produtos em tempo real. Sem custo de reimpressão.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Estatísticas Completas</h3>
                    <p>Saiba quais pratos são mais visualizados e otimize seu cardápio.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🖼️</div>
                    <h3>Fotos e Vídeos</h3>
                    <p>Apresente seus pratos com imagens profissionais e vídeos.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>Link Personalizado</h3>
                    <p>cardapiofloripa.com.br/seu-restaurante - fácil de lembrar e compartilhar.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- How it Works -->
    <section id="como-funciona" class="how-it-works">
        <div class="how-it-works-inner">
            <div class="section-header">
                <h2>Como <span>funciona</span>?</h2>
                <p>Em 4 passos simples você tem seu cardápio digital funcionando.</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Cadastro</h3>
                    <p>Crie sua conta e escolha seu plano.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Personalização</h3>
                    <p>Configure cores, logo e adicione seus produtos.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>QR Code</h3>
                    <p>Receba seu QR Code exclusivo para imprimir.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Pronto!</h3>
                    <p>Seus clientes já podem acessar o cardápio.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Plans -->
    <section id="planos" class="plans">
        <div class="plans-inner">
            <div class="section-header">
                <h2>Planos que cabem no seu <span>bolso</span></h2>
                <p>Escolha o plano ideal para o seu negócio.</p>
            </div>
            <div class="plans-grid">
                <!-- Basic Plan -->
                <div class="plan-card">
                    <div class="plan-name">Basic</div>
                    <p class="plan-description">Perfeito para começar seu cardápio digital</p>
                    <div class="plan-price">
                        <span class="plan-price-value">R$65</span>
                        <span class="plan-price-period">/mês</span>
                    </div>
                    <p class="plan-annual">ou R$45/mês no plano anual</p>
                    <ul class="plan-features">
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Lista completa de produtos
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Nome, preço e foto dos itens
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Clique para ampliar imagens
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            URL única + QR Code
                        </li>
                        <li class="excluded">
                            <span class="feature-icon x"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l6 6M9 3l-6 6"/></svg></span>
                            Filtro por categorias
                        </li>
                        <li class="excluded">
                            <span class="feature-icon x"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l6 6M9 3l-6 6"/></svg></span>
                            Ícones especiais (vegano, promoção)
                        </li>
                        <li class="excluded">
                            <span class="feature-icon x"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l6 6M9 3l-6 6"/></svg></span>
                            Vídeos nos produtos
                        </li>
                        <li class="excluded">
                            <span class="feature-icon x"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l6 6M9 3l-6 6"/></svg></span>
                            Logotipo e cores personalizadas
                        </li>
                    </ul>
                    <a href="https://wa.me/5548999999999?text=Olá! Quero assinar o plano Basic" class="btn btn-outline" style="width: 100%; justify-content: center;">
                        Começar agora
                    </a>
                </div>
                
                <!-- Premium Plan (Featured) -->
                <div class="plan-card featured">
                    <span class="plan-badge">Mais Popular</span>
                    <div class="plan-name">Premium</div>
                    <p class="plan-description">O mais completo para restaurantes exigentes</p>
                    <div class="plan-price">
                        <span class="plan-price-value">R$99</span>
                        <span class="plan-price-period">/mês</span>
                    </div>
                    <p class="plan-annual">ou R$79/mês no plano anual</p>
                    <ul class="plan-features">
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Tudo do plano Basic
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Filtro por categorias (estilo iFood)
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Ícones: promoção, vegano, destaque
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Vídeos por produto
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Logotipo do restaurante
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Cores personalizadas
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Indicador de mais pedidos
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Status de produto em falta
                        </li>
                    </ul>
                    <a href="https://wa.me/5548999999999?text=Olá! Quero assinar o plano Premium" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        Começar agora
                    </a>
                </div>
                
                <!-- Personalité Plan -->
                <div class="plan-card">
                    <div class="plan-name">Personalité</div>
                    <p class="plan-description">Layout exclusivo para sua marca</p>
                    <div class="plan-price">
                        <span class="plan-price-value">R$199</span>
                        <span class="plan-price-period">/mês</span>
                    </div>
                    <p class="plan-annual">ou R$149/mês no plano anual</p>
                    <ul class="plan-features">
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Tudo do plano Premium
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Layout totalmente personalizado
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Design exclusivo da sua marca
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Consultoria de design inclusa
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Suporte prioritário
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Integrações especiais
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Múltiplos cardápios
                        </li>
                        <li class="included">
                            <span class="feature-icon check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6l3 3 5-5"/></svg></span>
                            Relatórios de visualização
                        </li>
                    </ul>
                    <a href="https://wa.me/5548999999999?text=Olá! Quero assinar o plano Personalité" class="btn btn-outline" style="width: 100%; justify-content: center;">
                        Começar agora
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contact -->
    <section id="contato" class="contact">
        <div class="contact-inner">
            <h2>Pronto para começar?</h2>
            <p>Entre em contato conosco e tenha seu cardápio digital em até 24 horas.</p>
            <div class="contact-buttons">
                <a href="https://wa.me/5548999999999?text=Olá! Gostaria de saber mais sobre o Cardápio Floripa" class="btn btn-primary btn-lg">
                    💬 Falar no WhatsApp
                </a>
                <a href="mailto:contato@cardapiofloripa.com.br" class="btn btn-outline btn-lg">
                    ✉️ Enviar Email
                </a>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <p class="footer-text">© <?= date('Y') ?> <?= APP_NAME ?>. Feito com ❤️ em Florianópolis.</p>
            <div class="footer-links">
                <a href="/admin/login.php">Área do Cliente</a>
                <a href="/master/login.php">Master Admin</a>
            </div>
        </div>
    </footer>
    
    <!-- WhatsApp Float -->
    <a href="https://wa.me/5548999999999?text=Olá! Gostaria de saber mais sobre o Cardápio Floripa" class="whatsapp-float" target="_blank">
        💬
    </a>
</body>
</html>
