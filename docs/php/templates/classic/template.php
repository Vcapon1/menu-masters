<?php
/**
 * CARDÁPIO FLORIPA - Template Classic
 * 
 * Template padrão/fallback com design limpo e elegante.
 * 
 * Variáveis disponíveis:
 * - $restaurant: array com dados do restaurante
 * - $categories: array de categorias
 * - $products: array de produtos
 * - $productsByCategory: produtos agrupados por categoria
 * - $customCss: CSS variables dinâmicas
 */

// Configurações de cores com fallback
$primaryColor = $restaurant['primary_color'] ?? '#1f2937';
$secondaryColor = $restaurant['secondary_color'] ?? '#f59e0b';
$accentColor = $restaurant['accent_color'] ?? '#d97706';
$buttonColor = $restaurant['button_color'] ?? '#f59e0b';
$buttonTextColor = $restaurant['button_text_color'] ?? '#1f2937';
$fontColor = $restaurant['font_color'] ?? '#1f2937';
$backgroundColor = $restaurant['background_color'] ?? '#ffffff';

// Dados do restaurante
$restaurantName = htmlspecialchars($restaurant['name'] ?? 'Restaurante');
$logo = $restaurant['logo'] ?? '';
$banner = $restaurant['banner'] ?? '';
$address = htmlspecialchars($restaurant['address'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $restaurantName ?> - Cardápio Digital</title>
    
    <!-- SEO -->
    <meta name="description" content="Cardápio digital de <?= $restaurantName ?>. Veja nossos pratos e faça seu pedido.">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $restaurantName ?> - Cardápio">
    <meta property="og:description" content="Confira nosso cardápio digital">
    <?php if ($banner): ?>
    <meta property="og:image" content="<?= $banner ?>">
    <?php endif; ?>
    
    <!-- CSS Variables Dinâmicas -->
    <?= $customCss ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: <?= $primaryColor ?>;
            --secondary: <?= $secondaryColor ?>;
            --accent: <?= $accentColor ?>;
            --button: <?= $buttonColor ?>;
            --button-text: <?= $buttonTextColor ?>;
            --font: <?= $fontColor ?>;
            --bg: <?= $backgroundColor ?>;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: var(--bg);
            color: var(--font);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: var(--primary);
            color: #ffffff;
            padding: 2rem 1rem;
            text-align: center;
        }
        
        .logo {
            max-width: 120px;
            max-height: 120px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .header .address {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        /* Navigation */
        .categories-nav {
            background: #ffffff;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #e5e7eb;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }
        
        .categories-nav::-webkit-scrollbar {
            display: none;
        }
        
        .category-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            border: 2px solid var(--secondary);
            border-radius: 25px;
            background: transparent;
            color: var(--font);
            font-size: 0.9rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: var(--secondary);
            color: var(--button-text);
        }
        
        /* Main Content */
        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }
        
        /* Category Section */
        .category-section {
            margin-bottom: 2rem;
        }
        
        .category-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary);
        }
        
        /* Product Card */
        .product-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: #ffffff;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: box-shadow 0.2s;
        }
        
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .product-image-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }
        
        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--font);
            margin-bottom: 0.25rem;
        }
        
        .product-description {
            font-size: 0.9rem;
            color: #6b7280;
            flex: 1;
            margin-bottom: 0.5rem;
        }
        
        .product-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            background: var(--accent);
            color: #ffffff;
        }
        
        .product-price {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        /* Sizes Prices */
        .product-sizes {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 0.5rem;
        }
        
        .size-price-chip {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid var(--secondary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .size-price-chip .size-label {
            color: var(--font);
            opacity: 0.8;
            margin-right: 4px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 2rem 1rem;
            background: var(--primary);
            color: #ffffff;
            margin-top: 2rem;
        }
        
        .footer a {
            color: var(--secondary);
            text-decoration: none;
        }
        
        /* Mobile adjustments */
        @media (max-width: 480px) {
            .product-card {
                flex-direction: column;
            }
            
            .product-image,
            .product-image-placeholder {
                width: 100%;
                height: 180px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <?php if ($logo): ?>
        <img src="<?= $logo ?>" alt="<?= $restaurantName ?>" class="logo">
        <?php endif; ?>
        <h1><?= $restaurantName ?></h1>
        <?php if ($address): ?>
        <p class="address">📍 <?= $address ?></p>
        <?php endif; ?>
    </header>

    <!-- Category Navigation -->
    <nav class="categories-nav">
        <button class="category-btn active" data-category="all">Todos</button>
        <?php foreach ($categories as $category): ?>
        <button class="category-btn" data-category="<?= $category['id'] ?>">
            <?= htmlspecialchars($category['name']) ?>
        </button>
        <?php endforeach; ?>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <?php foreach ($categories as $category): ?>
        <?php if (isset($productsByCategory[$category['id']]) && count($productsByCategory[$category['id']]) > 0): ?>
        <section class="category-section" data-category-section="<?= $category['id'] ?>">
            <h2 class="category-title"><?= htmlspecialchars($category['name']) ?></h2>
            
            <?php foreach ($productsByCategory[$category['id']] as $product): ?>
            <article class="product-card" data-category="<?= $product['category_id'] ?>">
                <?php if (!empty($product['image'])): ?>
                <img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                <?php else: ?>
                <div class="product-image-placeholder">🍽️</div>
                <?php endif; ?>
                
                <div class="product-info">
                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                    
                    <?php if (!empty($product['description'])): ?>
                    <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                    <?php endif; ?>
                    
                    <?php 
                    $badges = !empty($product['badges']) ? json_decode($product['badges'], true) : [];
                    if (!empty($badges)): 
                    ?>
                    <div class="product-badges">
                        <?php foreach ($badges as $badge): ?>
                        <span class="badge"><?= ucfirst($badge) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <p class="product-price"><?= formatPrice($product['price']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
        <?php endforeach; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>Cardápio digital por <a href="<?= APP_URL ?>" target="_blank"><?= APP_NAME ?></a></p>
    </footer>

    <script>
        // Category filter
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;
                
                // Update active button
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Filter sections
                document.querySelectorAll('.category-section').forEach(section => {
                    if (category === 'all' || section.dataset.categorySection === category) {
                        section.style.display = '';
                    } else {
                        section.style.display = 'none';
                    }
                });
                
                // Smooth scroll to top of content
                if (category !== 'all') {
                    const targetSection = document.querySelector(`[data-category-section="${category}"]`);
                    if (targetSection) {
                        targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    </script>
</body>
</html>
