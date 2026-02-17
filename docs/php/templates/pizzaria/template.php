<?php
/**
 * CARDÁPIO FLORIPA - Template Pizzaria
 * 
 * Design temático para pizzarias com fundo escuro quente,
 * cards horizontais e Pizza Builder integrado (multi-sabor).
 * 
 * Variáveis disponíveis:
 * - $restaurant, $categories, $products, $productsByCategory
 * - $customCss, $cartMode, $isOpen, $tableNumber
 * - $productVariations, $multiFlavorCategories
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($restaurant['name']) ?> - Cardápio Digital</title>
    
    <meta name="description" content="Cardápio digital de <?= htmlspecialchars($restaurant['name']) ?>">
    <meta name="theme-color" content="#1a1410">
    
    <meta property="og:title" content="<?= htmlspecialchars($restaurant['name']) ?> - Cardápio">
    <meta property="og:description" content="Confira nosso cardápio digital">
    <?php if ($restaurant['banner']): ?>
    <meta property="og:image" content="<?= $restaurant['banner'] ?>">
    <?php endif; ?>
    
    <?php if ($restaurant['logo']): ?>
    <link rel="icon" href="<?= $restaurant['logo'] ?>" type="image/png">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --background: #1a1410;
            --surface: #241e18;
            --surface-alt: #2e2620;
            --primary: <?= $restaurant['primary_color'] ?? '#c0392b' ?>;
            --secondary: <?= $restaurant['secondary_color'] ?? '#d4a574' ?>;
            --accent: <?= $restaurant['accent_color'] ?? '#e74c3c' ?>;
            --button: <?= $restaurant['button_color'] ?? '#c0392b' ?>;
            --button-text: <?= $restaurant['button_text_color'] ?? '#ffffff' ?>;
            --font: <?= $restaurant['font_color'] ?? '#faf5f0' ?>;
            --muted: #a89888;
            --border: rgba(255, 255, 255, 0.08);
            --card-bg: #241e18;
            --badge-promo: #c0392b;
        }
        
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--font);
            min-height: 100vh;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ===== HERO ===== */
        .hero {
            position: relative;
            height: 25vh;
            min-height: 180px;
            max-height: 300px;
            overflow: hidden;
        }
        
        .hero-bg {
            position: absolute;
            inset: -10%;
            width: 120%;
            height: 120%;
            background-size: cover;
            background-position: center;
            filter: brightness(0.4);
            will-change: transform;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(26,20,16,0.6) 60%, var(--background) 100%);
        }
        
        .hero-content {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding: 0 20px 20px;
        }
        
        .hero-logo {
            max-width: 55%;
            max-height: 100px;
            object-fit: contain;
            filter: drop-shadow(0 2px 12px rgba(0,0,0,0.6));
        }
        
        .hero-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            color: var(--font);
        }
        
        .hero-subtitle {
            font-size: 0.7rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--secondary);
            margin-top: 4px;
        }
        
        /* ===== Social Bar ===== */
        .social-bar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 5px 16px;
            position: fixed;
            bottom: 0;
            width: 100%;
            z-index: 1;
        }
        
        .social-bar-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }
        
        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 33px;
            height: 33px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            color: var(--muted);
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .social-link:hover {
            background: var(--primary);
            color: var(--button-text);
        }
        
        .social-link svg { width: 17px; height: 17px; fill: currentColor; }
        
        /* ===== Category Nav ===== */
        .category-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--background);
            border-bottom: 1px solid var(--border);
            padding: 12px 0;
        }
        
        .category-nav-inner {
            display: flex;
            gap: 8px;
            padding: 0 16px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        
        .category-nav-inner::-webkit-scrollbar { display: none; }
        
        .category-chip {
            flex-shrink: 0;
            padding: 7px 16px;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 50px;
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .category-chip:hover,
        .category-chip.active {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--button-text);
        }
        
        /* ===== Main ===== */
        .main-content {
            padding: 24px 16px 100px;
            max-width: 640px;
            margin: 0 auto;
        }
        
        .category-section {
            margin-bottom: 40px;
            scroll-margin-top: 80px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .category-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary);
            letter-spacing: 0.04em;
        }
        
        .category-header::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--secondary), transparent);
            opacity: 0.3;
        }
        
        /* Botão Montar Pizza */
        .mount-pizza-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: var(--button-text);
            border: none;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(192,57,43,0.3);
        }
        
        .mount-pizza-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(192,57,43,0.4);
        }
        
        .mount-pizza-btn svg { width: 22px; height: 22px; }
        
        /* ===== Product Cards - Horizontal ===== */
        .products-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .product-card {
            display: flex;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .product-card:hover {
            border-color: rgba(212,165,116,0.25);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .product-card.unavailable {
            opacity: 0.4;
            pointer-events: none;
        }
        
        .product-card-image {
            width: 110px;
            min-height: 110px;
            flex-shrink: 0;
            overflow: hidden;
        }
        
        .product-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }
        
        .product-card:hover .product-card-image img {
            transform: scale(1.05);
        }
        
        .product-card-image .no-img {
            width: 100%;
            height: 100%;
            background: var(--surface-alt);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.1);
            font-size: 1.8rem;
        }
        
        .product-card-body {
            flex: 1;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }
        
        .product-card-name {
            font-family: 'Playfair Display', serif;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--font);
            margin-bottom: 2px;
        }
        
        .product-card-desc {
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .product-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        
        .product-card-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .product-card-price-old {
            font-size: 0.75rem;
            color: var(--muted);
            text-decoration: line-through;
            margin-right: 6px;
        }
        
        .product-card-sizes {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .size-chip {
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .size-chip .sl { color: var(--muted); margin-right: 3px; }
        
        /* Badges */
        .badge-row {
            display: flex;
            gap: 4px;
            margin-bottom: 4px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 7px;
            border-radius: 6px;
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-promo { background: var(--badge-promo); color: white; }
        .badge-chef { background: rgba(212,165,116,0.15); color: var(--secondary); border: 1px solid rgba(212,165,116,0.3); }
        .badge-vegan { background: rgba(34,197,94,0.15); color: #22c55e; }
        .badge-new { background: var(--accent); color: white; }
        
        /* Product order button (card) */
        .product-order-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            background: var(--button);
            color: var(--button-text);
            border: none;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .product-order-btn:hover { filter: brightness(1.1); }
        .product-order-btn svg { width: 14px; height: 14px; }
        
        /* ===== Modal ===== */
        .modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal.active { opacity: 1; visibility: visible; }
        
        .modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
        }
        
        .modal-content {
            position: relative;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            background: var(--background);
            border-radius: 24px 24px 0 0;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .modal.active .modal-content { transform: translateY(0); }
        
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 10;
            width: 36px;
            height: 36px;
            background: rgba(26,20,16,0.7);
            border: 1px solid var(--border);
            border-radius: 50%;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-media {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            background: var(--surface);
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .modal-media img, .modal-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
        }
        
        .modal-header {
            padding: 20px 20px 0;
            flex-shrink: 0;
        }
        
        .modal-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--font);
        }
        
        .modal-price-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 16px;
        }
        
        .modal-price { font-size: 1.4rem; font-weight: 700; color: var(--secondary); }
        .modal-price-old { font-size: 0.95rem; color: var(--muted); text-decoration: line-through; }
        
        .modal-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 0 20px 20px;
        }
        
        .modal-description {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.7;
            white-space: pre-wrap;
        }
        
        .modal-order-area {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }
        
        .modal-order-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            background: var(--button);
            color: var(--button-text);
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .modal-order-btn:hover { filter: brightness(1.1); }
        .modal-order-btn svg { width: 20px; height: 20px; }
        
        .hidden { display: none !important; }
        
        @media (min-width: 768px) {
            .hero { height: 35vh; max-height: 380px; }
            .hero-logo { max-width: 240px; max-height: 140px; }
            .product-card-image { width: 140px; min-height: 130px; }
            .modal-content { border-radius: 24px; margin: auto; max-height: 85vh; }
        }
    </style>
</head>
<body>
    <!-- Hero -->
    <section class="hero">
        <div class="hero-bg" id="heroBg" style="background-image: url('<?= htmlspecialchars($restaurant['banner'] ?? '') ?>')"></div>
        <div class="hero-content">
            <?php if ($restaurant['logo']): ?>
                <img src="<?= htmlspecialchars($restaurant['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>" class="hero-logo">
            <?php else: ?>
                <h1 class="hero-name"><?= htmlspecialchars($restaurant['name']) ?></h1>
            <?php endif; ?>
            <span class="hero-subtitle">Cardápio Digital</span>
        </div>
    </section>
    
    <!-- Social Bar -->
    <?php 
    $hasSocial = !empty($restaurant['instagram']) || !empty($restaurant['facebook']) || !empty($restaurant['whatsapp']);
    ?>
    <?php if ($hasSocial): ?>
    <div class="social-bar">
        <div class="social-bar-inner">
            <?php if (!empty($restaurant['instagram'])): ?>
                <a href="https://instagram.com/<?= htmlspecialchars(ltrim($restaurant['instagram'], '@')) ?>" target="_blank" class="social-link" title="Instagram">
                    <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
            <?php endif; ?>
            <?php if (!empty($restaurant['whatsapp'])): ?>
                <?php $whatsappClean = preg_replace('/\D/', '', $restaurant['whatsapp']); ?>
                <a href="https://wa.me/55<?= $whatsappClean ?>" target="_blank" class="social-link" title="WhatsApp">
                    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
            <?php endif; ?>
            <?php if (!empty($restaurant['google_maps_url'])): ?>
                <a href="<?= htmlspecialchars($restaurant['google_maps_url']) ?>" target="_blank" class="social-link" title="Google Maps">
                    <svg viewBox="0 0 24 24"><path d="M12 0C7.802 0 4 3.403 4 7.602 4 11.8 7.469 16.812 12 24c4.531-7.188 8-12.2 8-16.398C20 3.403 16.199 0 12 0zm0 11a3 3 0 110-6 3 3 0 010 6z"/></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Closed Banner -->
    <?php if (!$isOpen): ?>
    <div class="closed-banner" style="background:var(--surface);border-bottom:1px solid var(--border);padding:10px 16px;text-align:center;font-size:0.85rem;color:var(--muted);">
        <strong style="color:var(--badge-promo)">Fechado</strong> — Estamos fora do horário de atendimento
    </div>
    <?php endif; ?>
    
    <!-- Category Nav -->
    <?php if (!empty($categories)): ?>
    <nav class="category-nav">
        <div class="category-nav-inner">
            <?php foreach ($categories as $category): ?>
                <a href="#cat-<?= $category['id'] ?>" class="category-chip" data-category="<?= $category['id'] ?>">
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
            $isMultiFlavor = !empty($category['allow_multi_flavor']);
            ?>
            
            <section class="category-section" id="cat-<?= $category['id'] ?>">
                <div class="category-header">
                    <h2><?= htmlspecialchars($category['name']) ?></h2>
                </div>
                
                <!-- Botão Montar Pizza -->
                <?php if ($isMultiFlavor && $cartMode && $isOpen): ?>
                <button class="mount-pizza-btn" onclick="Cart.openPizzaBuilder(<?= $category['id'] ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="2" x2="12" y2="22"/>
                        <path d="M4.93 4.93l14.14 14.14"/>
                    </svg>
                    🍕 Montar Pizza
                </button>
                <?php endif; ?>
                
                <div class="products-grid">
                    <?php foreach ($categoryProducts as $product): ?>
                        <?php 
                        if (!$product['is_available'] && $product['hide_when_unavailable']) continue;
                        
                        $badges = json_decode($product['badges'] ?? '[]', true) ?: [];
                        $hasPromo = $product['promo_price'] && $product['promo_price'] < $product['price'];
                        $sizesPrices = json_decode($product['sizes_prices'] ?? 'null', true);
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
                            <div class="product-card-image">
                                <?php if ($product['image']): ?>
                                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="no-img">🍕</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-card-body">
                                <?php if (!empty($badges)): ?>
                                <div class="badge-row">
                                    <?php if (in_array('chef', $badges)): ?><span class="badge badge-chef">⭐ Chef</span><?php endif; ?>
                                    <?php if (in_array('vegan', $badges)): ?><span class="badge badge-vegan">🌱 Vegano</span><?php endif; ?>
                                    <?php if (in_array('new', $badges)): ?><span class="badge badge-new">Novo</span><?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <h3 class="product-card-name"><?= htmlspecialchars($product['name']) ?></h3>
                                
                                <?php if ($product['description']): ?>
                                    <p class="product-card-desc"><?= htmlspecialchars($product['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="product-card-footer">
                                    <?php if ($sizesPrices && is_array($sizesPrices) && count($sizesPrices) > 0): ?>
                                        <div class="product-card-sizes">
                                            <?php foreach ($sizesPrices as $size): ?>
                                                <span class="size-chip"><span class="sl"><?= htmlspecialchars($size['label']) ?></span> R$ <?= number_format($size['price'], 2, ',', '.') ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($hasPromo): ?>
                                        <span>
                                            <span class="product-card-price-old">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                            <span class="product-card-price">R$ <?= number_format($product['promo_price'], 2, ',', '.') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="product-card-price">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($cartMode && $isOpen): ?>
                                        <button class="product-order-btn" onclick="event.stopPropagation(); Cart.openVariationsModal(<?= htmlspecialchars(json_encode([
                                            'id' => $product['id'],
                                            'name' => $product['name'],
                                            'price' => (float)$product['price'],
                                            'promoPrice' => $hasPromo ? (float)$product['promo_price'] : null,
                                            'image' => $product['image'],
                                            'sizesPrices' => $sizesPrices
                                        ])) ?>)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/></svg>
                                            Pedir
                                        </button>
                                    <?php endif; ?>
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
                <video id="modalVideo" autoplay loop muted playsinline style="display:none;"></video>
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
                </div>
                <div class="modal-order-area hidden" id="modalOrderArea">
                    <button id="modalOrderBtn" class="modal-order-btn" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        </svg>
                        Pedir
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Hero Parallax
        var heroBg = document.getElementById('heroBg');
        var ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    var scrollY = window.scrollY;
                    var heroH = document.querySelector('.hero').offsetHeight;
                    if (scrollY < heroH) {
                        heroBg.style.transform = 'translateY(' + (scrollY * 0.3) + 'px)';
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
        
        // Modal
        var __scrollTop = 0;
        var __currentModalProduct = null;
        
        function lockScroll() {
            __scrollTop = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.top = '-' + __scrollTop + 'px';
            document.body.style.left = '0';
            document.body.style.right = '0';
            document.body.style.width = '100%';
        }
        
        function unlockScroll() {
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.left = '';
            document.body.style.right = '';
            document.body.style.width = '';
            window.scrollTo(0, __scrollTop);
        }
        
        function openProductModal(product) {
            __currentModalProduct = product;
            var modal = document.getElementById('productModal');
            var img = document.getElementById('modalImage');
            var video = document.getElementById('modalVideo');
            
            if (product.video) {
                img.style.display = 'none';
                video.style.display = 'block';
                video.src = product.video;
                video.muted = true;
                video.loop = true;
                video.play().catch(function(){});
            } else {
                video.style.display = 'none';
                video.pause();
                video.src = '';
                img.style.display = 'block';
                img.src = product.image || '';
            }
            
            document.getElementById('modalName').textContent = product.name;
            document.getElementById('modalDescription').textContent = product.description || '';
            document.getElementById('modalPrice').textContent = 'R$ ' + product.price;
            
            var oldPriceEl = document.getElementById('modalOldPrice');
            if (product.oldPrice) {
                oldPriceEl.textContent = 'R$ ' + product.oldPrice;
                oldPriceEl.style.display = 'inline';
            } else {
                oldPriceEl.style.display = 'none';
            }
            
            // Order button
            var orderArea = document.getElementById('modalOrderArea');
            var orderBtn = document.getElementById('modalOrderBtn');
            if (orderBtn && typeof Cart !== 'undefined' && IS_OPEN) {
                orderArea.classList.remove('hidden');
                orderBtn.onclick = function() {
                    var priceStr = (product.price || '').replace('.', '').replace(',', '.');
                    var oldPriceStr = product.oldPrice ? product.oldPrice.replace('.', '').replace(',', '.') : null;
                    var numPrice = parseFloat(priceStr) || 0;
                    var numOldPrice = oldPriceStr ? parseFloat(oldPriceStr) : null;
                    
                    var cartProduct = {
                        id: product.id,
                        name: product.name,
                        price: numOldPrice || numPrice,
                        promoPrice: numOldPrice ? numPrice : null,
                        image: product.image || '',
                        sizesPrices: null
                    };
                    closeProductModal();
                    Cart.openVariationsModal(cartProduct);
                };
            } else if (orderArea) {
                orderArea.classList.add('hidden');
            }
            
            modal.classList.add('active');
            lockScroll();
        }
        
        function closeProductModal() {
            var modal = document.getElementById('productModal');
            var video = document.getElementById('modalVideo');
            modal.classList.remove('active');
            video.pause();
            video.src = '';
            unlockScroll();
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeProductModal();
        });
        
        // Category active state
        var categoryChips = document.querySelectorAll('.category-chip');
        var sections = document.querySelectorAll('.category-section');
        
        function updateActiveCategory() {
            var scrollY = window.scrollY + 120;
            sections.forEach(function(section) {
                var id = section.getAttribute('id');
                var top = section.offsetTop;
                var height = section.offsetHeight;
                if (scrollY >= top && scrollY < top + height) {
                    categoryChips.forEach(function(chip) {
                        chip.classList.remove('active');
                        if (chip.getAttribute('href') === '#' + id) chip.classList.add('active');
                    });
                }
            });
        }
        
        window.addEventListener('scroll', updateActiveCategory);
        updateActiveCategory();
        
        categoryChips.forEach(function(chip) {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                var targetId = this.getAttribute('href').substring(1);
                var target = document.getElementById(targetId);
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>

    <?php if ($cartMode): ?>
    <!-- Cart System -->
    <link rel="stylesheet" href="/includes/cart-styles.css">
    <script>
        var CART_MODE = <?= json_encode($cartMode) ?>;
        var RESTAURANT = <?= json_encode([
            'id' => $restaurant['id'],
            'name' => $restaurant['name'],
            'slug' => $restaurant['slug'],
            'whatsapp' => $restaurant['whatsapp'] ?? '',
            'logo' => $restaurant['logo'] ?? ''
        ]) ?>;
        var TABLE_NUMBER = <?= json_encode($tableNumber) ?>;
        var IS_OPEN = <?= json_encode($isOpen) ?>;
        var PRODUCT_VARIATIONS = <?= json_encode($productVariations ?? []) ?>;
        var MULTI_FLAVOR_CATEGORIES = <?= json_encode($multiFlavorCategories ?? []) ?>;
    </script>
    <script src="/includes/cart.js"></script>
    <?php endif; ?>
</body>
</html>