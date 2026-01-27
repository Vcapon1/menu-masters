<?php
/**
 * CARDÁPIO FLORIPA - Gerenciar Categorias
 * 
 * CRUD de categorias do restaurante.
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

if (!$restaurant) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $name = sanitize($_POST['name'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                
                if (empty($name)) {
                    throw new Exception('Nome da categoria é obrigatório.');
                }
                
                // Verificar limite do plano
                $categories = getCategories($restaurantId);
                $maxCategories = $restaurant['max_categories'];
                if ($maxCategories !== -1 && count($categories) >= $maxCategories) {
                    throw new Exception('Limite de categorias atingido. Faça upgrade do plano.');
                }
                
                $sql = "INSERT INTO categories (restaurant_id, name, description, sort_order) VALUES (:rid, :name, :desc, :sort)";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'rid' => $restaurantId,
                    'name' => $name,
                    'desc' => $description,
                    'sort' => $sortOrder
                ]);
                
                $message = 'Categoria criada com sucesso!';
                $messageType = 'success';
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $name = sanitize($_POST['name'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($name)) {
                    throw new Exception('Nome da categoria é obrigatório.');
                }
                
                $sql = "UPDATE categories SET name = :name, description = :desc, sort_order = :sort, is_active = :active WHERE id = :id AND restaurant_id = :rid";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'desc' => $description,
                    'sort' => $sortOrder,
                    'active' => $isActive,
                    'id' => $id,
                    'rid' => $restaurantId
                ]);
                
                $message = 'Categoria atualizada!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                
                // Verificar se tem produtos
                $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute(['id' => $id]);
                $result = $stmt->fetch();
                
                if ($result['count'] > 0) {
                    throw new Exception('Não é possível excluir categoria com produtos. Mova ou exclua os produtos primeiro.');
                }
                
                $sql = "DELETE FROM categories WHERE id = :id AND restaurant_id = :rid";
                $stmt = db()->prepare($sql);
                $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
                
                $message = 'Categoria excluída!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar categorias
$categories = getCategories($restaurantId);
$maxCategories = $restaurant['max_categories'];
$canAddCategories = $maxCategories === -1 || count($categories) < $maxCategories;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-gray-400 hover:text-white">← Dashboard</a>
                <h1 class="font-bold">Categorias</h1>
            </div>
            <span class="text-sm text-gray-400">
                <?= count($categories) ?><?= $maxCategories !== -1 ? "/$maxCategories" : '' ?> categorias
            </span>
        </div>
    </nav>
    
    <main class="max-w-4xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-900/50 border-green-500 text-green-400' : 'bg-red-900/50 border-red-500 text-red-400' ?> border">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Nova Categoria -->
        <?php if ($canAddCategories): ?>
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
            <h2 class="font-bold mb-4">Nova Categoria</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="create">
                <input 
                    type="text" 
                    name="name" 
                    placeholder="Nome da categoria" 
                    required
                    class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
                >
                <input 
                    type="text" 
                    name="description" 
                    placeholder="Descrição (opcional)"
                    class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
                >
                <button type="submit" class="bg-green-600 hover:bg-green-700 rounded-lg px-4 py-2 font-medium transition">
                    + Adicionar
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-yellow-900/50 border border-yellow-600 rounded-lg p-4 mb-8">
            <p class="text-yellow-400">⚠️ Limite de categorias atingido. <a href="upgrade.php" class="underline">Faça upgrade</a> para adicionar mais.</p>
        </div>
        <?php endif; ?>
        
        <!-- Lista de Categorias -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="divide-y divide-gray-700">
                <?php if (empty($categories)): ?>
                    <div class="p-8 text-center text-gray-400">
                        Nenhuma categoria cadastrada.
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <form method="POST" class="p-4 flex items-center gap-4">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $category['id'] ?>">
                            
                            <input 
                                type="number" 
                                name="sort_order" 
                                value="<?= $category['sort_order'] ?>"
                                class="w-16 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-center text-sm"
                                title="Ordem"
                            >
                            
                            <input 
                                type="text" 
                                name="name" 
                                value="<?= htmlspecialchars($category['name']) ?>"
                                required
                                class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-2"
                            >
                            
                            <input 
                                type="text" 
                                name="description" 
                                value="<?= htmlspecialchars($category['description'] ?? '') ?>"
                                placeholder="Descrição"
                                class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-2"
                            >
                            
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" <?= $category['is_active'] ? 'checked' : '' ?>>
                                Ativo
                            </label>
                            
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 rounded px-4 py-2 text-sm transition">
                                Salvar
                            </button>
                        </form>
                        
                        <form method="POST" class="px-4 pb-4" onsubmit="return confirm('Excluir esta categoria?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $category['id'] ?>">
                            <button type="submit" class="text-sm text-red-400 hover:text-red-300">
                                Excluir categoria
                            </button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
