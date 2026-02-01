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
        
        /* Hero Section - Parallax Mobile */
        .hero {
            position: relative;
            height: 45vh;
            min-height: 300px;
            max-height: 450px;
            overflow: hidden;
        }
        
        .hero-bg {
            position: absolute;
            inset: -20%;
            width: 140%;
            height: 140%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            will-change: transform;
            transition: transform 0.1s ease-out;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.4) 0%,
                rgba(0, 0, 0, 0.1) 40%,
                rgba(0, 0, 0, 0.75) 85%,
                var(--background) 100%
            );
        }
        
        .hero-content {
            position: absolute;
            inset: 0;
            z-index: 2;
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
            border-radius: 16px;
            overflow: hidden;
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.08);
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
            width: 100%;
            aspect-ratio: 16/9;
            overflow: hidden;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
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
        
        .product-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px 16px 16px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.7) 50%, transparent 100%);
        }
        
        .play-icon {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        
        .play-icon svg {
            width: 18px;
            height: 18px;
            fill: white;
            margin-left: 2px;
        }
        
        .product-badges-float {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .product-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--font);
            margin-bottom: 4px;
            line-height: 1.3;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        }
        
        .product-description {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.65);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--secondary);
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        }
        
        .product-price-old {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.5);
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
            padding-bottom: 20px;
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
            max-height: 88vh;
            background: var(--card-bg);
            border-radius: 24px 24px 0 0;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 10;
            width: 40px;
            height: 40px;
            background: rgba(30, 30, 30, 0.8);
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
            background: rgba(50, 50, 50, 0.9);
        }
        
        .modal-media {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            background: rgba(0, 0, 0, 0.5);
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .modal-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
        }
        
        /* Modal Info - Fixed header with scrollable description */
        .modal-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            background: var(--background);
        }
        
        .modal-header {
            padding: 20px 20px 0 20px;
            flex-shrink: 0;
        }
        
        .modal-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--font);
        }
        
        .modal-price-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .modal-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .modal-price-old {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: line-through;
        }
        
        .modal-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 0 20px 20px 20px;
            -webkit-overflow-scrolling: touch;
        }
        
        .modal-description {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
            white-space: pre-wrap;
            margin-bottom: 16px;
        }
        
        .modal-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        /* Utilities */
        .hidden {
            display: none !important;
        }
        
        /* Desktop Adjustments */
        @media (min-width: 768px) {
            .hero {
                height: 55vh;
                max-height: 550px;
            }
            
            .hero-logo {
                max-width: 280px;
                max-height: 200px;
            }
            
            .hero-name {
                font-size: 2rem;
            }
            
            .product-image-wrapper {
                width: 180px;
                height: 180px;
            }
            
            .modal-content {
                border-radius: 24px;
                margin: auto;
                max-height: 85vh;
            }
            
            .modal-media {
                aspect-ratio: 16/10;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section with Parallax -->
    <section class="hero">
        <div class="hero-bg" id="heroBg" style="background-image: url('<?= htmlspecialchars($restaurant['banner'] ?? '') ?>')"></div>
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
                                
                                <?php if (!empty($badges) || $hasPromo): ?>
                                    <div class="product-badges-float">
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
                                
                                <?php if ($hasVideo): ?>
                                    <div class="play-icon">
                                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-overlay">
                                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                    
                                    <?php if ($product['description']): ?>
                                        <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="product-price-row">
                                        <?php if ($hasPromo): ?>
                                            <span class="product-price-old">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <span class="product-price">
                                            R$ <?= number_format($hasPromo ? $product['promo_price'] : $product['price'], 2, ',', '.') ?>
                                        </span>
                                    </div>
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
                <video id="modalVideo" autoplay loop muted playsinline style="display: none;"></video>
            </div>
            
            <div class="modal-info">
                <div class="modal-header">
                    <h2 id="modalName" class="modal-name"></h2>
                    <div class="modal-price-row">
                        <span id="modalOldPrice" class="modal-price-old"></span>
                        <span id="modalPrice" class="modal-price"></span>
                    </div>
                </div>
                <div class="modal-scrollable">
                    <p id="modalDescription" class="modal-description"></p>
                    <div id="modalBadges" class="modal-badges"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Hero Parallax Effect for Mobile
        const heroBg = document.getElementById('heroBg');
        let ticking = false;
        
        function updateParallax() {
            const scrollY = window.scrollY;
            const heroHeight = document.querySelector('.hero').offsetHeight;
            
            if (scrollY < heroHeight) {
                const parallaxSpeed = 0.4;
                const yPos = scrollY * parallaxSpeed;
                heroBg.style.transform = `translateY(${yPos}px) scale(1.1)`;
            }
            ticking = false;
        }
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }, { passive: true });
        
        // Product Modal Functions
        function openProductModal(product) {
            const modal = document.getElementById('productModal');
            const img = document.getElementById('modalImage');
            const video = document.getElementById('modalVideo');
            
            // Handle media - video autoplay loop muted
            if (product.video) {
                img.style.display = 'none';
                video.style.display = 'block';
                video.src = product.video;
                video.muted = true;
                video.loop = true;
                video.play().catch(() => {});
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
            video.src = '';
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
