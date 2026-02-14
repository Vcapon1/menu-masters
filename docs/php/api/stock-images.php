<?php
/**
 * PREMIUM MENU - API: Banco de Imagens
 * 
 * Endpoint público (somente leitura) para listar imagens do banco compartilhado.
 * 
 * GET /api/stock-images.php
 * GET /api/stock-images.php?category=bebidas
 * GET /api/stock-images.php?search=coca
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/functions.php';

try {
    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;

    // Buscar categorias disponíveis
    $categories = getStockCategories();

    // Buscar imagens
    $images = getStockImages($category, $search);

    // Montar URLs completas
    $stockBaseUrl = UPLOAD_URL . 'stock-images/';
    $videoBaseUrl = UPLOAD_URL . 'stock-images/videos/';

    $result = [];
    foreach ($images as $img) {
        $result[] = [
            'id' => (int)$img['id'],
            'name' => $img['name'],
            'category' => $img['category'],
            'image_url' => $stockBaseUrl . $img['filename'],
            'video_url' => $img['video_filename'] ? $videoBaseUrl . $img['video_filename'] : null,
            'has_video' => !empty($img['video_filename']),
            'tags' => $img['tags'],
        ];
    }

    echo json_encode([
        'success' => true,
        'images' => $result,
        'categories' => $categories,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
