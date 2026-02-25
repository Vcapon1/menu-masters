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
                $image = isset($_POST['current_image']) ? trim((string)$_POST['current_image']) : null;
                if ($image === '') {
                    $image = null;
                }

                // Normalizar caminhos relativos antigos (ex: "uploads/..." -> URL absoluta)
                if ($image && strpos($image, 'uploads/') === 0) {
                    $image = rtrim(APP_URL, '/') . '/' . ltrim($image, '/');
                } elseif ($image && strpos($image, '/uploads/') === 0) {
                    $image = rtrim(APP_URL, '/') . $image;
                }

                // Se current_image vier em base64 (data URI ou base64 puro), converter para arquivo
                if ($image) {
                    $mime = 'jpeg';
                    $base64Data = null;

                    if (preg_match('/^data:image\/([a-zA-Z0-9.+-]+)(?:;charset=[^;]+)?;base64,(.*)$/is', $image, $matches)) {
                        $mime = strtolower($matches[1]);
                        $base64Data = $matches[2];
                    } elseif (strlen($image) > 500 && preg_match('/^[A-Za-z0-9+\/=\s]+$/', $image)) {
                        // fallback para respostas que venham sem prefixo data:image
                        $base64Data = $image;
                    }

                    if ($base64Data !== null) {
                        $base64Data = preg_replace('/\s+/', '', $base64Data);
                        $binaryData = base64_decode($base64Data, true);

                        if ($binaryData === false) {
                            throw new Exception('Imagem da IA inválida. Gere novamente e tente salvar.');
                        }

                        if (strlen($binaryData) > MAX_IMAGE_SIZE) {
                            throw new Exception('Imagem da IA muito grande. Gere novamente com menor resolução.');
                        }

                        $extMap = [
                            'jpeg' => 'jpg',
                            'jpg' => 'jpg',
                            'png' => 'png',
                            'webp' => 'webp',
                            'gif' => 'gif',
                        ];
                        $ext = $extMap[$mime] ?? 'jpg';

                        $folder = "restaurants/{$restaurantId}/products";
                        $uploadDir = UPLOAD_DIR . $folder . '/';
                        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                            throw new Exception('Não foi possível criar pasta para salvar imagem.');
                        }

                        $filename = 'ai_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $filePath = $uploadDir . $filename;

                        if (file_put_contents($filePath, $binaryData) === false) {
                            throw new Exception('Erro ao salvar imagem gerada por IA.');
                        }

                        $image = UPLOAD_URL . $folder . '/' . $filename;
                    }
                }

                // Segurança: evita gravar base64 bruto no banco se algo inesperado acontecer
                if ($image && strpos($image, 'data:image/') === 0) {
                    throw new Exception('Formato de imagem inválido para salvar. Tente gerar novamente.');
                }

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
                    <div class="p-3 sm:p-4" data-id="<?= $product['id'] ?>">
                        <div class="flex items-center gap-2 sm:gap-4">
                            <!-- Ordenação -->
                            <div class="flex flex-col gap-0.5 flex-shrink-0">
                                <button onclick="moveProduct(<?= $product['id'] ?>, 'up')" 
                                        class="text-gray-400 hover:text-white p-0.5 text-xs" <?= $index === 0 ? 'disabled' : '' ?>>
                                    ▲
                                </button>
                                <button onclick="moveProduct(<?= $product['id'] ?>, 'down')" 
                                        class="text-gray-400 hover:text-white p-0.5 text-xs" <?= $index === count($products) - 1 ? 'disabled' : '' ?>>
                                    ▼
                                </button>
                            </div>
                            
                            <!-- Imagem/Vídeo -->
                            <?php if (!empty($product['video'])): ?>
                                <video class="w-12 h-12 sm:w-16 sm:h-16 rounded object-cover flex-shrink-0" muted>
                                    <source src="<?= htmlspecialchars($product['video']) ?>" type="video/mp4">
                                </video>
                            <?php elseif (!empty($product['image'])): ?>
                                <img src="<?= htmlspecialchars($product['image']) ?>" 
                                     class="w-12 h-12 sm:w-16 sm:h-16 rounded object-cover flex-shrink-0">
                            <?php else: ?>
                                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-700 rounded flex items-center justify-center text-xl sm:text-2xl flex-shrink-0">
                                    🍽️
                                </div>
                            <?php endif; ?>
                            
                            <!-- Info + Preço -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-medium text-sm sm:text-base truncate"><?= htmlspecialchars($product['name']) ?></p>
                                        <p class="text-xs sm:text-sm text-gray-400 truncate"><?= htmlspecialchars($product['category_name']) ?></p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <?php if (!empty($product['promo_price'])): ?>
                                            <p class="text-xs line-through text-gray-500"><?= formatPrice($product['price']) ?></p>
                                            <p class="font-bold text-sm text-red-400"><?= formatPrice($product['promo_price']) ?></p>
                                        <?php else: ?>
                                            <p class="font-bold text-sm"><?= formatPrice($product['price']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <?php foreach ($productBadges as $badge): ?>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-gray-700">
                                            <?= $availableBadges[$badge] ?? $badge ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if ($hasVariations): ?>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-indigo-900 text-indigo-300">
                                            📋 <?= count($productVariations[$product['id']]) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status + Ações (row below on mobile) -->
                        <div class="flex items-center justify-end gap-2 mt-2 ml-8 sm:ml-20">
                            <?php if (!$product['is_available']): ?>
                                <span class="text-xs px-2 py-1 rounded bg-red-900 text-red-400">
                                    <?= $product['hide_when_unavailable'] ? 'Oculto' : 'Indisponível' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs px-2 py-1 rounded bg-green-900 text-green-400">Disponível</span>
                            <?php endif; ?>
                            <button onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)" 
                                    class="text-blue-400 hover:text-blue-300 text-sm px-2 py-1">
                                ✏️ Editar
                            </button>
                            <form method="post" class="inline" onsubmit="return confirm('Excluir este prato?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                <button type="submit" class="text-red-400 hover:text-red-300 text-sm px-2 py-1">🗑️</button>
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
                            <div class="flex items-center gap-2 flex-wrap">
                                    <img id="preview-img" src="" class="w-16 h-16 rounded object-cover border border-gray-600">
                                    <div class="flex flex-wrap gap-1 items-center">
                                        <span id="stock-badge" class="hidden text-xs bg-purple-600 px-2 py-0.5 rounded font-medium">📸 Banco</span>
                                        <span id="upload-badge" class="hidden text-xs bg-gray-600 px-2 py-0.5 rounded">Upload</span>
                                        <span id="ai-badge" class="hidden text-xs bg-gradient-to-r from-pink-600 to-purple-600 px-2 py-0.5 rounded font-medium">✨ IA</span>
                                    </div>
                                    <button type="button" onclick="openEnhanceModal()" 
                                            class="text-xs bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 px-2 py-1 rounded font-medium whitespace-nowrap" 
                                            id="btn-enhance-existing" title="Melhorar esta imagem com IA">
                                        ✨ Melhorar
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <input type="file" name="image" accept="image/*" id="file-image-input"
                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm" 
                                       onchange="onFileImageSelected()">
                                <div class="flex gap-2">
                                    <button type="button" onclick="openStockModal()" 
                                            class="flex-1 bg-purple-600 hover:bg-purple-700 px-3 py-2 rounded text-sm font-medium flex items-center justify-center gap-1">
                                        📸 Banco
                                    </button>
                                    <button type="button" onclick="openEnhanceModal()" 
                                            class="flex-1 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 px-3 py-2 rounded text-sm font-medium flex items-center justify-center gap-1">
                                        ✨ IA
                                    </button>
                                </div>
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
                            <div class="space-y-2">
                                <input type="file" name="video" accept="video/mp4,video/webm"
                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                <button type="button" onclick="openVideoAiModal()" 
                                        class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 px-3 py-2 rounded text-sm font-medium flex items-center justify-center gap-1">
                                    🎬 Gerar Vídeo com IA
                                </button>
                            </div>
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

        function normalizeImageUrl(src) {
            if (!src) return '';
            if (src.startsWith('data:') || /^https?:\/\//i.test(src) || src.startsWith('//')) return src;
            if (src.startsWith('/')) return src;
            return '../' + src.replace(/^\.\/?/, '');
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
            document.getElementById('ai-badge').classList.add('hidden');
            document.getElementById('btn-enhance-existing').classList.remove('hidden');
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
            const existingImage = product.image || '';
            document.getElementById('form-current-image').value = existingImage;
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
            if (existingImage) {
                previewImg.src = normalizeImageUrl(existingImage);
                imagePreview.classList.remove('hidden');
                const aiBadge = document.getElementById('ai-badge');
                const enhanceBtn = document.getElementById('btn-enhance-existing');
                aiBadge.classList.add('hidden');
                enhanceBtn.classList.remove('hidden');
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
    <div id="stock-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center" style="z-index: 60;">
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
    
    <!-- Modal de Melhoria por IA -->
    <div id="enhance-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center" style="z-index: 70;">
        <div class="bg-gray-800 rounded-lg max-w-3xl w-full mx-2 sm:mx-4 modal-container" style="max-height: 90vh; max-height: 90dvh;">
            <div class="modal-header flex items-center justify-between">
                <h2 class="text-lg font-bold">✨ Melhorar Foto com IA</h2>
                <button type="button" onclick="closeEnhanceModal()" class="text-gray-400 hover:text-white text-xl">✕</button>
            </div>
            
            <!-- Fase 1: Upload + Escolha de Estilo -->
            <div id="enhance-phase-upload" class="modal-body">
                <div class="mb-4">
                    <label class="block text-sm mb-2 font-medium">Foto do Prato</label>
                    <div id="enhance-drop-zone" class="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-purple-500 transition-colors"
                         onclick="document.getElementById('enhance-file-input').click()">
                        <div id="enhance-preview-area" class="hidden">
                            <img id="enhance-preview-img" src="" class="max-h-48 mx-auto rounded-lg mb-2">
                            <p class="text-xs text-gray-400">Clique para trocar a imagem</p>
                        </div>
                        <div id="enhance-upload-hint">
                            <p class="text-3xl mb-2">📷</p>
                            <p class="text-gray-400">Clique ou arraste uma foto do prato aqui</p>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG ou WebP</p>
                        </div>
                    </div>
                    <input type="file" id="enhance-file-input" accept="image/*" class="hidden" onchange="onEnhanceFileSelected(this)">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm mb-2 font-medium">Nome do Prato (opcional, melhora o resultado)</label>
                    <input type="text" id="enhance-food-name" placeholder="Ex: Hambúrguer artesanal, Pizza margherita..." 
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm mb-3 font-medium">Escolha o Estilo</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="enhance-styles">
                        <div class="enhance-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-purple-500 transition-all" 
                             data-style="minimalist" onclick="selectEnhanceStyle('minimalist')">
                            <div class="text-xl mb-1">🍣</div>
                            <h4 class="font-bold text-sm">Minimalista & Moderno</h4>
                            <p class="text-xs text-gray-400 mt-1">Fine Dining / Sushi — Foco total na comida, pratos clean</p>
                        </div>
                        <div class="enhance-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-purple-500 transition-all" 
                             data-style="industrial" onclick="selectEnhanceStyle('industrial')">
                            <div class="text-xl mb-1">🍔</div>
                            <h4 class="font-bold text-sm">Industrial & Urbano</h4>
                            <p class="text-xs text-gray-400 mt-1">Hamburguerias / Pubs — Visual noturno e de rua</p>
                        </div>
                        <div class="enhance-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-purple-500 transition-all" 
                             data-style="solar" onclick="selectEnhanceStyle('solar')">
                            <div class="text-xl mb-1">🥗</div>
                            <h4 class="font-bold text-sm">Solar & Orgânico</h4>
                            <p class="text-xs text-gray-400 mt-1">Saudável / Cafés / Brunch — Frescor e leveza</p>
                        </div>
                        <div class="enhance-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-purple-500 transition-all" 
                             data-style="traditional" onclick="selectEnhanceStyle('traditional')">
                            <div class="text-xl mb-1">🍕</div>
                            <h4 class="font-bold text-sm">Tradicional & Aconchegante</h4>
                            <p class="text-xs text-gray-400 mt-1">Pizzarias / Padarias — Tradição e preparo artesanal</p>
                        </div>
                        <div class="enhance-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-purple-500 transition-all" 
                             data-style="pop" onclick="selectEnhanceStyle('pop')">
                            <div class="text-xl mb-1">🍦</div>
                            <h4 class="font-bold text-sm">Pop & Colorido</h4>
                            <p class="text-xs text-gray-400 mt-1">Sorveterias / Docerias / Fast Food — Visual lúdico</p>
                        </div>
                        <div class="enhance-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-purple-500 transition-all" 
                             data-style="customizavel" onclick="selectEnhanceStyle('customizavel')">
                            <div class="text-xl mb-1">🎨</div>
                            <h4 class="font-bold text-sm">Customizável (Prato + Ambiente)</h4>
                            <p class="text-xs text-gray-400 mt-1">Envie 2 fotos + ajuste enquadramento, ângulo, luz e fundo</p>
                        </div>
                    </div>
                </div>
                
                <!-- Opção de cor para estilo Pop -->
                <div id="enhance-pop-color" class="mb-4 hidden">
                    <label class="block text-sm mb-2 font-medium">Cor de Fundo (estilo Pop)</label>
                    <div class="flex gap-2 flex-wrap">
                        <button type="button" class="pop-color-btn w-8 h-8 rounded-full border-2 border-gray-600 hover:border-white" 
                                style="background: #fecdd3" data-color="pink" onclick="selectPopColor('pink')"></button>
                        <button type="button" class="pop-color-btn w-8 h-8 rounded-full border-2 border-gray-600 hover:border-white" 
                                style="background: #bfdbfe" data-color="blue" onclick="selectPopColor('blue')"></button>
                        <button type="button" class="pop-color-btn w-8 h-8 rounded-full border-2 border-gray-600 hover:border-white" 
                                style="background: #bbf7d0" data-color="green" onclick="selectPopColor('green')"></button>
                        <button type="button" class="pop-color-btn w-8 h-8 rounded-full border-2 border-gray-600 hover:border-white" 
                                style="background: #fef08a" data-color="yellow" onclick="selectPopColor('yellow')"></button>
                        <button type="button" class="pop-color-btn w-8 h-8 rounded-full border-2 border-gray-600 hover:border-white" 
                                style="background: #e9d5ff" data-color="purple" onclick="selectPopColor('purple')"></button>
                        <button type="button" class="pop-color-btn w-8 h-8 rounded-full border-2 border-gray-600 hover:border-white" 
                                style="background: #fed7aa" data-color="orange" onclick="selectPopColor('orange')"></button>
                    </div>
                </div>
                
                <!-- Upload de imagem do ambiente + parâmetros para estilo Customizável -->
                <div id="enhance-environment-upload" class="mb-4 hidden">
                    <label class="block text-sm mb-2 font-medium">📍 Foto do Ambiente</label>
                    <div id="enhance-env-drop-zone" class="border-2 border-dashed border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:border-purple-500 transition-colors"
                         onclick="document.getElementById('enhance-env-file-input').click()">
                        <div id="enhance-env-preview-area" class="hidden">
                            <img id="enhance-env-preview-img" src="" class="max-h-32 mx-auto rounded-lg mb-2">
                            <p class="text-xs text-gray-400">Clique para trocar a foto do ambiente</p>
                        </div>
                        <div id="enhance-env-upload-hint">
                            <p class="text-2xl mb-1">🏠</p>
                            <p class="text-gray-400 text-sm">Clique ou arraste a foto do <strong>ambiente</strong> aqui</p>
                            <p class="text-xs text-gray-500 mt-1">Ex: interior do restaurante, balcão, mesa decorada...</p>
                        </div>
                    </div>
                    <input type="file" id="enhance-env-file-input" accept="image/*" class="hidden" onchange="onEnhanceEnvFileSelected(this)">
                    
                    <!-- Parâmetros do estilo Customizável -->
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <div>
                            <label class="block text-xs mb-1 font-medium text-gray-300">📐 Enquadramento</label>
                            <select id="enhance-framing" class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1.5 text-sm">
                                <option value="90">90% — Close-up (padrão)</option>
                                <option value="70">70% — Mostra ambiente</option>
                                <option value="200">200% — Macro extremo</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-medium text-gray-300">📷 Ângulo</label>
                            <select id="enhance-angle" class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1.5 text-sm">
                                <option value="45">45° — Três quartos (padrão)</option>
                                <option value="top">De cima — Flatlay</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-medium text-gray-300">💡 Iluminação</label>
                            <select id="enhance-lighting" class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1.5 text-sm">
                                <option value="professional">Profissional (padrão)</option>
                                <option value="ambient">Ambiente natural</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-medium text-gray-300">🖼️ Fundo</label>
                            <select id="enhance-bg-effect" class="w-full bg-gray-700 border border-gray-600 rounded px-2 py-1.5 text-sm">
                                <option value="blurred_darkened">Desfocado + Escurecido (padrão)</option>
                                <option value="blurred">Desfocado</option>
                                <option value="darkened">Escurecido</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="button" onclick="startEnhance()" id="btn-start-enhance"
                        class="w-full bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 py-3 rounded-lg font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                    ✨ Melhorar com IA
                </button>
            </div>
            
            <!-- Fase 2: Loading -->
            <div id="enhance-phase-loading" class="modal-body hidden">
                <div class="text-center py-12">
                    <div class="inline-block animate-spin text-5xl mb-4">✨</div>
                    <p class="text-lg font-bold mb-2">Melhorando sua foto...</p>
                    <p class="text-sm text-gray-400">A IA está aplicando o estilo escolhido</p>
                    <p class="text-xs text-gray-500 mt-2">Isso pode levar até 30 segundos</p>
                </div>
            </div>
            
            <!-- Fase 3: Resultado -->
            <div id="enhance-phase-result" class="modal-body hidden">
                <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1 text-center">📷 Original</p>
                        <img id="enhance-original-result" src="" class="w-full rounded-lg border border-gray-600">
                    </div>
                    <div class="relative">
                        <p class="text-xs text-gray-400 mb-1 text-center">✨ Melhorada</p>
                        <div class="relative group">
                            <img id="enhance-result-img" src="" class="w-full rounded-lg border border-purple-500">
                            <button type="button" onclick="zoomEnhancedImage()" 
                                    class="absolute top-2 right-2 bg-black/70 hover:bg-black/90 text-white w-8 h-8 rounded-full flex items-center justify-center text-lg transition-all opacity-80 group-hover:opacity-100"
                                    title="Ver em tamanho real">
                                🔍
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-center text-sm text-gray-400 mb-3" id="enhance-style-label"></p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="button" onclick="useEnhancedImage()" 
                            class="flex-1 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 py-3 rounded-lg font-bold text-sm sm:text-base">
                        ✅ Usar Imagem
                    </button>
                    <button type="button" onclick="retryEnhance()" 
                            class="flex-1 py-2.5 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">
                        🔄 Outro Estilo
                    </button>
                    <button type="button" onclick="closeEnhanceModal()" 
                            class="py-2.5 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // =====================================================
        // MELHORIA DE IMAGEM POR IA
        // =====================================================
        const ENHANCE_EDGE_URL = 'https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1/menu-enhance-image';
        let enhanceImageBase64 = null;
        let enhanceEnvImageBase64 = null;
        let enhanceSelectedStyle = null;
        let enhancePopColor = null;
        let enhancedResultBase64 = null;
        
        function openEnhanceModal() {
            document.getElementById('enhance-modal').classList.remove('hidden');
            document.getElementById('enhance-modal').classList.add('flex');
            showEnhancePhase('upload');
            
            // Pre-fill with existing image if available
            const currentImg = document.getElementById('form-current-image').value;
            if (currentImg) {
                loadImageAsBase64(currentImg).then(b64 => {
                    if (b64) {
                        enhanceImageBase64 = b64;
                        document.getElementById('enhance-preview-img').src = currentImg;
                        document.getElementById('enhance-preview-area').classList.remove('hidden');
                        document.getElementById('enhance-upload-hint').classList.add('hidden');
                        updateEnhanceButton();
                    }
                });
            }
            
            // Pre-fill food name
            const foodName = document.getElementById('form-name').value;
            if (foodName) {
                document.getElementById('enhance-food-name').value = foodName;
            }
        }
        
        function closeEnhanceModal() {
            document.getElementById('enhance-modal').classList.add('hidden');
            document.getElementById('enhance-modal').classList.remove('flex');
            resetEnhanceState();
        }
        
        function resetEnhanceState() {
            enhanceImageBase64 = null;
            enhanceEnvImageBase64 = null;
            enhanceSelectedStyle = null;
            enhancePopColor = null;
            enhancedResultBase64 = null;
            document.getElementById('enhance-preview-area').classList.add('hidden');
            document.getElementById('enhance-upload-hint').classList.remove('hidden');
            document.getElementById('enhance-food-name').value = '';
            document.getElementById('enhance-pop-color').classList.add('hidden');
            document.getElementById('enhance-environment-upload').classList.add('hidden');
            document.getElementById('enhance-env-preview-area').classList.add('hidden');
            document.getElementById('enhance-env-upload-hint').classList.remove('hidden');
            document.querySelectorAll('.enhance-style-card').forEach(c => c.classList.remove('border-purple-500', 'bg-purple-900/30'));
            document.querySelectorAll('.enhance-style-card').forEach(c => c.classList.add('border-gray-600'));
            document.getElementById('btn-start-enhance').disabled = true;
        }
        
        function showEnhancePhase(phase) {
            ['upload', 'loading', 'result'].forEach(p => {
                document.getElementById(`enhance-phase-${p}`).classList.toggle('hidden', p !== phase);
            });
        }
        
        function onEnhanceFileSelected(input) {
            const file = input.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                enhanceImageBase64 = e.target.result;
                document.getElementById('enhance-preview-img').src = enhanceImageBase64;
                document.getElementById('enhance-preview-area').classList.remove('hidden');
                document.getElementById('enhance-upload-hint').classList.add('hidden');
                updateEnhanceButton();
            };
            reader.readAsDataURL(file);
        }
        
        // Drag and drop
        document.addEventListener('DOMContentLoaded', () => {
            const dropZone = document.getElementById('enhance-drop-zone');
            if (!dropZone) return;
            
            ['dragenter', 'dragover'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropZone.classList.add('border-purple-500', 'bg-purple-900/20');
                });
            });
            ['dragleave', 'drop'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('border-purple-500', 'bg-purple-900/20');
                });
            });
            dropZone.addEventListener('drop', (e) => {
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    document.getElementById('enhance-file-input').files = dt.files;
                    onEnhanceFileSelected(document.getElementById('enhance-file-input'));
                }
            });
        });
        
        // Environment image handler for teste_vitor
        function onEnhanceEnvFileSelected(input) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                enhanceEnvImageBase64 = e.target.result;
                document.getElementById('enhance-env-preview-img').src = enhanceEnvImageBase64;
                document.getElementById('enhance-env-preview-area').classList.remove('hidden');
                document.getElementById('enhance-env-upload-hint').classList.add('hidden');
                updateEnhanceButton();
            };
            reader.readAsDataURL(file);
        }
        
        // Drag and drop for environment zone
        document.addEventListener('DOMContentLoaded', () => {
            const envZone = document.getElementById('enhance-env-drop-zone');
            if (!envZone) return;
            ['dragenter', 'dragover'].forEach(evt => {
                envZone.addEventListener(evt, (e) => { e.preventDefault(); envZone.classList.add('border-purple-500', 'bg-purple-900/20'); });
            });
            ['dragleave', 'drop'].forEach(evt => {
                envZone.addEventListener(evt, (e) => { e.preventDefault(); envZone.classList.remove('border-purple-500', 'bg-purple-900/20'); });
            });
            envZone.addEventListener('drop', (e) => {
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    document.getElementById('enhance-env-file-input').files = dt.files;
                    onEnhanceEnvFileSelected(document.getElementById('enhance-env-file-input'));
                }
            });
        });
        
        function selectEnhanceStyle(style) {
            enhanceSelectedStyle = style;
            document.querySelectorAll('.enhance-style-card').forEach(c => {
                const isSelected = c.dataset.style === style;
                c.classList.toggle('border-purple-500', isSelected);
                c.classList.toggle('bg-purple-900/30', isSelected);
                c.classList.toggle('border-gray-600', !isSelected);
            });
            
            // Show/hide pop color picker
            document.getElementById('enhance-pop-color').classList.toggle('hidden', style !== 'pop');
            
            // Show/hide environment upload for customizavel
            document.getElementById('enhance-environment-upload').classList.toggle('hidden', style !== 'customizavel');
            
            updateEnhanceButton();
        }
        
        function selectPopColor(color) {
            enhancePopColor = color;
            document.querySelectorAll('.pop-color-btn').forEach(btn => {
                btn.classList.toggle('border-white', btn.dataset.color === color);
                btn.classList.toggle('border-gray-600', btn.dataset.color !== color);
                btn.classList.toggle('ring-2', btn.dataset.color === color);
                btn.classList.toggle('ring-white', btn.dataset.color === color);
            });
        }
        
        function updateEnhanceButton() {
            const btn = document.getElementById('btn-start-enhance');
            const needsEnv = enhanceSelectedStyle === 'customizavel';
            btn.disabled = !(enhanceImageBase64 && enhanceSelectedStyle && (!needsEnv || enhanceEnvImageBase64));
        }
        
        async function loadImageAsBase64(url) {
            try {
                const res = await fetch(url);
                const blob = await res.blob();
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = () => resolve(null);
                    reader.readAsDataURL(blob);
                });
            } catch {
                return null;
            }
        }
        
        async function startEnhance() {
            if (!enhanceImageBase64 || !enhanceSelectedStyle) return;
            
            showEnhancePhase('loading');
            
            try {
                const body = {
                    image: enhanceImageBase64,
                    style: enhanceSelectedStyle,
                    food_name: document.getElementById('enhance-food-name').value.trim() || undefined,
                };
                
                if (enhanceSelectedStyle === 'pop' && enhancePopColor) {
                    body.bg_color = enhancePopColor;
                }
                
                if (enhanceSelectedStyle === 'customizavel' && enhanceEnvImageBase64) {
                    body.image_environment = enhanceEnvImageBase64;
                    body.framing = document.getElementById('enhance-framing').value;
                    body.angle = document.getElementById('enhance-angle').value;
                    body.lighting = document.getElementById('enhance-lighting').value;
                    body.background_effect = document.getElementById('enhance-bg-effect').value;
                }
                
                const res = await fetch(ENHANCE_EDGE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                });
                
                const data = await res.json();
                
                if (!res.ok) {
                    throw new Error(data.error || 'Erro ao processar imagem');
                }
                
                enhancedResultBase64 = data.enhanced_image;
                
                // Show result
                document.getElementById('enhance-original-result').src = enhanceImageBase64;
                document.getElementById('enhance-result-img').src = enhancedResultBase64;
                document.getElementById('enhance-style-label').textContent = `Estilo: ${data.style_name}`;
                showEnhancePhase('result');
                
            } catch (err) {
                alert('Erro: ' + err.message);
                showEnhancePhase('upload');
            }
        }
        
        function useEnhancedImage() {
            if (!enhancedResultBase64) return;
            
            // Set as current image in the product form
            document.getElementById('form-current-image').value = enhancedResultBase64;
            document.getElementById('preview-img').src = enhancedResultBase64;
            document.getElementById('current-image-preview').classList.remove('hidden');
            
            // Show AI badge, hide others
            document.getElementById('stock-badge').classList.add('hidden');
            document.getElementById('upload-badge').classList.add('hidden');
            document.getElementById('ai-badge').classList.remove('hidden');
            
            // Hide the "Melhorar" button since already enhanced
            document.getElementById('btn-enhance-existing').classList.add('hidden');
            
            // Clear file input so the base64 is used
            document.getElementById('file-image-input').value = '';
            
            closeEnhanceModal();
        }
        
        function retryEnhance() {
            showEnhancePhase('upload');
        }
        
        function zoomEnhancedImage() {
            const src = document.getElementById('enhance-result-img').src;
            if (!src) return;
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;cursor:zoom-out;padding:1rem;';
            overlay.onclick = () => overlay.remove();
            const img = document.createElement('img');
            img.src = src;
            img.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain;border-radius:0.5rem;';
            overlay.appendChild(img);
            document.body.appendChild(overlay);
        }
    </script>
    
    <!-- Modal de Geração de Vídeo por IA -->
    <div id="video-ai-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center" style="z-index: 70;">
        <div class="bg-gray-800 rounded-lg max-w-lg w-full mx-4 modal-container" style="max-height: 85vh; max-height: 85dvh;">
            <!-- Fase 1: Seleção de estilo -->
            <div id="video-phase-select" class="flex flex-col flex-1 overflow-hidden">
                <div class="modal-header flex items-center justify-between">
                    <h2 class="text-lg font-bold">🎬 Gerar Vídeo com IA</h2>
                    <button type="button" onclick="closeVideoAiModal()" class="text-gray-400 hover:text-white text-xl">✕</button>
                </div>
                <div class="modal-body">
                    <p class="text-sm text-gray-400 mb-4">Selecione um estilo cinematográfico para transformar a foto do prato em um vídeo vertical (9:16) de 5 segundos.</p>
                    
                    <div id="video-no-image-warn" class="hidden bg-yellow-900/40 border border-yellow-600 rounded-lg p-3 mb-4">
                        <p class="text-sm text-yellow-300">⚠️ Adicione uma imagem ao prato primeiro para gerar o vídeo.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <div class="video-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-cyan-500 transition-all" 
                             data-style="cheese_pull" onclick="selectVideoStyle('cheese_pull')">
                            <div class="text-2xl mb-1">🧀</div>
                            <h4 class="font-bold text-sm">Cheese Pull</h4>
                            <p class="text-xs text-gray-400 mt-1">Queijo derretido se esticando em câmera lenta</p>
                        </div>
                        <div class="video-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-cyan-500 transition-all" 
                             data-style="spinning_plate" onclick="selectVideoStyle('spinning_plate')">
                            <div class="text-2xl mb-1">🍽️</div>
                            <h4 class="font-bold text-sm">Prato Girando</h4>
                            <p class="text-xs text-gray-400 mt-1">Rotação 360° dramática com iluminação de estúdio</p>
                        </div>
                        <div class="video-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-cyan-500 transition-all" 
                             data-style="macro_detail" onclick="selectVideoStyle('macro_detail')">
                            <div class="text-2xl mb-1">🔬</div>
                            <h4 class="font-bold text-sm">Macro Detail</h4>
                            <p class="text-xs text-gray-400 mt-1">Ultra close-up nas texturas e detalhes do prato</p>
                        </div>
                        <div class="video-style-card border-2 border-gray-600 rounded-lg p-3 cursor-pointer hover:border-cyan-500 transition-all" 
                             data-style="steam_heat" onclick="selectVideoStyle('steam_heat')">
                            <div class="text-2xl mb-1">♨️</div>
                            <h4 class="font-bold text-sm">Vapor & Calor</h4>
                            <p class="text-xs text-gray-400 mt-1">Vapor subindo dramaticamente em câmera lenta</p>
                        </div>
                    </div>
                    
                    <button type="button" onclick="startVideoGeneration()" id="btn-start-video"
                            class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 py-3 rounded-lg font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        🎬 Gerar Vídeo
                    </button>
                </div>
            </div>
            
            <!-- Fase 2: Progresso -->
            <div id="video-phase-progress" class="hidden flex flex-col flex-1 overflow-hidden">
                <div class="modal-header flex items-center justify-between">
                    <h2 class="text-lg font-bold">🎬 Gerando Vídeo...</h2>
                    <span class="text-xs text-gray-400" id="video-style-label"></span>
                </div>
                <div class="modal-body">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin text-5xl mb-4">🎬</div>
                        <p class="text-lg font-bold mb-2" id="video-progress-text">Iniciando geração...</p>
                        <p class="text-sm text-gray-400 mb-4">A IA está criando seu vídeo cinematográfico</p>
                        
                        <!-- Barra de progresso -->
                        <div class="w-full bg-gray-700 rounded-full h-3 mb-2 overflow-hidden">
                            <div id="video-progress-bar" class="bg-gradient-to-r from-blue-500 to-cyan-400 h-3 rounded-full transition-all duration-500" style="width: 5%"></div>
                        </div>
                        <p class="text-xs text-gray-500" id="video-progress-percent">5%</p>
                        
                        <p class="text-xs text-gray-500 mt-4">⏱️ Isso pode levar de 2 a 5 minutos</p>
                    </div>
                </div>
            </div>
            
            <!-- Fase 3: Resultado -->
            <div id="video-phase-result" class="hidden flex flex-col">
                <div class="modal-header flex items-center justify-between">
                    <h2 class="text-lg font-bold">🎬 Vídeo Pronto!</h2>
                    <button type="button" onclick="closeVideoAiModal()" class="text-gray-400 hover:text-white text-xl">✕</button>
                </div>
                <div class="modal-body">
                    <div class="mb-4 rounded-lg overflow-hidden border border-cyan-500 bg-black">
                        <video id="video-result-player" controls class="w-full" style="max-height: 400px;">
                            <source src="" type="video/mp4">
                        </video>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button type="button" onclick="useGeneratedVideo()" 
                                class="flex-1 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 py-3 rounded-lg font-bold text-sm sm:text-base">
                            ✅ Usar Vídeo
                        </button>
                        <button type="button" onclick="retryVideoGeneration()" 
                                class="flex-1 py-2.5 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">
                            🔄 Outro Estilo
                        </button>
                        <button type="button" onclick="closeVideoAiModal()" 
                                class="py-2.5 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Fase Erro -->
            <div id="video-phase-error" class="hidden flex flex-col">
                <div class="modal-header flex items-center justify-between">
                    <h2 class="text-lg font-bold">❌ Erro na Geração</h2>
                    <button type="button" onclick="closeVideoAiModal()" class="text-gray-400 hover:text-white text-xl">✕</button>
                </div>
                <div class="modal-body text-center py-8">
                    <div class="text-5xl mb-4">😔</div>
                    <p class="text-lg font-bold mb-2">Não foi possível gerar o vídeo</p>
                    <p class="text-sm text-red-400 mb-4" id="video-error-msg"></p>
                    <div class="flex gap-2 justify-center">
                        <button type="button" onclick="retryVideoGeneration()" 
                                class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium">
                            🔄 Tentar Novamente
                        </button>
                        <button type="button" onclick="closeVideoAiModal()" 
                                class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg text-sm">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // =====================================================
        // GERAÇÃO DE VÍDEO POR IA (Vertex AI Veo)
        // =====================================================
        const VIDEO_API_URL = '../api/generate-video.php';
        let videoSelectedStyle = null;
        let videoOperationName = null;
        let videoPollTimer = null;
        let videoResultUri = null;
        
        function openVideoAiModal() {
            const modal = document.getElementById('video-ai-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            showVideoPhase('select');
            videoSelectedStyle = null;
            videoOperationName = null;
            videoResultUri = null;
            
            // Check if product has image
            const currentImg = document.getElementById('form-current-image').value;
            const fileInput = document.getElementById('file-image-input');
            const hasImage = currentImg || (fileInput && fileInput.files.length > 0);
            
            document.getElementById('video-no-image-warn').classList.toggle('hidden', hasImage);
            document.getElementById('btn-start-video').disabled = true;
            
            // Reset style selection
            document.querySelectorAll('.video-style-card').forEach(c => {
                c.classList.remove('border-cyan-500', 'bg-cyan-900/20');
                c.classList.add('border-gray-600');
            });
        }
        
        function closeVideoAiModal() {
            document.getElementById('video-ai-modal').classList.add('hidden');
            document.getElementById('video-ai-modal').classList.remove('flex');
            if (videoPollTimer) {
                clearInterval(videoPollTimer);
                videoPollTimer = null;
            }
        }
        
        function showVideoPhase(phase) {
            ['select', 'progress', 'result', 'error'].forEach(p => {
                document.getElementById(`video-phase-${p}`).classList.toggle('hidden', p !== phase);
            });
        }
        
        function selectVideoStyle(style) {
            videoSelectedStyle = style;
            document.querySelectorAll('.video-style-card').forEach(c => {
                const isSelected = c.dataset.style === style;
                c.classList.toggle('border-cyan-500', isSelected);
                c.classList.toggle('bg-cyan-900/20', isSelected);
                c.classList.toggle('border-gray-600', !isSelected);
            });
            
            // Enable button if we have image
            const currentImg = document.getElementById('form-current-image').value;
            const fileInput = document.getElementById('file-image-input');
            const hasImage = currentImg || (fileInput && fileInput.files.length > 0);
            document.getElementById('btn-start-video').disabled = !hasImage;
        }
        
        async function getImageBase64ForVideo() {
            // Priority: file input > current image URL > enhanced image
            const fileInput = document.getElementById('file-image-input');
            if (fileInput && fileInput.files.length > 0) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = () => resolve(null);
                    reader.readAsDataURL(fileInput.files[0]);
                });
            }
            
            const currentImg = document.getElementById('form-current-image').value;
            if (currentImg) {
                // If already base64
                if (currentImg.startsWith('data:')) return currentImg;
                // Load from URL/path
                return await loadImageAsBase64(normalizeImageUrl(currentImg));
            }
            
            return null;
        }
        
        async function startVideoGeneration() {
            if (!videoSelectedStyle) return;
            
            const imageBase64 = await getImageBase64ForVideo();
            if (!imageBase64) {
                alert('Adicione uma imagem ao prato primeiro.');
                return;
            }
            
            const foodName = document.getElementById('form-name').value.trim();
            const styleNames = {
                cheese_pull: 'Cheese Pull',
                spinning_plate: 'Prato Girando',
                macro_detail: 'Macro Detail',
                steam_heat: 'Vapor & Calor'
            };
            
            showVideoPhase('progress');
            document.getElementById('video-style-label').textContent = styleNames[videoSelectedStyle] || '';
            updateVideoProgress(5, 'Enviando imagem...');
            
            try {
                const res = await fetch(VIDEO_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'generate',
                        image: imageBase64,
                        style: videoSelectedStyle,
                        food_name: foodName,
                    }),
                });
                
                const data = await res.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Erro ao iniciar geração');
                }
                
                videoOperationName = data.operation_name;
                updateVideoProgress(10, 'Geração iniciada. Processando...');
                
                // Start polling
                let pollCount = 0;
                videoPollTimer = setInterval(async () => {
                    pollCount++;
                    try {
                        const pollRes = await fetch(VIDEO_API_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'poll',
                                operation_name: videoOperationName,
                            }),
                        });
                        
                        const pollData = await pollRes.json();
                        
                        if (pollData.done) {
                            clearInterval(videoPollTimer);
                            videoPollTimer = null;
                            
                            if (pollData.error || !pollData.success) {
                                showVideoError(pollData.error || 'Erro desconhecido na geração');
                                return;
                            }
                            
                            videoResultUri = pollData.video_uri;
                            updateVideoProgress(100, 'Vídeo pronto!');
                            
                            // Show result
                            setTimeout(() => {
                                const player = document.getElementById('video-result-player');
                                player.querySelector('source').src = videoResultUri;
                                player.load();
                                showVideoPhase('result');
                            }, 500);
                            return;
                        }
                        
                        // Update progress
                        const progress = pollData.progress || Math.min(10 + pollCount * 3, 90);
                        const stateMessages = {
                            'RUNNING': 'Processando vídeo...',
                            'QUEUED': 'Na fila de processamento...',
                        };
                        const msg = stateMessages[pollData.state] || 'Gerando vídeo...';
                        updateVideoProgress(progress, msg);
                        
                    } catch (err) {
                        console.error('Poll error:', err);
                        // Don't stop polling on transient errors
                        if (pollCount > 60) { // ~5 min timeout
                            clearInterval(videoPollTimer);
                            videoPollTimer = null;
                            showVideoError('Tempo limite excedido. Tente novamente.');
                        }
                    }
                }, 5000); // Poll every 5 seconds
                
            } catch (err) {
                showVideoError(err.message);
            }
        }
        
        function updateVideoProgress(percent, text) {
            document.getElementById('video-progress-bar').style.width = percent + '%';
            document.getElementById('video-progress-percent').textContent = percent + '%';
            if (text) {
                document.getElementById('video-progress-text').textContent = text;
            }
        }
        
        function showVideoError(msg) {
            document.getElementById('video-error-msg').textContent = msg;
            showVideoPhase('error');
        }
        
        async function useGeneratedVideo() {
            if (!videoResultUri) return;
            
            // Download and save via API
            const productId = document.getElementById('form-id').value || 0;
            
            try {
                const res = await fetch(VIDEO_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'download',
                        video_uri: videoResultUri,
                        product_id: parseInt(productId),
                    }),
                });
                
                const data = await res.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Erro ao salvar vídeo');
                }
                
                // Update form
                document.getElementById('form-current-video').value = data.video_url;
                
                // Update video preview
                const videoPreview = document.getElementById('current-video-preview');
                const previewVideo = document.getElementById('preview-video');
                if (videoPreview && previewVideo) {
                    previewVideo.querySelector('source').src = data.video_url;
                    previewVideo.load();
                    videoPreview.classList.remove('hidden');
                }
                
                closeVideoAiModal();
                
            } catch (err) {
                alert('Erro ao salvar: ' + err.message);
            }
        }
        
        function retryVideoGeneration() {
            if (videoPollTimer) {
                clearInterval(videoPollTimer);
                videoPollTimer = null;
            }
            videoOperationName = null;
            videoResultUri = null;
            showVideoPhase('select');
        }
    </script>
</body>
</html>
