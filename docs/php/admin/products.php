<?php
/**
 * PREMIUM MENU - Gerenciamento de Pratos
 * 
 * CRUD completo para pratos/produtos do restaurante.
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
                        // Quando tem tamanhos, o preço principal é o menor dos tamanhos
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
                    $sql = "INSERT INTO products (restaurant_id, category_id, name, description, price, promo_price, image, video, badges, is_available, hide_when_unavailable, sort_order)
                            VALUES (:restaurant_id, :category_id, :name, :description, :price, :promo_price, :image, :video, :badges, :is_available, :hide_when_unavailable, :sort_order)";
                    $params = [
                        'restaurant_id' => $restaurantId,
                        'category_id' => $categoryId,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'promo_price' => $promoPrice,
                        'image' => $image,
                        'video' => $video,
                        'badges' => $badges,
                        'is_available' => $isAvailable,
                        'hide_when_unavailable' => $hideWhenUnavailable,
                        'sort_order' => $sortOrder,
                    ];
                    $message = 'Prato criado com sucesso!';
                } else {
                    $sql = "UPDATE products SET category_id = :category_id, name = :name, description = :description, 
                            price = :price, promo_price = :promo_price, image = :image, video = :video, badges = :badges, 
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
                        'image' => $image,
                        'video' => $video,
                        'badges' => $badges,
                        'is_available' => $isAvailable,
                        'hide_when_unavailable' => $hideWhenUnavailable,
                        'sort_order' => $sortOrder,
                    ];
                    $message = 'Prato atualizado com sucesso!';
                }
                
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
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
    </style>
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg max-w-lg w-full mx-4 modal-container">
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
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm mb-1">Preço *</label>
                                <input type="number" name="price" id="form-price" step="0.01" min="0" required
                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Preço Promocional</label>
                                <input type="number" name="promo_price" id="form-promo-price" step="0.01" min="0"
                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Imagem</label>
                            <div id="current-image-preview" class="mb-2 hidden flex items-center gap-2">
                                <img id="preview-img" src="" class="w-16 h-16 rounded object-cover border border-gray-600">
                                <span class="text-xs text-gray-400">Imagem atual</span>
                            </div>
                            <input type="file" name="image" accept="image/*"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
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
    
    <script>
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
            
            // Esconder previews
            document.getElementById('current-image-preview').classList.add('hidden');
            const videoPreview = document.getElementById('current-video-preview');
            if (videoPreview) videoPreview.classList.add('hidden');
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
            document.getElementById('form-price').value = product.price;
            document.getElementById('form-promo-price').value = product.promo_price || '';
            document.getElementById('form-available').checked = product.is_available == 1;
            document.getElementById('form-hide').checked = product.hide_when_unavailable == 1;
            document.getElementById('form-current-image').value = product.image || '';
            document.getElementById('form-current-video').value = product.video || '';
            document.getElementById('form-sort').value = product.sort_order || 0;
            
            // Preview de imagem atual
            const imagePreview = document.getElementById('current-image-preview');
            const previewImg = document.getElementById('preview-img');
            if (product.image) {
                previewImg.src = product.image;
                imagePreview.classList.remove('hidden');
            } else {
                imagePreview.classList.add('hidden');
            }
            
            // Preview de vídeo atual
            const videoPreview = document.getElementById('current-video-preview');
            const previewVideo = document.getElementById('preview-video');
            if (videoPreview && product.video) {
                previewVideo.querySelector('source').src = product.video;
                previewVideo.load();
                videoPreview.classList.remove('hidden');
            } else if (videoPreview) {
                videoPreview.classList.add('hidden');
            }
            
            // Marcar badges
            const badges = JSON.parse(product.badges || '[]');
            document.querySelectorAll('.badge-checkbox').forEach(cb => {
                cb.checked = badges.includes(cb.value);
            });
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
    </script>
</body>
</html>
