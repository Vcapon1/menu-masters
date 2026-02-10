<?php
/**
 * CARDÁPIO FLORIPA - Roteador Principal
 * 
 * Este é o ponto de entrada principal da aplicação.
 * Carrega o cardápio do restaurante baseado no slug da URL.
 * 
 * Uso: https://cardapiofloripa.com.br/pizzaria-bella
 */

require_once __DIR__ . '/includes/functions.php';

// Obter slug da URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

// Se não há slug, redirecionar para landing page
if (empty($slug)) {
    header('Location: /landing.php');
    exit;
}

// Buscar restaurante
$restaurant = getRestaurantBySlug($slug);

// Se não encontrou, mostrar 404
if (!$restaurant) {
    http_response_code(404);
    include __DIR__ . '/templates/404.php';
    exit;
}

// Verificar se expirou
if ($restaurant['expires_at'] && strtotime($restaurant['expires_at']) < time()) {
    http_response_code(403);
    include __DIR__ . '/templates/expired.php';
    exit;
}

// Registrar acesso
logMenuAccess($restaurant['id'], null, 'menu_view');

// Buscar categorias e pratos
$categories = getCategories($restaurant['id']);
$products = getProducts($restaurant['id']);

// Agrupar pratos por categoria
$productsByCategory = [];
foreach ($products as $product) {
    $catId = $product['category_id'];
    if (!isset($productsByCategory[$catId])) {
        $productsByCategory[$catId] = [];
    }
    $productsByCategory[$catId][] = $product;
}

// Determinar modo de carrinho via GET
$cartMode = null;
$cartSlug = isset($_GET['cart']) ? sanitize($_GET['cart']) : '';
if (!empty($cartSlug)) {
    $cartMode = getRestaurantCartMode($restaurant['id'], $cartSlug);
}
$tableNumber = isset($_GET['mesa']) ? sanitize($_GET['mesa']) : null;
$isOpen = (bool)($restaurant['is_open'] ?? 1);

// Buscar variações dos produtos (para modal de pedido)
$productVariations = [];
if ($cartMode) {
    foreach ($products as $product) {
        $vars = getProductVariations($product['id']);
        if (!empty($vars)) {
            $productVariations[$product['id']] = $vars;
        }
    }
}

// Gerar CSS customizado
$customCss = generateCssVariables($restaurant);

// Determinar template a ser usado
$templateSlug = $restaurant['template_slug'];
$templatePath = __DIR__ . '/templates/' . $templateSlug . '/template.php';

// Fallback para template padrão
if (!file_exists($templatePath)) {
    $templatePath = __DIR__ . '/templates/classic/template.php';
}

// Renderizar template
include $templatePath;
