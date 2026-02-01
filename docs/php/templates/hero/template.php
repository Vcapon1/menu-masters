<?php
/**
 * CARDÁPIO FLORIPA - Template Hero
 * 
 * Design impactante com hero banner fullscreen
 * Ideal para hamburgerias e restaurantes temáticos
 * 
 * Variáveis disponíveis:
 * - $restaurant: dados do restaurante
 * - $categories: lista de categorias
 * - $products: lista de pratos
 * - $productsByCategory: pratos agrupados por categoria
 * - $customCss: variáveis CSS geradas
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($restaurant['name']) ?> - Cardápio Digital</title>
    
    <meta name="description" content="Cardápio digital de <?= htmlspecialchars($restaurant['name']) ?>">
    <meta name="theme-color" content="#0a0a0a">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($restaurant['name']) ?> - Cardápio">
    <meta property="og:description" content="Confira nosso cardápio digital">
    <?php if ($restaurant['banner']): ?>
    <meta property="og:image" content="<?= $restaurant['banner'] ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <?php if ($restaurant['logo']): ?>
    <link rel="icon" href="<?= $restaurant['logo'] ?>" type="image/png">
    <?php endif; ?>
    
    <style>
        /* Reset & Base */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        :root {
            --background: #0a0a0a;
            --primary: <?= $restaurant['primary_color'] ?? '#f59e0b' ?>;
            --secondary: <?= $restaurant['secondary_color'] ?? '#fbbf24' ?>;
            --accent: <?= $restaurant['accent_color'] ?? '#f97316' ?>;
            --button: <?= $restaurant['button_color'] ?? '#f59e0b' ?>;
            --button-text: <?= $restaurant['button_text_color'] ?? '#000000' ?>;
            --font: <?= $restaurant['font_color'] ?? '#ffffff' ?>;
            --badge-promo: #dc2626;
            --badge-chef: #3b82f6;
            --badge-vegan: #22c55e;
            --card-bg: rgba(20, 20, 20, 0.95);
            --overlay: rgba(0, 0, 0, 0.7);
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--font);
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Hero Section */
        .hero {
            position: relative;
            height: 40vh;
            min-height: 280px;
            max-height: 400px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.3) 0%,
                rgba(0, 0, 0, 0.1) 40%,
                rgba(0, 0, 0, 0.7) 80%,
                var(--background) 100%
            );
        }
        
        .hero-content {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .hero-logo {
            max-width: 200px;
            max-height: 150px;
            width: auto;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 20px rgba(0, 0, 0, 0.5));
        }
        
        .hero-name {
            margin-top: 16px;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
        }
        
        /* Category Navigation */
        .category-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--background);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 0;
        }
        
        .category-nav-inner {
            display: flex;
            gap: 10px;
            padding: 0 16px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .category-nav-inner::-webkit-scrollbar {
            display: none;
        }
        
        .category-chip {
            flex-shrink: 0;
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--primary);
            border-radius: 50px;
            color: var(--primary);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .category-chip:hover,
        .category-chip.active {
            background: var(--primary);
            color: var(--button-text);
        }
        
        /* Main Content */
        .main-content {
            padding: 24px 16px 100px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Category Section */
        .category-section {
            margin-bottom: 40px;
            scroll-margin-top: 80px;
        }
        
        .category-title {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .category-title h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .category-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, var(--primary), transparent);
        }
        
        /* Product Cards */
        .products-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .product-card {
            position: relative;
            display: flex;
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        }
        
        .product-card.unavailable {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .product-image-wrapper {
            position: relative;
            flex-shrink: 0;
            width: 130px;
            height: 130px;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-no-image {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.2);
            font-size: 2rem;
        }
        
        .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 44px;
            height: 44px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        
        .play-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
            margin-left: 2px;
        }
        
        .product-info {
            flex: 1;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--font);
            margin-bottom: 6px;
            line-height: 1.3;
        }
        
        .product-description {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .product-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        
        .badge-promo {
            background: var(--badge-promo);
            color: white;
        }
        
        .badge-chef {
            background: var(--badge-chef);
            color: white;
        }
        
        .badge-vegan {
            background: var(--badge-vegan);
            color: white;
        }
        
        .badge-new {
            background: var(--accent);
            color: var(--button-text);
        }
        
        .product-price-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .product-price-old {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: line-through;
        }
        
        /* Modal */
        .modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
        }
        
        .modal-content {
            position: relative;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            background: var(--card-bg);
            border-radius: 24px 24px 0 0;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 10;
            width: 36px;
            height: 36px;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            transition: background 0.2s;
        }
        
        .modal-close:hover {
            background: rgba(0, 0, 0, 0.7);
        }
        
        .modal-media {
            position: relative;
            width: 100%;
            aspect-ratio: 16/10;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-media img,
        .modal-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-info {
            padding: 24px;
            max-height: 50vh;
            overflow-y: auto;
        }
        
        .modal-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--font);
        }
        
        .modal-description {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .modal-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .modal-price-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-price {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .modal-price-old {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: line-through;
        }
        
        /* Utilities */
        .hidden {
            display: none !important;
        }
        
        /* Desktop Adjustments */
        @media (min-width: 768px) {
            .hero {
                height: 50vh;
                max-height: 500px;
            }
            
            .hero-logo {
                max-width: 280px;
                max-height: 200px;
            }
            
            .hero-name {
                font-size: 2rem;
            }
            
            .product-image-wrapper {
                width: 160px;
                height: 160px;
            }
            
            .modal-content {
                border-radius: 24px;
                margin: auto;
                max-height: 85vh;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero" style="background-image: url('<?= htmlspecialchars($restaurant['banner'] ?? '') ?>')">
        <div class="hero-content">
            <?php if ($restaurant['logo']): ?>
                <img 
                    src="<?= htmlspecialchars($restaurant['logo']) ?>" 
                    alt="<?= htmlspecialchars($restaurant['name']) ?>" 
                    class="hero-logo"
                >
            <?php else: ?>
                <h1 class="hero-name"><?= htmlspecialchars($restaurant['name']) ?></h1>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Category Navigation -->
    <?php if (!empty($categories)): ?>
    <nav class="category-nav">
        <div class="category-nav-inner">
            <?php foreach ($categories as $category): ?>
                <a 
                    href="#cat-<?= $category['id'] ?>" 
                    class="category-chip"
                    data-category="<?= $category['id'] ?>"
                >
                    <?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php foreach ($categories as $category): ?>
            <?php 
            $categoryProducts = $productsByCategory[$category['id']] ?? [];
            if (empty($categoryProducts)) continue;
            ?>
            
            <section class="category-section" id="cat-<?= $category['id'] ?>">
                <div class="category-title">
                    <h2><?= htmlspecialchars($category['name']) ?></h2>
                </div>
                
                <div class="products-grid">
                    <?php foreach ($categoryProducts as $product): ?>
                        <?php 
                        // Pular produtos ocultos quando indisponíveis
                        if (!$product['is_available'] && $product['hide_when_unavailable']) continue;
                        
                        $badges = json_decode($product['badges'] ?? '[]', true) ?: [];
                        $hasPromo = $product['promo_price'] && $product['promo_price'] < $product['price'];
                        $hasVideo = !empty($product['video']);
                        ?>
                        
                        <article 
                            class="product-card <?= !$product['is_available'] ? 'unavailable' : '' ?>"
                            onclick="openProductModal(<?= htmlspecialchars(json_encode([
                                'id' => $product['id'],
                                'name' => $product['name'],
                                'description' => $product['description'],
                                'price' => number_format($hasPromo ? $product['promo_price'] : $product['price'], 2, ',', '.'),
                                'oldPrice' => $hasPromo ? number_format($product['price'], 2, ',', '.') : null,
                                'image' => $product['image'],
                                'video' => $product['video'],
                                'badges' => $badges,
                                'hasPromo' => $hasPromo
                            ])) ?>)"
                        >
                            <div class="product-image-wrapper">
                                <?php if ($product['image']): ?>
                                    <img 
                                        src="<?= htmlspecialchars($product['image']) ?>" 
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        class="product-image"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="product-no-image">🍽️</div>
                                <?php endif; ?>
                                
                                <?php if ($hasVideo): ?>
                                    <div class="play-icon">
                                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <div>
                                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                    
                                    <?php if ($product['description']): ?>
                                        <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($badges) || $hasPromo): ?>
                                        <div class="product-badges">
                                            <?php if ($hasPromo): ?>
                                                <span class="badge badge-promo">Promoção</span>
                                            <?php endif; ?>
                                            <?php if (in_array('chef', $badges)): ?>
                                                <span class="badge badge-chef">⭐ Chef</span>
                                            <?php endif; ?>
                                            <?php if (in_array('vegan', $badges)): ?>
                                                <span class="badge badge-vegan">🌱 Vegano</span>
                                            <?php endif; ?>
                                            <?php if (in_array('new', $badges)): ?>
                                                <span class="badge badge-new">Novo</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-price-row">
                                    <?php if ($hasPromo): ?>
                                        <span class="product-price-old">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                    <?php endif; ?>
                                    <span class="product-price">
                                        R$ <?= number_format($hasPromo ? $product['promo_price'] : $product['price'], 2, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </main>
    
    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-overlay" onclick="closeProductModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeProductModal()">&times;</button>
            
            <div class="modal-media">
                <img id="modalImage" src="" alt="">
                <video id="modalVideo" controls playsinline style="display: none;"></video>
            </div>
            
            <div class="modal-info">
                <h2 id="modalName" class="modal-name"></h2>
                <p id="modalDescription" class="modal-description"></p>
                <div id="modalBadges" class="modal-badges"></div>
                <div class="modal-price-row">
                    <span id="modalOldPrice" class="modal-price-old"></span>
                    <span id="modalPrice" class="modal-price"></span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Product Modal Functions
        function openProductModal(product) {
            const modal = document.getElementById('productModal');
            const img = document.getElementById('modalImage');
            const video = document.getElementById('modalVideo');
            
            // Handle media
            if (product.video) {
                img.style.display = 'none';
                video.style.display = 'block';
                video.src = product.video;
            } else {
                video.style.display = 'none';
                video.pause();
                video.src = '';
                img.style.display = 'block';
                img.src = product.image || '';
            }
            
            // Fill content
            document.getElementById('modalName').textContent = product.name;
            document.getElementById('modalDescription').textContent = product.description || '';
            document.getElementById('modalPrice').textContent = 'R$ ' + product.price;
            
            // Old price
            const oldPriceEl = document.getElementById('modalOldPrice');
            if (product.oldPrice) {
                oldPriceEl.textContent = 'R$ ' + product.oldPrice;
                oldPriceEl.style.display = 'inline';
            } else {
                oldPriceEl.style.display = 'none';
            }
            
            // Badges
            const badgesEl = document.getElementById('modalBadges');
            badgesEl.innerHTML = '';
            
            if (product.hasPromo) {
                badgesEl.innerHTML += '<span class="badge badge-promo">Promoção</span>';
            }
            if (product.badges && product.badges.includes('chef')) {
                badgesEl.innerHTML += '<span class="badge badge-chef">⭐ Sugestão do Chef</span>';
            }
            if (product.badges && product.badges.includes('vegan')) {
                badgesEl.innerHTML += '<span class="badge badge-vegan">🌱 Vegano</span>';
            }
            if (product.badges && product.badges.includes('new')) {
                badgesEl.innerHTML += '<span class="badge badge-new">Novo</span>';
            }
            
            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Log product view
            logProductView(product.id);
        }
        
        function closeProductModal() {
            const modal = document.getElementById('productModal');
            const video = document.getElementById('modalVideo');
            
            modal.classList.remove('active');
            video.pause();
            document.body.style.overflow = '';
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProductModal();
            }
        });
        
        // Category Navigation Active State
        const categoryChips = document.querySelectorAll('.category-chip');
        const sections = document.querySelectorAll('.category-section');
        
        function updateActiveCategory() {
            const scrollY = window.scrollY + 120;
            
            sections.forEach(section => {
                const id = section.getAttribute('id');
                const top = section.offsetTop;
                const height = section.offsetHeight;
                
                if (scrollY >= top && scrollY < top + height) {
                    categoryChips.forEach(chip => {
                        chip.classList.remove('active');
                        if (chip.getAttribute('href') === '#' + id) {
                            chip.classList.add('active');
                        }
                    });
                }
            });
        }
        
        window.addEventListener('scroll', updateActiveCategory);
        updateActiveCategory();
        
        // Smooth scroll for category links
        categoryChips.forEach(chip => {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const target = document.getElementById(targetId);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Log product view for analytics
        function logProductView(productId) {
            fetch('<?= dirname($_SERVER['SCRIPT_NAME']) ?>/api/log.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    restaurant_id: <?= $restaurant['id'] ?>,
                    product_id: productId,
                    type: 'product_view'
                })
            }).catch(() => {});
        }
    </script>
</body>
</html>
