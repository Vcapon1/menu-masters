<?php
/**
 * PREMIUM MENU - Master Admin: Banco de Imagens
 * 
 * CRUD para gerenciar imagens compartilhadas do sistema.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['master_admin'])) {
    header('Location: login.php');
    exit;
}

$admin = $_SESSION['master_admin'];
$message = '';
$error = '';

// Categorias padrão
$defaultCategories = ['bebidas', 'sobremesas', 'acompanhamentos', 'carnes', 'massas', 'saladas', 'diversos'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $name = sanitize($_POST['name'] ?? '');
                $category = sanitize($_POST['category'] ?? '');
                $tags = sanitize($_POST['tags'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                
                if (empty($name) || empty($category)) {
                    throw new Exception('Nome e categoria são obrigatórios.');
                }
                
                // Upload de imagem obrigatório
                if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Imagem é obrigatória.');
                }
                
                $imageUrl = uploadImage($_FILES['image'], "stock-images/{$category}");
                // Extrair caminho relativo: "category/filename.ext"
                $filename = str_replace(UPLOAD_URL . 'stock-images/', '', $imageUrl);
                
                // Upload de vídeo opcional
                $videoFilename = null;
                if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    $videoUrl = uploadVideo($_FILES['video'], "stock-images/videos");
                    $videoFilename = str_replace(UPLOAD_URL . 'stock-images/videos/', '', $videoUrl);
                }
                
                $sql = "INSERT INTO stock_images (category, name, filename, video_filename, tags, sort_order) 
                        VALUES (:category, :name, :filename, :video, :tags, :sort)";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'category' => $category,
                    'name' => $name,
                    'filename' => $filename,
                    'video' => $videoFilename,
                    'tags' => $tags,
                    'sort' => $sortOrder,
                ]);
                $message = 'Imagem adicionada ao banco!';
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $name = sanitize($_POST['name'] ?? '');
                $category = sanitize($_POST['category'] ?? '');
                $tags = sanitize($_POST['tags'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($name) || empty($category)) {
                    throw new Exception('Nome e categoria são obrigatórios.');
                }
                
                // Buscar dados atuais
                $current = db()->prepare("SELECT * FROM stock_images WHERE id = :id");
                $current->execute(['id' => $id]);
                $currentImg = $current->fetch();
                if (!$currentImg) throw new Exception('Imagem não encontrada.');
                
                $filename = $currentImg['filename'];
                $videoFilename = $currentImg['video_filename'];
                
                // Atualizar imagem se enviada
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imageUrl = uploadImage($_FILES['image'], "stock-images/{$category}");
                    $filename = str_replace(UPLOAD_URL . 'stock-images/', '', $imageUrl);
                }
                
                // Atualizar vídeo se enviado
                if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    $videoUrl = uploadVideo($_FILES['video'], "stock-images/videos");
                    $videoFilename = str_replace(UPLOAD_URL . 'stock-images/videos/', '', $videoUrl);
                }
                
                // Remover vídeo se solicitado
                if (isset($_POST['remove_video']) && $_POST['remove_video'] === '1') {
                    $videoFilename = null;
                }
                
                $sql = "UPDATE stock_images SET category = :category, name = :name, filename = :filename, 
                        video_filename = :video, tags = :tags, sort_order = :sort, is_active = :active 
                        WHERE id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'category' => $category,
                    'name' => $name,
                    'filename' => $filename,
                    'video' => $videoFilename,
                    'tags' => $tags,
                    'sort' => $sortOrder,
                    'active' => $isActive,
                    'id' => $id,
                ]);
                $message = 'Imagem atualizada!';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $stmt = db()->prepare("DELETE FROM stock_images WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $message = 'Imagem removida do banco!';
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Filtros
$filterCategory = $_GET['category'] ?? '';
$filterSearch = $_GET['search'] ?? '';

$images = getStockImages($filterCategory ?: null, $filterSearch ?: null, true);
$categories = getStockCategories();

$stockBaseUrl = UPLOAD_URL . 'stock-images/';
$videoBaseUrl = UPLOAD_URL . 'stock-images/videos/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Imagens - Master Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .modal-container { display: flex; flex-direction: column; max-height: 90vh; }
        .modal-header { flex-shrink: 0; padding: 1.25rem; border-bottom: 1px solid #374151; }
        .modal-body { flex: 1; overflow-y: auto; padding: 1.5rem; }
        .modal-footer { flex-shrink: 0; padding: 1rem 1.5rem; border-top: 1px solid #374151; }
        .stock-card { transition: all 0.2s; }
        .stock-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .video-badge { position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.7); padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; }
        .inactive-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-gray-400 hover:text-white">← Voltar</a>
                <h1 class="font-bold">📸 Banco de Imagens</h1>
                <span class="text-sm text-gray-400"><?= count($images) ?> imagens</span>
            </div>
            <button onclick="openModal()" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm font-medium">
                + Nova Imagem
            </button>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="bg-green-900/50 border border-green-600 rounded-lg p-4 mb-6"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="stock-images.php" class="px-3 py-1.5 rounded-full text-sm <?= empty($filterCategory) ? 'bg-blue-600' : 'bg-gray-700 hover:bg-gray-600' ?>">
                Todas
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="stock-images.php?category=<?= urlencode($cat) ?>" 
                   class="px-3 py-1.5 rounded-full text-sm capitalize <?= $filterCategory === $cat ? 'bg-blue-600' : 'bg-gray-700 hover:bg-gray-600' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Grid de Imagens -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php foreach ($images as $img): ?>
                <div class="stock-card bg-gray-800 rounded-lg border border-gray-700 overflow-hidden relative">
                    <div class="relative aspect-w-4 aspect-h-3">
                        <img src="<?= htmlspecialchars($stockBaseUrl . $img['filename']) ?>" 
                             alt="<?= htmlspecialchars($img['name']) ?>"
                             class="w-full h-32 object-cover" loading="lazy">
                        <?php if ($img['video_filename']): ?>
                            <span class="video-badge text-green-400">🎬 Vídeo</span>
                        <?php endif; ?>
                        <?php if (!$img['is_active']): ?>
                            <div class="inactive-overlay">
                                <span class="text-xs bg-red-600 px-2 py-1 rounded">Inativo</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <p class="font-medium text-sm truncate"><?= htmlspecialchars($img['name']) ?></p>
                        <p class="text-xs text-gray-400 capitalize"><?= htmlspecialchars($img['category']) ?></p>
                        <?php if ($img['tags']): ?>
                            <p class="text-xs text-gray-500 truncate mt-1"><?= htmlspecialchars($img['tags']) ?></p>
                        <?php endif; ?>
                        <div class="flex gap-2 mt-2">
                            <button onclick='editImage(<?= json_encode($img) ?>)' class="text-xs text-blue-400 hover:text-blue-300">Editar</button>
                            <form method="post" class="inline" onsubmit="return confirm('Remover esta imagem do banco?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $img['id'] ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-300">Excluir</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($images)): ?>
            <div class="text-center py-12 text-gray-400">
                <p class="text-4xl mb-3">📸</p>
                <p>Nenhuma imagem no banco ainda.</p>
                <button onclick="openModal()" class="mt-3 text-green-400 hover:text-green-300 text-sm">+ Adicionar primeira imagem</button>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg max-w-lg w-full mx-4 modal-container">
            <div class="modal-header">
                <h2 id="modal-title" class="text-xl font-bold">Nova Imagem</h2>
            </div>
            <form method="post" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                <div class="modal-body">
                    <input type="hidden" name="action" id="form-action" value="create">
                    <input type="hidden" name="id" id="form-id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Nome *</label>
                            <input type="text" name="name" id="form-name" required placeholder="Ex: Coca-Cola 350ml"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Categoria *</label>
                            <select name="category" id="form-category" required
                                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                                <option value="">Selecione...</option>
                                <?php foreach ($defaultCategories as $cat): ?>
                                    <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Tags (palavras-chave)</label>
                            <input type="text" name="tags" id="form-tags" placeholder="refrigerante, cola, soda"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                            <p class="text-xs text-gray-500 mt-1">Separadas por vírgula. Ajudam na busca.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Imagem *</label>
                            <div id="preview-container" class="mb-2 hidden">
                                <img id="preview-img" src="" class="w-24 h-24 rounded object-cover border border-gray-600">
                            </div>
                            <input type="file" name="image" id="form-image" accept="image/*"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                            <p class="text-xs text-gray-500 mt-1">Recomendado: 600x400px, WebP.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Vídeo (opcional)</label>
                            <div id="video-preview-container" class="mb-2 hidden flex items-center gap-2">
                                <span class="text-green-400">🎬</span>
                                <span id="video-preview-name" class="text-xs text-gray-400">Vídeo atual</span>
                                <button type="button" onclick="removeVideo()" class="text-xs text-red-400 hover:text-red-300 ml-2">Remover</button>
                            </div>
                            <input type="hidden" name="remove_video" id="form-remove-video" value="0">
                            <input type="file" name="video" accept="video/mp4,video/webm"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Ordem</label>
                            <input type="number" name="sort_order" id="form-sort" value="0"
                                   class="w-24 bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div id="active-field" class="hidden">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" id="form-active" checked>
                                <span class="text-sm">Ativo</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer flex gap-2">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 py-2 rounded font-medium">Salvar</button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal').classList.add('flex');
            document.getElementById('modal-title').textContent = 'Nova Imagem';
            document.getElementById('form-action').value = 'create';
            document.getElementById('form-id').value = '';
            document.getElementById('form-name').value = '';
            document.getElementById('form-category').value = '';
            document.getElementById('form-tags').value = '';
            document.getElementById('form-sort').value = '0';
            document.getElementById('form-image').required = true;
            document.getElementById('preview-container').classList.add('hidden');
            document.getElementById('video-preview-container').classList.add('hidden');
            document.getElementById('form-remove-video').value = '0';
            document.getElementById('active-field').classList.add('hidden');
        }
        
        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('modal').classList.remove('flex');
        }
        
        function editImage(img) {
            openModal();
            document.getElementById('modal-title').textContent = 'Editar Imagem';
            document.getElementById('form-action').value = 'update';
            document.getElementById('form-id').value = img.id;
            document.getElementById('form-name').value = img.name;
            document.getElementById('form-category').value = img.category;
            document.getElementById('form-tags').value = img.tags || '';
            document.getElementById('form-sort').value = img.sort_order || 0;
            document.getElementById('form-image').required = false;
            
            // Preview da imagem
            const stockBase = '<?= $stockBaseUrl ?>';
            document.getElementById('preview-img').src = stockBase + img.filename;
            document.getElementById('preview-container').classList.remove('hidden');
            
            // Vídeo
            if (img.video_filename) {
                document.getElementById('video-preview-name').textContent = img.video_filename;
                document.getElementById('video-preview-container').classList.remove('hidden');
            }
            
            // Ativo
            document.getElementById('active-field').classList.remove('hidden');
            document.getElementById('form-active').checked = img.is_active == 1;
        }
        
        function removeVideo() {
            document.getElementById('form-remove-video').value = '1';
            document.getElementById('video-preview-container').classList.add('hidden');
        }
    </script>
</body>
</html>
