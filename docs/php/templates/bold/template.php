<?php
/**
 * PREMIUM MENU - Template Bold
 * 
 * Template com alto contraste, cores vibrantes e visual impactante.
 * Suporta toggle entre visualização em lista e grid.
 * 
 * Variáveis disponíveis:
 * - $restaurant: Dados do restaurante
 * - $categories: Lista de categorias
 * - $products: Lista de todos os pratos
 * - $productsByCategory: Pratos agrupados por categoria
 * - $customCss: CSS com variáveis de cores
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> - Cardápio Digital</title>
    <meta name="description" content="Cardápio digital de <?= htmlspecialchars($restaurant['name']) ?>. Conheça nossos pratos e faça seu pedido!">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($restaurant['name']) ?> - Cardápio">
    <meta property="og:description" content="Confira nosso cardápio digital com todos os pratos disponíveis.">
    <meta property="og:image" content="<?= htmlspecialchars($restaurant['banner'] ?: $restaurant['logo']) ?>">
    <meta property="og:type" content="restaurant.menu">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= htmlspecialchars($restaurant['logo']) ?>" type="image/png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        <?= $customCss ?>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            background-image: var(--background-image);
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            color: var(--font-color);
            min-height: 100vh;
        }
        
        .overlay {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.6) 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(var(--primary-rgb), 0.3);
        }
        
        .logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--secondary-color);
            margin-bottom: 15px;
        }
        
        .restaurant-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .restaurant-address {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Category Filter */
        .category-filter {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            margin-bottom: 20px;
            -webkit-overflow-scrolling: touch;
        }
        
        .category-filter::-webkit-scrollbar {
            display: none;
        }
        
        .category-btn {
            padding: 10px 20px;
            background: rgba(var(--primary-rgb), 0.2);
            border: 2px solid var(--primary-color);
            color: var(--font-color);
            border-radius: 30px;
            cursor: pointer;
            white-space: nowrap;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: var(--primary-color);
            color: var(--button-text-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(var(--primary-rgb), 0.4);
        }
        
        /* View Toggle */
        .view-toggle {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .view-btn {
            padding: 10px 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid var(--secondary-color);
            color: var(--font-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-btn.active {
            background: var(--secondary-color);
            color: var(--primary-color);
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        /* Product Card */
        .product-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border-color: var(--accent-color);
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .product-video {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .product-content {
            padding: 15px;
        }
        
        .product-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .product-description {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--secondary-color);
        }
        
        .product-price-original {
            text-decoration: line-through;
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .product-price-promo {
            color: #ff4444;
        }
        
        /* Badges */
        .badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-promo { background: #ff4444; }
        .badge-vegan { background: #22c55e; }
        .badge-spicy { background: #f97316; }
        .badge-new { background: #3b82f6; }
        .badge-chef { background: #a855f7; }
        
        /* Unavailable */
        .product-card.unavailable {
            opacity: 0.5;
        }
        
        .unavailable-badge {
            background: rgba(0,0,0,0.8);
            color: #ff4444;
            padding: 5px 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* List View */
        .products-list .product-card {
            display: flex;
            flex-direction: row;
        }
        
        .products-list .product-image,
        .products-list .product-video {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }
        
        .products-list .product-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        /* Category Section */
        .category-section {
            margin-bottom: 40px;
        }
        
        .category-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--accent-color);
            display: inline-block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .restaurant-name {
                font-size: 1.5rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .products-list .product-card {
                flex-direction: column;
            }
            
            .products-list .product-image,
            .products-list .product-video {
                width: 100%;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <!-- Header -->
            <header class="header">
                <?php if (!empty($restaurant['logo'])): ?>
                    <img src="<?= htmlspecialchars($restaurant['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>" class="logo">
                <?php endif; ?>
                <h1 class="restaurant-name"><?= htmlspecialchars($restaurant['name']) ?></h1>
                <?php if (!empty($restaurant['address'])): ?>
                    <p class="restaurant-address"><?= htmlspecialchars($restaurant['address']) ?></p>
                <?php endif; ?>
            </header>
            
            <!-- Category Filter -->
            <div class="category-filter">
                <button class="category-btn active" data-category="all">Todos</button>
                <?php foreach ($categories as $category): ?>
                    <button class="category-btn" data-category="<?= $category['id'] ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                    </svg>
                </button>
                <button class="view-btn" data-view="list">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="3" y="4" width="18" height="4"/><rect x="3" y="10" width="18" height="4"/>
                        <rect x="3" y="16" width="18" height="4"/>
                    </svg>
                </button>
            </div>
            
            <!-- Products -->
            <main id="products-container" class="products-grid">
                <?php foreach ($products as $product): 
                    $badges = getProductBadges($product['badges']);
                    $isUnavailable = !$product['is_available'];
                    $hasPromo = !empty($product['promo_price']);
                ?>
                    <article class="product-card <?= $isUnavailable ? 'unavailable' : '' ?>" 
                             data-category="<?= $product['category_id'] ?>"
                             data-product-id="<?= $product['id'] ?>">
                        
                        <?php if (!empty($product['video'])): ?>
                            <video class="product-video" autoplay muted loop playsinline>
                                <source src="<?= htmlspecialchars($product['video']) ?>" type="video/mp4">
                            </video>
                        <?php elseif (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="product-image"
                                 loading="lazy">
                        <?php endif; ?>
                        
                        <?php if ($isUnavailable): ?>
                            <div class="unavailable-badge">Indisponível</div>
                        <?php endif; ?>
                        
                        <div class="product-content">
                            <?php if (!empty($badges)): ?>
                                <div class="badges">
                                    <?php foreach ($badges as $badge): ?>
                                        <span class="badge <?= $badge['color'] ?>"><?= $badge['label'] ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            
                            <?php if (!empty($product['description'])): ?>
                                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="product-price">
                                <?php if ($hasPromo): ?>
                                    <span class="product-price-original"><?= formatPrice($product['price']) ?></span>
                                    <span class="product-price-promo"><?= formatPrice($product['promo_price']) ?></span>
                                <?php else: ?>
                                    <?= formatPrice($product['price']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </main>
        </div>
    </div>
    
    <script>
        // Category Filter
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const category = this.dataset.category;
                
                // Update active button
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter products
                document.querySelectorAll('.product-card').forEach(card => {
                    if (category === 'all' || card.dataset.category === category) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        // View Toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                const container = document.getElementById('products-container');
                
                // Update active button
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update container class
                container.className = view === 'grid' ? 'products-grid' : 'products-list';
            });
        });
        
        // Track product views
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function() {
                const productId = this.dataset.productId;
                // Send analytics (you can implement this with fetch)
                console.log('Product viewed:', productId);
            });
        });
    </script>
</body>
</html>
