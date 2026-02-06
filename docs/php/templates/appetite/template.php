<?php
/**
 * Template: Appetite
 * Style: Clean, mobile-first, inspired by iFood/McDonald's
 * Min Plan: Basic
 * Features: Category scroll, List/Grid view, Video support, Promotional pricing
 */

// Restaurant data is passed from the main router
// $restaurant, $categories, $products are available

$primaryColor = $restaurant['primary_color'] ?? '#f97316';
$secondaryColor = $restaurant['secondary_color'] ?? '#1f2937';
$backgroundColor = $restaurant['background_color'] ?? '#fafafa';
$accentColor = $restaurant['accent_color'] ?? '#f59e0b';
$buttonColor = $restaurant['button_color'] ?? '#f97316';
$buttonTextColor = $restaurant['button_text_color'] ?? '#ffffff';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="<?= htmlspecialchars($primaryColor) ?>">
    <title><?= htmlspecialchars($restaurant['name']) ?> - Cardápio Digital</title>
    <meta name="description" content="Cardápio digital de <?= htmlspecialchars($restaurant['name']) ?>. Veja nosso menu completo e faça seu pedido!">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.min.css">
    
    <style>
        :root {
            --primary: <?= htmlspecialchars($primaryColor) ?>;
            --secondary: <?= htmlspecialchars($secondaryColor) ?>;
            --background: <?= htmlspecialchars($backgroundColor) ?>;
            --accent: <?= htmlspecialchars($accentColor) ?>;
            --button: <?= htmlspecialchars($buttonColor) ?>;
            --button-text: <?= htmlspecialchars($buttonTextColor) ?>;
            --card: #ffffff;
            --muted: #f3f4f6;
            --muted-foreground: #6b7280;
            --border: #e5e7eb;
            --success: #22c55e;
            --promo: #ef4444;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--background);
            color: var(--secondary);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 12px 16px;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo {
            height: 40px;
            max-width: 140px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            padding: 4px 8px;
        }
        
        .logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .restaurant-info {
            flex: 1;
            min-width: 0;
        }
        
        .restaurant-name {
            font-weight: 600;
            font-size: 18px;
            color: var(--secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .restaurant-address {
            font-size: 13px;
            color: var(--muted-foreground);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            flex-shrink: 0;
        }
        
        .status-open {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }
        
        .status-closed {
            background: rgba(239, 68, 68, 0.1);
            color: var(--promo);
        }
        
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Category Navigation */
        .category-nav {
            position: sticky;
            top: 68px;
            z-index: 30;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            overflow: hidden;
        }
        
        .category-scroll {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .category-scroll::-webkit-scrollbar {
            display: none;
        }
        
        .category-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 999px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--muted);
            color: var(--muted-foreground);
        }
        
        .category-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: scale(1.02);
        }
        
        .category-btn:hover:not(.active) {
            background: #e5e7eb;
        }
        
        /* Toolbar */
        .toolbar {
            position: sticky;
            top: 124px;
            z-index: 20;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-bottom: 1px solid var(--border);
        }
        
        .search-container {
            flex: 1;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            border-radius: 999px;
            border: none;
            background: var(--muted);
            font-size: 14px;
            outline: none;
        }
        
        .search-input:focus {
            box-shadow: 0 0 0 2px var(--primary);
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted-foreground);
            font-size: 16px;
        }
        
        .view-toggle {
            display: flex;
            background: var(--muted);
            border-radius: 999px;
            padding: 4px;
        }
        
        .view-btn {
            padding: 6px;
            border-radius: 999px;
            border: none;
            background: transparent;
            color: var(--muted-foreground);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .view-btn.active {
            background: white;
            color: var(--secondary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* Content */
        .content {
            padding: 16px;
            padding-bottom: 100px;
        }
        
        .section {
            margin-bottom: 32px;
            scroll-margin-top: 160px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .section-icon {
            font-size: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .section-line {
            flex: 1;
            height: 1px;
            background: var(--border);
            margin-left: 8px;
        }
        
        /* Product Cards - List View */
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .product-card {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.2s;
        }
        
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .product-card.unavailable {
            opacity: 0.6;
        }
        
        .product-inner {
            display: flex;
            padding: 12px;
            gap: 12px;
        }
        
        .product-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .product-badges {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        
        .badge {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-promo {
            background: rgba(239, 68, 68, 0.1);
            color: var(--promo);
        }
        
        .badge-popular {
            background: rgba(249, 115, 22, 0.1);
            color: var(--primary);
        }
        
        .badge-vegan {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }
        
        .badge-highlight {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent);
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--secondary);
            line-height: 1.3;
        }
        
        .product-description {
            font-size: 14px;
            color: var(--muted-foreground);
            margin-top: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .price-old {
            font-size: 14px;
            color: var(--muted-foreground);
            text-decoration: line-through;
        }
        
        .price-current {
            font-size: 18px;
            font-weight: 700;
        }
        
        .price-promo {
            color: var(--promo);
        }
        
        /* Sizes Prices */
        .product-sizes {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .size-price-chip {
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .size-price-chip .size-label {
            color: var(--secondary);
            margin-right: 4px;
        }
        
        .product-image-container {
            position: relative;
            flex-shrink: 0;
        }
        
        .product-image {
            width: 96px;
            height: 96px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .add-btn {
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--button);
            color: var(--button-text);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        
        .add-btn:hover {
            transform: scale(1.1);
        }
        
        .unavailable-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        .unavailable-text {
            background: var(--muted);
            color: var(--muted-foreground);
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 500;
        }
        
        /* Product Cards - Grid View */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .products-grid .product-card .product-inner {
            flex-direction: column;
            padding: 0;
        }
        
        .products-grid .product-image {
            width: 100%;
            height: 0;
            padding-bottom: 100%;
            position: relative;
            border-radius: 12px 12px 0 0;
        }
        
        .products-grid .product-image img {
            position: absolute;
            inset: 0;
        }
        
        .products-grid .product-info {
            padding: 12px;
        }
        
        .products-grid .product-name {
            font-size: 14px;
        }
        
        .products-grid .product-description {
            font-size: 12px;
            min-height: 32px;
        }
        
        .products-grid .product-price {
            justify-content: space-between;
        }
        
        .products-grid .price-current {
            font-size: 16px;
        }
        
        .products-grid .add-btn {
            position: static;
            width: 32px;
            height: 32px;
        }
        
        /* WhatsApp Float */
        .whatsapp-float {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
            cursor: pointer;
            transition: transform 0.2s;
            z-index: 50;
            text-decoration: none;
        }
        
        .whatsapp-float:hover {
            transform: scale(1.1);
        }
        
        .whatsapp-float i {
            font-size: 28px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 16px;
            color: var(--muted-foreground);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Responsive */
        @media (min-width: 640px) {
            .content {
                max-width: 480px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <?php if (!empty($restaurant['logo'])): ?>
                    <img src="<?= htmlspecialchars($restaurant['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                <?php else: ?>
                    <?= strtoupper(substr($restaurant['name'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            
            <div class="restaurant-info">
                <h1 class="restaurant-name"><?= htmlspecialchars($restaurant['name']) ?></h1>
            </div>
            
            <div class="status-badge status-open">
                <span class="status-dot"></span>
                <span>Aberto</span>
            </div>
        </div>
    </header>
    
    <!-- Category Navigation -->
    <nav class="category-nav">
        <div class="category-scroll">
            <button class="category-btn active" data-category="all">
                <span>🍽️</span>
                <span>Todos</span>
            </button>
            <?php foreach ($categories as $category): ?>
                <button class="category-btn" data-category="<?= $category['id'] ?>">
                    <?php if (!empty($category['icon'])): ?>
                        <span><?= htmlspecialchars($category['icon']) ?></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($category['name']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </nav>
    
    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-container">
            <i class="lucide-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Buscar pratos..." id="searchInput">
        </div>
        <div class="view-toggle">
            <button class="view-btn active" data-view="list" title="Lista">
                <i class="lucide-list"></i>
            </button>
            <button class="view-btn" data-view="grid" title="Grade">
                <i class="lucide-grid-3x3"></i>
            </button>
        </div>
    </div>
    
    <!-- Content -->
    <main class="content" id="menuContent">
        <?php
        // Group products by category
        $productsByCategory = [];
        foreach ($products as $product) {
            $catId = $product['category_id'];
            if (!isset($productsByCategory[$catId])) {
                $productsByCategory[$catId] = [];
            }
            $productsByCategory[$catId][] = $product;
        }
        ?>
        
        <?php foreach ($categories as $category): ?>
            <?php if (!empty($productsByCategory[$category['id']])): ?>
                <section class="section" data-section="<?= $category['id'] ?>">
                    <div class="section-header">
                        <?php if (!empty($category['icon'])): ?>
                            <span class="section-icon"><?= htmlspecialchars($category['icon']) ?></span>
                        <?php endif; ?>
                        <h2 class="section-title"><?= htmlspecialchars($category['name']) ?></h2>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="products-list" data-category="<?= $category['id'] ?>">
                        <?php foreach ($productsByCategory[$category['id']] as $product): ?>
                            <?php
                            $isAvailable = $product['is_available'] ?? true;
                            $hasPromo = !empty($product['promo_price']) && $product['promo_price'] < $product['price'];
                            $badges = json_decode($product['badges'] ?? '[]', true) ?: [];
                            ?>
                            <article class="product-card <?= !$isAvailable ? 'unavailable' : '' ?> animate-in" data-product="<?= $product['id'] ?>">
                                <div class="product-inner">
                                    <div class="product-info">
                                        <?php if (!empty($badges)): ?>
                                            <div class="product-badges">
                                                <?php foreach ($badges as $badge): ?>
                                                    <span class="badge badge-<?= htmlspecialchars($badge) ?>"><?= htmlspecialchars(ucfirst($badge)) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                        <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                        
                                        <div class="product-price">
                                            <?php 
                                            $sizesPrices = json_decode($product['sizes_prices'] ?? 'null', true);
                                            if ($sizesPrices && is_array($sizesPrices) && count($sizesPrices) > 0): 
                                            ?>
                                                <div class="product-sizes">
                                                    <?php foreach ($sizesPrices as $size): ?>
                                                        <span class="size-price-chip">
                                                            <span class="size-label"><?= htmlspecialchars($size['label']) ?></span>
                                                            R$ <?= number_format($size['price'], 2, ',', '.') ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif ($hasPromo): ?>
                                                <span class="price-old">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                                <span class="price-current price-promo">R$ <?= number_format($product['promo_price'], 2, ',', '.') ?></span>
                                            <?php else: ?>
                                                <span class="price-current">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="product-image-container">
                                        <div class="product-image">
                                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                                        </div>
                                        
                                        <?php if ($isAvailable): ?>
                                            <button class="add-btn" onclick="addToCart('<?= $product['id'] ?>')">
                                                <i class="lucide-plus"></i>
                                            </button>
                                        <?php else: ?>
                                            <div class="unavailable-overlay">
                                                <span class="unavailable-text">Indisponível</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
    </main>
    
    <!-- WhatsApp Float -->
    <?php if (!empty($restaurant['whatsapp'])): ?>
        <a href="https://wa.me/<?= htmlspecialchars($restaurant['whatsapp']) ?>?text=Olá! Gostaria de fazer um pedido." class="whatsapp-float" target="_blank" rel="noopener">
            <i class="lucide-message-circle"></i>
        </a>
    <?php endif; ?>
    
    <script>
        // Category Navigation
        const categoryBtns = document.querySelectorAll('.category-btn');
        const sections = document.querySelectorAll('.section');
        
        categoryBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;
                
                // Update active state
                categoryBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Scroll to section
                if (category !== 'all') {
                    const section = document.querySelector(`[data-section="${category}"]`);
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
        
        // View Toggle
        const viewBtns = document.querySelectorAll('.view-btn');
        const productContainers = document.querySelectorAll('[data-category]');
        
        viewBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;
                
                viewBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                productContainers.forEach(container => {
                    container.className = view === 'grid' ? 'products-grid' : 'products-list';
                });
            });
        });
        
        // Search
        const searchInput = document.getElementById('searchInput');
        const productCards = document.querySelectorAll('.product-card');
        
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            
            productCards.forEach(card => {
                const name = card.querySelector('.product-name').textContent.toLowerCase();
                const description = card.querySelector('.product-description').textContent.toLowerCase();
                
                if (name.includes(query) || description.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide sections based on visible products
            sections.forEach(section => {
                const visibleProducts = section.querySelectorAll('.product-card:not([style*="display: none"])');
                section.style.display = visibleProducts.length > 0 ? '' : 'none';
            });
        });
        
        // Intersection Observer for scroll spy
        const observerOptions = {
            threshold: 0.3,
            rootMargin: '-160px 0px -60% 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const categoryId = entry.target.dataset.section;
                    categoryBtns.forEach(btn => {
                        btn.classList.toggle('active', btn.dataset.category === categoryId);
                    });
                }
            });
        }, observerOptions);
        
        sections.forEach(section => observer.observe(section));
        
        // Add to Cart (placeholder)
        function addToCart(productId) {
            console.log('Add to cart:', productId);
            // Implement cart functionality or WhatsApp integration
        }
    </script>
</body>
</html>
