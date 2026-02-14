<?php
/**
 * PREMIUM MENU - Gerenciamento de Pratos
 * 
 * CRUD completo para pratos/produtos do restaurante.
 * Inclui gerenciamento de variações para pedido (bordas, adicionais, etc.)
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['restaurant_id'])) {
    header('Location: login.php');
    exit;
}

$restaurantId = $_SESSION['restaurant_id'];
$restaurant = getRestaurantById($restaurantId);
$categories = getCategories($restaurantId);
$message = '';
$error = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
            case 'update':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
                $name = sanitize($_POST['name'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $hasSizes = isset($_POST['has_sizes']) && $_POST['has_sizes'] === '1';
                $price = (float)($_POST['price'] ?? 0);
                $promoPrice = !empty($_POST['promo_price']) ? (float)$_POST['promo_price'] : null;
                
                // Processar tamanhos/preços
                $sizesPrices = null;
                if ($hasSizes && !empty($_POST['size_labels']) && !empty($_POST['size_prices'])) {
                    $sizeLabels = $_POST['size_labels'];
                    $sizePrices = $_POST['size_prices'];
                    $sizesArray = [];
                    
                    for ($i = 0; $i < count($sizeLabels); $i++) {
                        $label = trim($sizeLabels[$i] ?? '');
                        $sizePrice = (float)($sizePrices[$i] ?? 0);
                        if (!empty($label) && $sizePrice > 0) {
                            $sizesArray[] = ['label' => $label, 'price' => $sizePrice];
                        }
                    }
                    
                    if (!empty($sizesArray)) {
                        $sizesPrices = json_encode($sizesArray);
                        $price = min(array_column($sizesArray, 'price'));
                    }
                }
                
                $badges = isset($_POST['badges']) ? json_encode($_POST['badges']) : null;
                $isAvailable = isset($_POST['is_available']) ? 1 : 0;
                $hideWhenUnavailable = isset($_POST['hide_when_unavailable']) ? 1 : 0;
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                
                // Validar
                $priceValid = $hasSizes ? ($sizesPrices !== null) : ($price > 0);
                if (empty($name) || $categoryId === 0 || !$priceValid) {
                    throw new Exception('Preencha todos os campos obrigatórios.');
                }
                
                // Upload de imagem
                $image = $_POST['current_image'] ?? null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $image = uploadImage($_FILES['image'], "restaurants/{$restaurantId}/products");
                }
                
                // Upload de vídeo
                $video = $_POST['current_video'] ?? null;
                if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    if (!$restaurant['supports_video']) {
                        throw new Exception('Seu plano não suporta upload de vídeos.');
                    }
                    $video = uploadVideo($_FILES['video'], "restaurants/{$restaurantId}/videos");
                }
                
                if ($action === 'create') {
                    $sql = "INSERT INTO products (restaurant_id, category_id, name, description, price, promo_price, sizes_prices, image, video, badges, is_available, hide_when_unavailable, sort_order)
                            VALUES (:restaurant_id, :category_id, :name, :description, :price, :promo_price, :sizes_prices, :image, :video, :badges, :is_available, :hide_when_unavailable, :sort_order)";
                    $params = [
                        'restaurant_id' => $restaurantId,
                        'category_id' => $categoryId,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'promo_price' => $promoPrice,
                        'sizes_prices' => $sizesPrices,
                        'image' => $image,
                        'video' => $video,
                        'badges' => $badges,
                        'is_available' => $isAvailable,
                        'hide_when_unavailable' => $hideWhenUnavailable,
                        'sort_order' => $sortOrder,
                    ];
                    $message = 'Prato criado com sucesso!';
                    
                    $stmt = db()->prepare($sql);
                    $stmt->execute($params);
                    $id = db()->lastInsertId();
                } else {
                    $sql = "UPDATE products SET category_id = :category_id, name = :name, description = :description, 
                            price = :price, promo_price = :promo_price, sizes_prices = :sizes_prices, image = :image, video = :video, badges = :badges, 
                            is_available = :is_available, hide_when_unavailable = :hide_when_unavailable, sort_order = :sort_order
                            WHERE id = :id AND restaurant_id = :restaurant_id";
                    $params = [
                        'id' => $id,
                        'restaurant_id' => $restaurantId,
                        'category_id' => $categoryId,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'promo_price' => $promoPrice,
                        'sizes_prices' => $sizesPrices,
                        'image' => $image,
                        'video' => $video,
                        'badges' => $badges,
                        'is_available' => $isAvailable,
                        'hide_when_unavailable' => $hideWhenUnavailable,
                        'sort_order' => $sortOrder,
                    ];
                    $message = 'Prato atualizado com sucesso!';
                    
                    $stmt = db()->prepare($sql);
                    $stmt->execute($params);
                }
                
                // === SALVAR VARIAÇÕES ===
                if ($id) {
                    // Deletar variações antigas
                    $delStmt = db()->prepare("DELETE FROM product_variations WHERE product_id = :pid");
                    $delStmt->execute(['pid' => $id]);
                    
                    // Inserir novas variações
                    $variationGroups = $_POST['variation_group_name'] ?? [];
                    $variationRequired = $_POST['variation_is_required'] ?? [];
                    $variationMax = $_POST['variation_max_selections'] ?? [];
                    $variationOptions = $_POST['variation_options'] ?? [];
                    
                    if (!empty($variationGroups)) {
                        $insertVar = db()->prepare(
                            "INSERT INTO product_variations (product_id, group_name, is_required, max_selections, sort_order, options) 
                             VALUES (:pid, :gname, :req, :maxs, :sort, :opts)"
                        );
                        
                        foreach ($variationGroups as $gi => $groupName) {
                            $groupName = trim($groupName);
                            if (empty($groupName)) continue;
                            
                            $isRequired = isset($variationRequired[$gi]) ? 1 : 0;
                            $maxSel = max(1, (int)($variationMax[$gi] ?? 1));
                            $optionsJson = $variationOptions[$gi] ?? '[]';
                            
                            // Validar que o JSON de opções é válido e tem conteúdo
                            $optsParsed = json_decode($optionsJson, true);
                            if (empty($optsParsed)) continue;
                            
                            $insertVar->execute([
                                'pid' => $id,
                                'gname' => $groupName,
                                'req' => $isRequired,
                                'maxs' => $maxSel,
                                'sort' => $gi,
                                'opts' => $optionsJson,
                            ]);
                        }
                    }
                }
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $sql = "DELETE FROM products WHERE id = :id AND restaurant_id = :restaurant_id";
                $stmt = db()->prepare($sql);
                $stmt->execute(['id' => $id, 'restaurant_id' => $restaurantId]);
                $message = 'Prato removido com sucesso!';
                break;
                
            case 'reorder':
                $orders = json_decode($_POST['orders'] ?? '[]', true);
                foreach ($orders as $order) {
                    $sql = "UPDATE products SET sort_order = :sort WHERE id = :id AND restaurant_id = :restaurant_id";
                    $stmt = db()->prepare($sql);
                    $stmt->execute([
                        'sort' => $order['sort'],
                        'id' => $order['id'],
                        'restaurant_id' => $restaurantId,
                    ]);
                }
                $message = 'Ordem atualizada!';
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar produtos
$products = getProducts($restaurantId, true);

// Buscar variações de cada produto para exibir na edição
$productVariations = [];
foreach ($products as $p) {
    $vars = getProductVariations($p['id']);
    if (!empty($vars)) {
        $productVariations[$p['id']] = $vars;
    }
}

// Badges disponíveis
$availableBadges = [
    'promo' => 'Promoção',
    'new' => 'Novo',
    'chef' => 'Sugestão do Chef',
    'spicy' => 'Picante',
    'vegan' => 'Vegano',
    'gluten_free' => 'Sem Glúten',
    'lactose_free' => 'Sem Lactose',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratos - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .modal-container {
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }
        .modal-header {
            flex-shrink: 0;
            padding: 1.25rem;
            border-bottom: 1px solid #374151;
        }
        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }
        .modal-footer {
            flex-shrink: 0;
            padding: 1rem 1.5rem;
            border-top: 1px solid #374151;
        }
        /* Variações - estilo iFood admin */
        .variation-group {
            border: 1px solid #4b5563;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #1f2937;
        }
        .variation-group-header {
            background: #111827;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: grab;
        }
        .variation-group-header:active { cursor: grabbing; }
        .variation-group-body { padding: 1rem; }
        .variation-option-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #374151;
        }
        .variation-option-row:last-child { border-bottom: none; }
        .badge-required {
            background: #7c3aed;
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-optional {
            background: #374151;
            color: #9ca3af;
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .variation-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #6366f1;
            color: white;
            font-size: 0.7rem;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            font-weight: 700;
        }
        /* Stock Images Modal */
        .stock-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
        @media (min-width: 640px) { .stock-grid { grid-template-columns: repeat(4, 1fr); } }
        .stock-thumb { cursor: pointer; border: 2px solid transparent; border-radius: 0.5rem; overflow: hidden; transition: all 0.15s; position: relative; }
        .stock-thumb:hover { border-color: #7c3aed; transform: scale(1.03); }
        .stock-thumb.selected { border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,0.4); }
        .stock-thumb img { width: 100%; height: 80px; object-fit: cover; }
        .stock-video-icon { position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,0.7); padding: 1px 5px; border-radius: 3px; font-size: 0.65rem; color: #4ade80; }
        .stock-tab { padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; cursor: pointer; transition: all 0.15s; text-transform: capitalize; }
        .stock-tab.active { background: #7c3aed; color: white; }
        .stock-tab:not(.active) { background: #374151; color: #9ca3af; }
        .stock-tab:not(.active):hover { background: #4b5563; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-gray-400 hover:text-white">← Voltar</a>
                <h1 class="font-bold">Gerenciar Pratos</h1>
            </div>
            <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm">
                + Novo Prato
            </button>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="bg-green-900/50 border border-green-600 rounded-lg p-4 mb-6">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Lista de Pratos -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="divide-y divide-gray-700" id="products-list">
                <?php foreach ($products as $index => $product): 
                    $productBadges = json_decode($product['badges'] ?? '[]', true) ?: [];
                    $hasVariations = isset($productVariations[$product['id']]);
                ?>
                    <div class="p-4 flex items-center gap-4" data-id="<?= $product['id'] ?>">
                        <!-- Ordenação -->
                        <div class="flex flex-col gap-1">
                            <button onclick="moveProduct(<?= $product['id'] ?>, 'up')" 
                                    class="text-gray-400 hover:text-white p-1" <?= $index === 0 ? 'disabled' : '' ?>>
                                ▲
                            </button>
                            <button onclick="moveProduct(<?= $product['id'] ?>, 'down')" 
                                    class="text-gray-400 hover:text-white p-1" <?= $index === count($products) - 1 ? 'disabled' : '' ?>>
                                ▼
                            </button>
                        </div>
                        
                        <!-- Imagem/Vídeo -->
                        <?php if (!empty($product['video'])): ?>
                            <video class="w-16 h-16 rounded object-cover" muted>
                                <source src="<?= htmlspecialchars($product['video']) ?>" type="video/mp4">
                            </video>
                        <?php elseif (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars($product['image']) ?>" 
                                 class="w-16 h-16 rounded object-cover">
                        <?php else: ?>
                            <div class="w-16 h-16 bg-gray-700 rounded flex items-center justify-center text-2xl">
                                🍽️
                            </div>
                        <?php endif; ?>
                        
                        <!-- Info -->
                        <div class="flex-1">
                            <p class="font-medium"><?= htmlspecialchars($product['name']) ?></p>
                            <p class="text-sm text-gray-400"><?= htmlspecialchars($product['category_name']) ?></p>
                            <div class="flex gap-1 mt-1">
                                <?php foreach ($productBadges as $badge): ?>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-700">
                                        <?= $availableBadges[$badge] ?? $badge ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if ($hasVariations): ?>
                                    <span class="text-xs px-2 py-0.5 rounded bg-indigo-900 text-indigo-300">
                                        📋 <?= count($productVariations[$product['id']]) ?> variação(ões)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Preço -->
                        <div class="text-right">
                            <?php if (!empty($product['promo_price'])): ?>
                                <p class="text-sm line-through text-gray-500"><?= formatPrice($product['price']) ?></p>
                                <p class="font-bold text-red-400"><?= formatPrice($product['promo_price']) ?></p>
                            <?php else: ?>
                                <p class="font-bold"><?= formatPrice($product['price']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <?php if (!$product['is_available']): ?>
                                <span class="text-xs px-2 py-1 rounded bg-red-900 text-red-400">
                                    <?= $product['hide_when_unavailable'] ? 'Oculto' : 'Indisponível' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs px-2 py-1 rounded bg-green-900 text-green-400">Disponível</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Ações -->
                        <div class="flex gap-2">
                            <button onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)" 
                                    class="text-blue-400 hover:text-blue-300">
                                Editar
                            </button>
                            <form method="post" class="inline" onsubmit="return confirm('Excluir este prato?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                <button type="submit" class="text-red-400 hover:text-red-300">Excluir</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <!-- Modal de Edição -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg max-w-2xl w-full mx-4 modal-container">
            <div class="modal-header">
                <h2 id="modal-title" class="text-xl font-bold">Novo Prato</h2>
            </div>
            <form method="post" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                <div class="modal-body">
                    <input type="hidden" name="action" id="form-action" value="create">
                    <input type="hidden" name="id" id="form-id">
                    <input type="hidden" name="current_image" id="form-current-image">
                    <input type="hidden" name="current_video" id="form-current-video">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Nome *</label>
                            <input type="text" name="name" id="form-name" required
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Categoria *</label>
                            <select name="category_id" id="form-category" required
                                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                                <option value="">Selecione...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Descrição</label>
                            <textarea name="description" id="form-description" rows="3"
                                      class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2"></textarea>
                        </div>
                        
                        <!-- Toggle de Tamanhos -->
                        <div class="bg-gray-700/50 p-3 rounded-lg">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="form-has-sizes" onchange="toggleSizesMode(this.checked)">
                                <span class="text-sm font-medium">Produto com tamanhos variáveis (ex: P/M/G)</span>
                            </label>
                            <p class="text-xs text-gray-400 mt-1 ml-5">Ative para pizzas e produtos com múltiplos tamanhos</p>
                        </div>
                        
                        <input type="hidden" name="has_sizes" id="form-has-sizes-hidden" value="0">
                        
                        <!-- Preço Único (padrão) -->
                        <div id="single-price-section">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm mb-1">Preço *</label>
                                    <input type="number" name="price" id="form-price" step="0.01" min="0"
                                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm mb-1">Preço Promocional</label>
                                    <input type="number" name="promo_price" id="form-promo-price" step="0.01" min="0"
                                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tamanhos/Preços Múltiplos -->
                        <div id="sizes-section" class="hidden">
                            <label class="block text-sm mb-2">Tamanhos e Preços *</label>
                            <div id="sizes-container" class="space-y-2"></div>
                            <button type="button" onclick="addSizeRow()" 
                                    class="mt-2 text-sm text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <span>+</span> Adicionar tamanho
                            </button>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Imagem</label>
                            <div id="current-image-preview" class="mb-2 hidden">
                                <div class="flex items-center gap-2">
                                    <img id="preview-img" src="" class="w-16 h-16 rounded object-cover border border-gray-600">
                                    <div>
                                        <span id="stock-badge" class="hidden text-xs bg-purple-600 px-2 py-0.5 rounded font-medium">📸 Banco de Imagens</span>
                                        <span id="upload-badge" class="hidden text-xs bg-gray-600 px-2 py-0.5 rounded">Upload próprio</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <input type="file" name="image" accept="image/*"
                                       class="flex-1 bg-gray-700 border border-gray-600 rounded px-3 py-2" 
                                       onchange="onFileImageSelected()">
                                <button type="button" onclick="openStockModal()" 
                                        class="bg-purple-600 hover:bg-purple-700 px-3 py-2 rounded text-sm font-medium whitespace-nowrap flex items-center gap-1">
                                    📸 Banco
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($restaurant['supports_video']): ?>
                        <div>
                            <label class="block text-sm mb-1">Vídeo (máx 50MB)</label>
                            <div id="current-video-preview" class="mb-2 hidden flex items-center gap-2">
                                <video id="preview-video" class="w-24 h-16 rounded object-cover border border-gray-600" muted>
                                    <source src="" type="video/mp4">
                                </video>
                                <span class="text-xs text-gray-400">Vídeo atual</span>
                            </div>
                            <input type="file" name="video" accept="video/mp4,video/webm"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm mb-2">Tags</label>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($availableBadges as $key => $label): ?>
                                    <label class="flex items-center gap-1 bg-gray-700 px-3 py-1 rounded cursor-pointer">
                                        <input type="checkbox" name="badges[]" value="<?= $key ?>" class="badge-checkbox">
                                        <span class="text-sm"><?= $label ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_available" id="form-available" checked>
                                <span class="text-sm">Disponível</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="hide_when_unavailable" id="form-hide">
                                <span class="text-sm">Ocultar quando indisponível</span>
                            </label>
                        </div>
                        
                        <!-- ============================================= -->
                        <!-- VARIAÇÕES PARA PEDIDO (estilo iFood) -->
                        <!-- ============================================= -->
                        <div class="border-t border-gray-600 pt-4 mt-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="font-bold text-base">📋 Variações para Pedido</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Configure as opções que o cliente escolhe ao pedir (tipo de pão, adicionais, ponto da carne, etc.)</p>
                                </div>
                                <button type="button" onclick="addVariationGroup()" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1.5 rounded-lg font-medium flex items-center gap-1">
                                    <span class="text-base leading-none">+</span> Grupo
                                </button>
                            </div>
                            
                            <!-- Dica visual -->
                            <div id="variations-empty-hint" class="bg-gray-700/30 border border-dashed border-gray-600 rounded-lg p-4 text-center">
                                <p class="text-gray-400 text-sm">Nenhuma variação configurada.</p>
                                <p class="text-gray-500 text-xs mt-1">Exemplos: "Escolha seu pão", "Adicionais", "Ponto da carne"</p>
                                <button type="button" onclick="addVariationGroup()" 
                                        class="mt-3 text-indigo-400 hover:text-indigo-300 text-sm font-medium">
                                    + Adicionar primeiro grupo
                                </button>
                            </div>
                            
                            <div id="variations-container" class="space-y-4">
                                <!-- Grupos de variação inseridos via JS -->
                            </div>
                        </div>
                        
                        <input type="hidden" name="sort_order" id="form-sort" value="0">
                    </div>
                </div>
                
                <div class="modal-footer flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 py-2 rounded font-medium">
                        Salvar
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Dados das variações existentes (para edição) -->
    <script>
        const productVariationsData = <?= json_encode($productVariations) ?>;
    </script>
    
    <script>
        // =====================================================
        // TAMANHOS
        // =====================================================
        function toggleSizesMode(hasSizes) {
            document.getElementById('form-has-sizes-hidden').value = hasSizes ? '1' : '0';
            document.getElementById('single-price-section').classList.toggle('hidden', hasSizes);
            document.getElementById('sizes-section').classList.toggle('hidden', !hasSizes);
            document.getElementById('form-price').required = !hasSizes;
            
            if (hasSizes && document.querySelectorAll('.size-row').length === 0) {
                addSizeRow('Pequena', '');
                addSizeRow('Média', '');
                addSizeRow('Grande', '');
            }
        }
        
        function addSizeRow(label = '', price = '') {
            const container = document.getElementById('sizes-container');
            const row = document.createElement('div');
            row.className = 'size-row flex gap-2 items-center';
            row.innerHTML = `
                <input type="text" name="size_labels[]" value="${label}" placeholder="Ex: Pequena, Média, Grande" 
                       class="flex-1 bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                <div class="flex items-center gap-1">
                    <span class="text-sm text-gray-400">R$</span>
                    <input type="number" name="size_prices[]" value="${price}" step="0.01" min="0" placeholder="0,00"
                           class="w-24 bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                </div>
                <button type="button" onclick="removeSizeRow(this)" class="text-red-400 hover:text-red-300 p-1">✕</button>
            `;
            container.appendChild(row);
        }
        
        function removeSizeRow(btn) {
            const container = document.getElementById('sizes-container');
            if (container.children.length > 1) {
                btn.closest('.size-row').remove();
            }
        }

        // =====================================================
        // VARIAÇÕES PARA PEDIDO
        // =====================================================
        let variationGroupIndex = 0;

        function updateVariationsEmptyHint() {
            const container = document.getElementById('variations-container');
            const hint = document.getElementById('variations-empty-hint');
            hint.style.display = container.children.length === 0 ? 'block' : 'none';
        }

        function addVariationGroup(data = null) {
            const gi = variationGroupIndex++;
            const container = document.getElementById('variations-container');
            
            const groupName = data ? data.group_name : '';
            const isRequired = data ? data.is_required : false;
            const maxSelections = data ? data.max_selections : 1;
            const options = data ? (typeof data.options === 'string' ? JSON.parse(data.options) : data.options) : [];
            
            const group = document.createElement('div');
            group.className = 'variation-group';
            group.dataset.gi = gi;
            
            group.innerHTML = `
                <div class="variation-group-header">
                    <span class="text-gray-500 cursor-grab text-lg">⠿</span>
                    <div class="flex-1 flex items-center gap-2">
                        <input type="text" name="variation_group_name[${gi}]" value="${escHtml(groupName)}" 
                               placeholder="Nome do grupo (ex: Escolha seu pão)" 
                               class="flex-1 bg-transparent border-b border-gray-600 focus:border-indigo-400 outline-none px-1 py-0.5 text-sm font-semibold text-white placeholder-gray-500">
                        <span class="var-badge ${isRequired ? 'badge-required' : 'badge-optional'}" id="var-badge-${gi}">
                            ${isRequired ? 'OBRIGATÓRIO' : 'OPCIONAL'}
                        </span>
                    </div>
                    <button type="button" onclick="removeVariationGroup(this)" class="text-red-400 hover:text-red-300 text-lg ml-2" title="Remover grupo">✕</button>
                </div>
                <div class="variation-group-body">
                    <!-- Configurações do grupo -->
                    <div class="flex flex-wrap items-center gap-4 mb-3 pb-3 border-b border-gray-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="variation_is_required[${gi}]" value="1" ${isRequired ? 'checked' : ''} 
                                   onchange="toggleRequiredBadge(${gi}, this.checked)"
                                   class="w-4 h-4 rounded border-gray-600 text-indigo-500 focus:ring-indigo-500">
                            <span class="text-sm">Obrigatório</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-400">Máx. seleções:</label>
                            <select name="variation_max_selections[${gi}]" 
                                    class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm w-16"
                                    onchange="updateSelectionHint(${gi}, this.value)">
                                <option value="1" ${maxSelections == 1 ? 'selected' : ''}>1</option>
                                <option value="2" ${maxSelections == 2 ? 'selected' : ''}>2</option>
                                <option value="3" ${maxSelections == 3 ? 'selected' : ''}>3</option>
                                <option value="5" ${maxSelections == 5 ? 'selected' : ''}>5</option>
                                <option value="10" ${maxSelections == 10 ? 'selected' : ''}>10</option>
                            </select>
                        </div>
                        <span class="text-xs text-gray-500" id="var-selection-hint-${gi}">
                            ${maxSelections == 1 ? '→ Cliente escolhe 1 opção (rádio)' : '→ Cliente escolhe até ' + maxSelections + ' opções (checkbox)'}
                        </span>
                    </div>
                    
                    <!-- Lista de opções -->
                    <div class="space-y-0" id="var-options-${gi}">
                        <!-- Opções inseridas via JS -->
                    </div>
                    
                    <button type="button" onclick="addVariationOption(${gi})" 
                            class="mt-3 text-sm text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1">
                        <span class="text-lg leading-none">+</span> Adicionar opção
                    </button>
                </div>
                <!-- Hidden field para JSON das opções -->
                <input type="hidden" name="variation_options[${gi}]" id="var-options-json-${gi}" value="[]">
            `;
            
            container.appendChild(group);
            
            // Adicionar opções existentes ou uma vazia
            if (options.length > 0) {
                options.forEach(opt => addVariationOption(gi, opt));
            } else {
                addVariationOption(gi);
            }
            
            updateVariationsEmptyHint();
            syncVariationOptions(gi);
        }

        function removeVariationGroup(btn) {
            if (confirm('Remover este grupo de variação?')) {
                btn.closest('.variation-group').remove();
                updateVariationsEmptyHint();
            }
        }

        function toggleRequiredBadge(gi, isRequired) {
            const badge = document.getElementById(`var-badge-${gi}`);
            badge.className = 'var-badge ' + (isRequired ? 'badge-required' : 'badge-optional');
            badge.textContent = isRequired ? 'OBRIGATÓRIO' : 'OPCIONAL';
        }

        function updateSelectionHint(gi, max) {
            const hint = document.getElementById(`var-selection-hint-${gi}`);
            hint.textContent = max == 1 
                ? '→ Cliente escolhe 1 opção (rádio)' 
                : '→ Cliente escolhe até ' + max + ' opções (checkbox)';
        }

        function addVariationOption(gi, data = null) {
            const container = document.getElementById(`var-options-${gi}`);
            const label = data ? data.label : '';
            const description = data ? (data.description || '') : '';
            const price = data ? (data.price || 0) : 0;
            const priceDisplay = price > 0 ? price : '';
            
            const row = document.createElement('div');
            row.className = 'variation-option-row';
            row.innerHTML = `
                <div class="flex-1">
                    <input type="text" value="${escHtml(label)}" placeholder="Nome da opção (ex: Pão brioche)" 
                           class="var-opt-label w-full bg-transparent border-b border-gray-700 focus:border-indigo-400 outline-none px-1 py-0.5 text-sm text-white placeholder-gray-500"
                           onchange="syncVariationOptions(${gi})" onkeyup="syncVariationOptions(${gi})">
                    <input type="text" value="${escHtml(description)}" placeholder="Descrição curta (opcional)" 
                           class="var-opt-desc w-full bg-transparent outline-none px-1 py-0.5 text-xs text-gray-400 placeholder-gray-600 mt-0.5"
                           onchange="syncVariationOptions(${gi})">
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <span class="text-xs text-gray-500">+ R$</span>
                    <input type="number" value="${priceDisplay}" step="0.01" min="0" placeholder="0,00"
                           class="var-opt-price w-20 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm text-right"
                           onchange="syncVariationOptions(${gi})">
                </div>
                <button type="button" onclick="removeVariationOption(this, ${gi})" 
                        class="text-red-400 hover:text-red-300 p-1 flex-shrink-0" title="Remover opção">✕</button>
            `;
            container.appendChild(row);
            syncVariationOptions(gi);
        }

        function removeVariationOption(btn, gi) {
            const container = document.getElementById(`var-options-${gi}`);
            if (container.children.length > 1) {
                btn.closest('.variation-option-row').remove();
                syncVariationOptions(gi);
            }
        }

        function syncVariationOptions(gi) {
            const container = document.getElementById(`var-options-${gi}`);
            const rows = container.querySelectorAll('.variation-option-row');
            const options = [];
            
            rows.forEach(row => {
                const label = row.querySelector('.var-opt-label').value.trim();
                const description = row.querySelector('.var-opt-desc').value.trim();
                const price = parseFloat(row.querySelector('.var-opt-price').value) || 0;
                
                if (label) {
                    const opt = { label, price };
                    if (description) opt.description = description;
                    options.push(opt);
                }
            });
            
            document.getElementById(`var-options-json-${gi}`).value = JSON.stringify(options);
        }

        function escHtml(str) {
            if (!str) return '';
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // =====================================================
        // MODAL
        // =====================================================
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal').classList.add('flex');
            document.getElementById('modal-title').textContent = 'Novo Prato';
            document.getElementById('form-action').value = 'create';
            document.getElementById('form-id').value = '';
            document.getElementById('form-name').value = '';
            document.getElementById('form-category').value = '';
            document.getElementById('form-description').value = '';
            document.getElementById('form-price').value = '';
            document.getElementById('form-promo-price').value = '';
            document.getElementById('form-available').checked = true;
            document.getElementById('form-hide').checked = false;
            document.querySelectorAll('.badge-checkbox').forEach(cb => cb.checked = false);
            
            // Reset tamanhos
            document.getElementById('form-has-sizes').checked = false;
            document.getElementById('form-has-sizes-hidden').value = '0';
            document.getElementById('single-price-section').classList.remove('hidden');
            document.getElementById('sizes-section').classList.add('hidden');
            document.getElementById('sizes-container').innerHTML = '';
            document.getElementById('form-price').required = true;
            
            // Esconder previews e badges
            document.getElementById('current-image-preview').classList.add('hidden');
            document.getElementById('stock-badge').classList.add('hidden');
            document.getElementById('upload-badge').classList.add('hidden');
            const videoPreview = document.getElementById('current-video-preview');
            if (videoPreview) videoPreview.classList.add('hidden');
            
            // Reset variações
            variationGroupIndex = 0;
            document.getElementById('variations-container').innerHTML = '';
            updateVariationsEmptyHint();
        }
        
        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('modal').classList.remove('flex');
        }
        
        function editProduct(product) {
            openModal();
            document.getElementById('modal-title').textContent = 'Editar Prato';
            document.getElementById('form-action').value = 'update';
            document.getElementById('form-id').value = product.id;
            document.getElementById('form-name').value = product.name;
            document.getElementById('form-category').value = product.category_id;
            document.getElementById('form-description').value = product.description || '';
            document.getElementById('form-available').checked = product.is_available == 1;
            document.getElementById('form-hide').checked = product.hide_when_unavailable == 1;
            document.getElementById('form-current-image').value = product.image || '';
            document.getElementById('form-current-video').value = product.video || '';
            document.getElementById('form-sort').value = product.sort_order || 0;
            
            // Tamanhos
            const sizesPrices = product.sizes_prices ? JSON.parse(product.sizes_prices) : null;
            const hasSizes = sizesPrices && Array.isArray(sizesPrices) && sizesPrices.length > 0;
            
            document.getElementById('form-has-sizes').checked = hasSizes;
            document.getElementById('form-has-sizes-hidden').value = hasSizes ? '1' : '0';
            document.getElementById('single-price-section').classList.toggle('hidden', hasSizes);
            document.getElementById('sizes-section').classList.toggle('hidden', !hasSizes);
            document.getElementById('form-price').required = !hasSizes;
            document.getElementById('sizes-container').innerHTML = '';
            
            if (hasSizes) {
                sizesPrices.forEach(size => addSizeRow(size.label, size.price));
            } else {
                document.getElementById('form-price').value = product.price;
                document.getElementById('form-promo-price').value = product.promo_price || '';
            }
            
            // Preview de imagem
            const imagePreview = document.getElementById('current-image-preview');
            const previewImg = document.getElementById('preview-img');
            const stockBadge = document.getElementById('stock-badge');
            const uploadBadge = document.getElementById('upload-badge');
            if (product.image) {
                previewImg.src = product.image;
                imagePreview.classList.remove('hidden');
                // Detect stock image
                if (product.image.indexOf('stock-images/') !== -1) {
                    stockBadge.classList.remove('hidden');
                    uploadBadge.classList.add('hidden');
                } else {
                    stockBadge.classList.add('hidden');
                    uploadBadge.classList.remove('hidden');
                }
            } else {
                imagePreview.classList.add('hidden');
                stockBadge.classList.add('hidden');
                uploadBadge.classList.add('hidden');
            }
            
            // Preview de vídeo
            const videoPreview = document.getElementById('current-video-preview');
            const previewVideo = document.getElementById('preview-video');
            if (videoPreview && product.video) {
                previewVideo.querySelector('source').src = product.video;
                previewVideo.load();
                videoPreview.classList.remove('hidden');
            } else if (videoPreview) {
                videoPreview.classList.add('hidden');
            }
            
            // Badges
            const badges = JSON.parse(product.badges || '[]');
            document.querySelectorAll('.badge-checkbox').forEach(cb => {
                cb.checked = badges.includes(cb.value);
            });
            
            // === CARREGAR VARIAÇÕES EXISTENTES ===
            variationGroupIndex = 0;
            document.getElementById('variations-container').innerHTML = '';
            const variations = productVariationsData[product.id] || [];
            variations.forEach(v => addVariationGroup(v));
            updateVariationsEmptyHint();
        }
        
        function moveProduct(id, direction) {
            const list = document.getElementById('products-list');
            const items = Array.from(list.children);
            const currentIndex = items.findIndex(item => item.dataset.id == id);
            
            if (direction === 'up' && currentIndex > 0) {
                list.insertBefore(items[currentIndex], items[currentIndex - 1]);
            } else if (direction === 'down' && currentIndex < items.length - 1) {
                list.insertBefore(items[currentIndex + 1], items[currentIndex]);
            }
            
            saveOrder();
        }
        
        function saveOrder() {
            const list = document.getElementById('products-list');
            const items = Array.from(list.children);
            const orders = items.map((item, index) => ({
                id: parseInt(item.dataset.id),
                sort: index
            }));
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reorder">
                <input type="hidden" name="orders" value='${JSON.stringify(orders)}'>
            `;
            document.body.appendChild(form);
            form.submit();
        }
        // =====================================================
        // BANCO DE IMAGENS (Stock)
        // =====================================================
        let stockImagesCache = null;
        let stockCategoriesCache = null;
        
        function openStockModal() {
            document.getElementById('stock-modal').classList.remove('hidden');
            document.getElementById('stock-modal').classList.add('flex');
            loadStockImages();
        }
        
        function closeStockModal() {
            document.getElementById('stock-modal').classList.add('hidden');
            document.getElementById('stock-modal').classList.remove('flex');
        }
        
        async function loadStockImages(category = '', search = '') {
            const grid = document.getElementById('stock-grid');
            grid.innerHTML = '<p class="col-span-full text-center text-gray-400 py-8">Carregando...</p>';
            
            try {
                let url = 'stock-images.php';
                // Use API endpoint
                url = '../api/stock-images.php';
                const params = new URLSearchParams();
                if (category) params.set('category', category);
                if (search) params.set('search', search);
                if (params.toString()) url += '?' + params.toString();
                
                const res = await fetch(url);
                const data = await res.json();
                
                if (!data.success) throw new Error(data.error || 'Erro');
                
                stockImagesCache = data.images;
                stockCategoriesCache = data.categories;
                
                // Render tabs
                renderStockTabs(data.categories, category);
                
                // Render grid
                if (data.images.length === 0) {
                    grid.innerHTML = '<p class="col-span-full text-center text-gray-400 py-8">Nenhuma imagem encontrada.</p>';
                    return;
                }
                
                grid.innerHTML = data.images.map(img => `
                    <div class="stock-thumb" onclick="selectStockImage(${img.id})" data-id="${img.id}">
                        <img src="${img.image_url}" alt="${escHtml(img.name)}" loading="lazy">
                        ${img.has_video ? '<span class="stock-video-icon">🎬</span>' : ''}
                        <p class="text-xs text-center py-1 px-1 truncate text-gray-300">${escHtml(img.name)}</p>
                    </div>
                `).join('');
                
            } catch (err) {
                grid.innerHTML = `<p class="col-span-full text-center text-red-400 py-8">Erro: ${err.message}</p>`;
            }
        }
        
        function renderStockTabs(categories, active) {
            const container = document.getElementById('stock-tabs');
            let html = `<span class="stock-tab ${!active ? 'active' : ''}" onclick="filterStockCategory('')">Todas</span>`;
            categories.forEach(cat => {
                html += `<span class="stock-tab ${active === cat ? 'active' : ''}" onclick="filterStockCategory('${cat}')">${cat}</span>`;
            });
            container.innerHTML = html;
        }
        
        function filterStockCategory(cat) {
            const search = document.getElementById('stock-search').value;
            loadStockImages(cat, search);
        }
        
        function searchStock() {
            const search = document.getElementById('stock-search').value;
            // Get active tab
            const activeTab = document.querySelector('.stock-tab.active');
            const cat = activeTab ? (activeTab.textContent.trim().toLowerCase() === 'todas' ? '' : activeTab.textContent.trim().toLowerCase()) : '';
            loadStockImages(cat, search);
        }
        
        function selectStockImage(id) {
            const img = stockImagesCache.find(i => i.id === id);
            if (!img) return;
            
            // Set image
            document.getElementById('form-current-image').value = img.image_url;
            document.getElementById('preview-img').src = img.image_url;
            document.getElementById('current-image-preview').classList.remove('hidden');
            
            // Show stock badge
            document.getElementById('stock-badge').classList.remove('hidden');
            document.getElementById('upload-badge').classList.add('hidden');
            
            // Set video if available
            if (img.has_video && img.video_url) {
                document.getElementById('form-current-video').value = img.video_url;
                const videoPreview = document.getElementById('current-video-preview');
                const previewVideo = document.getElementById('preview-video');
                if (videoPreview && previewVideo) {
                    previewVideo.querySelector('source').src = img.video_url;
                    previewVideo.load();
                    videoPreview.classList.remove('hidden');
                }
            }
            
            closeStockModal();
        }
        
        function onFileImageSelected() {
            // When user selects a file upload, clear stock badges
            document.getElementById('stock-badge').classList.add('hidden');
            document.getElementById('upload-badge').classList.remove('hidden');
            document.getElementById('form-current-image').value = '';
        }
        
        // Debounce for search
        let stockSearchTimeout;
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('stock-search');
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    clearTimeout(stockSearchTimeout);
                    stockSearchTimeout = setTimeout(searchStock, 300);
                });
            }
        });
    </script>
    
    <!-- Modal do Banco de Imagens -->
    <div id="stock-modal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[60]">
        <div class="bg-gray-800 rounded-lg max-w-xl w-full mx-4 modal-container" style="max-height: 80vh;">
            <div class="modal-header flex items-center justify-between">
                <h2 class="text-lg font-bold">📸 Banco de Imagens</h2>
                <button type="button" onclick="closeStockModal()" class="text-gray-400 hover:text-white text-xl">✕</button>
            </div>
            <div class="px-4 py-3 border-b border-gray-700">
                <input type="text" id="stock-search" placeholder="Buscar por nome ou tag..."
                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm mb-2">
                <div id="stock-tabs" class="flex flex-wrap gap-1.5">
                    <!-- Tabs renderizadas via JS -->
                </div>
            </div>
            <div class="p-4 overflow-y-auto flex-1">
                <div id="stock-grid" class="stock-grid">
                    <!-- Grid renderizado via JS -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>
