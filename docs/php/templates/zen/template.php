<?php
/**
 * CARDÁPIO FLORIPA - Template Zen
 * 
 * Design minimalista oriental com fundo escuro e acentos dourados.
 * Ideal para restaurantes japoneses, chineses, thai e fusion.
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
    <meta name="theme-color" content="#0c0a09">
    
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

    <!-- Google Fonts - Noto Serif JP for oriental elegance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        :root {
            --background: #0c0a09;
            --surface: #1c1917;
            --surface-alt: #292524;
            --primary: <?= $restaurant['primary_color'] ?? '#d4a574' ?>;
            --secondary: <?= $restaurant['secondary_color'] ?? '#a3845a' ?>;
            --accent: <?= $restaurant['accent_color'] ?? '#f59e0b' ?>;
            --button: <?= $restaurant['button_color'] ?? '#d4a574' ?>;
            --button-text: <?= $restaurant['button_text_color'] ?? '#0c0a09' ?>;
            --font: <?= $restaurant['font_color'] ?? '#fafaf9' ?>;
            --muted: #a8a29e;
            --border: rgba(255, 255, 255, 0.06);
            --badge-promo: #dc2626;
            --badge-chef: #d4a574;
            --badge-vegan: #22c55e;
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
        
        /* ===== HERO - Minimal with subtle overlay ===== */
        .hero {
            position: relative;
            height: 28vh;
            min-height: 200px;
            max-height: 350px;
            overflow: hidden;
        }
        
        .hero-bg {
            position: absolute;
            inset: -10%;
            width: 120%;
            height: 120%;
            background-size: cover;
            background-position: center;
            will-change: transform;
            transition: transform 0.1s ease-out;
            filter: brightness(0.5);
        }
        
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                transparent 0%,
                rgba(12, 10, 9, 0.5) 60%,
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
            justify-content: flex-end;
            padding: 0 20px 24px;
        }
        
        .hero-logo {
            max-width: 60%;
            max-height: 120px;
            width: auto;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 12px rgba(0,0,0,0.6));
        }
        
        .hero-name {
            font-family: 'Noto Serif JP', serif;
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.08em;
            color: var(--font);
        }
        
        .hero-subtitle {
            font-size: 0.75rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--primary);
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
            flex-wrap: wrap;
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
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .social-link:hover {
            background: var(--primary);
            color: var(--button-text);
        }
        
        .social-link svg {
            width: 17px;
            height: 17px;
            fill: currentColor;
        }
        
        .social-divider {
            width: 1px;
            height: 28px;
            background: var(--border);
        }
        
        .google-review-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 50px;
            color: var(--font);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .google-review-btn:hover {
            border-color: var(--primary);
        }
        
        .google-review-btn svg { width: 18px; height: 18px; }
        
        .google-review-stars {
            display: flex;
            gap: 2px;
            color: var(--accent);
        }
        
        .google-review-stars svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }
        
        /* ===== Category Navigation ===== */
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
            transition: all 0.25s ease;
            text-decoration: none;
            white-space: nowrap;
            letter-spacing: 0.02em;
        }
        
        .category-chip:hover,
        .category-chip.active {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--button-text);
        }
        
        /* ===== Main Content ===== */
        .main-content {
            padding: 24px 16px 100px;
            max-width: 640px;
            margin: 0 auto;
        }
        
        /* ===== Category Section ===== */
        .category-section {
            margin-bottom: 40px;
            scroll-margin-top: 80px;
        }
        
        .category-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .category-title h2 {
            font-family: 'Noto Serif JP', serif;
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--primary);
            letter-spacing: 0.06em;
        }
        
        .category-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--primary), transparent);
            opacity: 0.3;
        }
        
        /* ===== Product Cards - Clean minimal ===== */
        .products-grid {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        
        .product-card {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: var(--surface);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            border-color: rgba(212, 165, 116, 0.25);
            box-shadow: 0 4px 24px rgba(0,0,0,0.3);
        }
        
        .product-card.unavailable {
            opacity: 0.4;
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
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.03);
        }
        
        .product-no-image {
            width: 100%;
            height: 100%;
            background: var(--surface-alt);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.1);
            font-size: 2rem;
        }
        
        /* Badges float over image */
        .product-badges-float {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        /* Discount badge top right */
        .discount-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--badge-promo);
            color: white;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .play-icon {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        
        .play-icon svg {
            width: 16px;
            height: 16px;
            fill: white;
            margin-left: 2px;
        }
        
        /* Product info - below image, clean */
        .product-info {
            padding: 14px 16px 16px;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
        }
        
        .rating-star {
            color: var(--accent);
            font-size: 0.8rem;
        }
        
        .rating-value {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--font);
        }
        
        .rating-count {
            font-size: 0.75rem;
            color: var(--muted);
        }
        
        .product-name {
            font-family: 'Noto Serif JP', serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--font);
            margin-bottom: 4px;
            line-height: 1.3;
        }
        
        .product-description {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 12px;
        }
        
        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .product-price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .product-price-old {
            font-size: 0.8rem;
            color: var(--muted);
            text-decoration: line-through;
        }
        
        .product-sizes {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .size-price-chip {
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .size-price-chip .size-label {
            color: var(--muted);
            margin-right: 4px;
        }
        
        /* Order button on card */
        .product-order-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--button);
            color: var(--button-text);
            border: none;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .product-order-btn:hover {
            filter: brightness(1.1);
            transform: scale(1.02);
        }
        
        .product-order-btn svg {
            width: 16px;
            height: 16px;
        }
        
        /* ===== Badges ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .badge-promo { background: var(--badge-promo); color: white; }
        .badge-chef { background: rgba(212,165,116,0.15); color: var(--badge-chef); border: 1px solid rgba(212,165,116,0.3); }
        .badge-vegan { background: rgba(34,197,94,0.15); color: var(--badge-vegan); border: 1px solid rgba(34,197,94,0.3); }
        .badge-new { background: var(--accent); color: var(--button-text); }
        .badge-popular { background: var(--badge-promo); color: white; }
        
        /* ===== Modal ===== */
        .modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 0;
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
            width: 36px;
            height: 36px;
            background: rgba(12,10,9,0.7);
            border: 1px solid var(--border);
            border-radius: 50%;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            transition: background 0.2s;
        }
        
        .modal-close:hover {
            background: var(--surface-alt);
        }
        
        .modal-media {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            background: var(--surface);
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .modal-media img,
        .modal-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-media video {
            pointer-events: none;
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
        
        .modal-rating {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }
        
        .modal-rating .rating-star { font-size: 0.9rem; }
        .modal-rating .rating-value { font-size: 0.9rem; }
        
        .modal-name {
            font-family: 'Noto Serif JP', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--font);
        }
        
        .modal-price-row {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .modal-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .modal-price-old {
            font-size: 1rem;
            color: var(--muted);
            text-decoration: line-through;
        }
        
        .modal-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 0 20px 20px;
            -webkit-overflow-scrolling: touch;
        }
        
        .modal-description {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.7;
            white-space: pre-wrap;
            margin-bottom: 16px;
        }
        
        .modal-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        /* Modal order button */
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
            transition: all 0.2s ease;
        }
        
        .modal-order-btn:hover {
            filter: brightness(1.1);
        }
        
        .modal-order-btn svg {
            width: 20px;
            height: 20px;
        }
        
        /* Utilities */
        .hidden { display: none !important; }
        
        /* ===== Closed Banner ===== */
        .closed-banner {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            text-align: center;
            font-size: 0.85rem;
            color: var(--muted);
        }
        
        .closed-banner strong {
            color: var(--badge-promo);
        }
        
        /* ===== Desktop ===== */
        @media (min-width: 768px) {
            .hero {
                height: 40vh;
                max-height: 400px;
            }
            
            .hero-logo {
                max-width: 240px;
                max-height: 160px;
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
    <!-- Hero Section -->
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
    
    <!-- Social Bar -->
    <?php 
    $hasSocial = !empty($restaurant['instagram']) || !empty($restaurant['facebook']) || !empty($restaurant['whatsapp']);
    $hasGoogleReview = !empty($restaurant['google_review_url']);
    ?>
    <?php if ($hasSocial || $hasGoogleReview): ?>
    <div class="social-bar">
        <div class="social-bar-inner">
            <?php if (!empty($restaurant['instagram'])): ?>
                <a href="https://instagram.com/<?= htmlspecialchars(ltrim($restaurant['instagram'], '@')) ?>" target="_blank" rel="noopener" class="social-link" title="Instagram">
                    <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($restaurant['facebook'])): ?>
                <a href="<?= htmlspecialchars($restaurant['facebook']) ?>" target="_blank" rel="noopener" class="social-link" title="Facebook">
                    <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($restaurant['whatsapp'])): ?>
                <?php $whatsappClean = preg_replace('/\D/', '', $restaurant['whatsapp']); ?>
                <a href="https://wa.me/55<?= $whatsappClean ?>" target="_blank" rel="noopener" class="social-link" title="WhatsApp">
                    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($restaurant['google_maps_url'])): ?>
                <a href="<?= htmlspecialchars($restaurant['google_maps_url']) ?>" target="_blank" rel="noopener" class="social-link" title="Google Maps">
                    <svg viewBox="0 0 24 24"><path d="M12 0C7.802 0 4 3.403 4 7.602 4 11.8 7.469 16.812 12 24c4.531-7.188 8-12.2 8-16.398C20 3.403 16.199 0 12 0zm0 11a3 3 0 110-6 3 3 0 010 6z"/></svg>
                </a>
            <?php endif; ?>
            
            <?php if ($hasSocial && $hasGoogleReview): ?>
                <div class="social-divider"></div>
            <?php endif; ?>
            
            <?php if ($hasGoogleReview): ?>
                <a href="<?= htmlspecialchars($restaurant['google_review_url']) ?>" target="_blank" rel="noopener" class="google-review-btn">
                    <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    <span>Avaliar no Google</span>
                    <div class="google-review-stars">
                        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Closed Banner -->
    <?php if (!$isOpen): ?>
    <div class="closed-banner" id="closedBanner">
        <strong>Fechado</strong> — Estamos fora do horário de atendimento
    </div>
    <?php endif; ?>
    
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
                        if (!$product['is_available'] && $product['hide_when_unavailable']) continue;
                        
                        $badges = json_decode($product['badges'] ?? '[]', true) ?: [];
                        $hasPromo = $product['promo_price'] && $product['promo_price'] < $product['price'];
                        $hasVideo = !empty($product['video']);
                        $discountPercent = $hasPromo ? round((1 - $product['promo_price'] / $product['price']) * 100) : 0;
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
                                
                                <?php if (!empty($badges)): ?>
                                    <div class="product-badges-float">
                                        <?php if (in_array('chef', $badges)): ?>
                                            <span class="badge badge-chef">⭐ Chef</span>
                                        <?php endif; ?>
                                        <?php if (in_array('popular', $badges)): ?>
                                            <span class="badge badge-popular">📈 Mais Pedido</span>
                                        <?php endif; ?>
                                        <?php if (in_array('vegan', $badges)): ?>
                                            <span class="badge badge-vegan">🌱 Vegano</span>
                                        <?php endif; ?>
                                        <?php if (in_array('new', $badges)): ?>
                                            <span class="badge badge-new">Novo</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($hasPromo && $discountPercent > 0): ?>
                                    <div class="discount-badge">-<?= $discountPercent ?>%</div>
                                <?php endif; ?>
                                
                                <?php if ($hasVideo && !$hasPromo): ?>
                                    <div class="play-icon">
                                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Product Info below image -->
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                
                                <?php if ($product['description']): ?>
                                    <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="product-footer">
                                    <div class="product-price-row">
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
                                            <span class="product-price-old">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                            <span class="product-price">R$ <?= number_format($product['promo_price'], 2, ',', '.') ?></span>
                                        <?php else: ?>
                                            <span class="product-price">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
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
                <div class="modal-order-area hidden" id="modalOrderArea">
                    <button id="modalOrderBtn" class="modal-order-btn" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            <circle cx="9" cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                        </svg>
                        Pedir
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Hero Parallax (subtle)
        const heroBg = document.getElementById('heroBg');
        let ticking = false;
        
        function updateParallax() {
            const scrollY = window.scrollY;
            const heroHeight = document.querySelector('.hero').offsetHeight;
            if (scrollY < heroHeight) {
                heroBg.style.transform = 'translateY(' + (scrollY * 0.3) + 'px)';
            }
            ticking = false;
        }
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }, { passive: true });
        
        // Modal
        let __scrollTopBeforeModal = 0;
        var __currentModalProduct = null;
        
        function lockPageScroll() {
            __scrollTopBeforeModal = window.scrollY || document.documentElement.scrollTop;
            document.body.style.position = 'fixed';
            document.body.style.top = '-' + __scrollTopBeforeModal + 'px';
            document.body.style.left = '0';
            document.body.style.right = '0';
            document.body.style.width = '100%';
        }
        
        function unlockPageScroll() {
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.left = '';
            document.body.style.right = '';
            document.body.style.width = '';
            window.scrollTo(0, __scrollTopBeforeModal);
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
            
            // Badges
            var badgesEl = document.getElementById('modalBadges');
            badgesEl.innerHTML = '';
            if (product.hasPromo) badgesEl.innerHTML += '<span class="badge badge-promo">Promoção</span>';
            if (product.badges && product.badges.indexOf('chef') !== -1) badgesEl.innerHTML += '<span class="badge badge-chef">⭐ Sugestão do Chef</span>';
            if (product.badges && product.badges.indexOf('vegan') !== -1) badgesEl.innerHTML += '<span class="badge badge-vegan">🌱 Vegano</span>';
            if (product.badges && product.badges.indexOf('new') !== -1) badgesEl.innerHTML += '<span class="badge badge-new">Novo</span>';
            
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
            lockPageScroll();
            logProductView(product.id);
        }
        
        function closeProductModal() {
            var modal = document.getElementById('productModal');
            var video = document.getElementById('modalVideo');
            modal.classList.remove('active');
            video.pause();
            video.src = '';
            unlockPageScroll();
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
        
        var __productModal = document.getElementById('productModal');
        __productModal.addEventListener('touchmove', function(e) {
            var scrollable = e.target.closest('.modal-scrollable');
            if (!scrollable) e.preventDefault();
        }, { passive: false });
        
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
        
        function logProductView(productId) {
            fetch('<?= dirname($_SERVER['SCRIPT_NAME']) ?>/api/log.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    restaurant_id: <?= $restaurant['id'] ?>,
                    product_id: productId,
                    type: 'product_view'
                })
            }).catch(function(){});
        }
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
    </script>
    <script src="/includes/cart.js"></script>
    <?php endif; ?>
</body>
</html>