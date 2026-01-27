<?php
/**
 * CARDÁPIO FLORIPA - Gerenciar Planos
 * 
 * CRUD de planos do sistema.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['master_admin'])) {
    header('Location: login.php');
    exit;
}

$admin = $_SESSION['master_admin'];

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $name = sanitize($_POST['name'] ?? '');
                $slug = generateSlug($name);
                $price = (float)($_POST['price'] ?? 0);
                $maxProducts = (int)($_POST['max_products'] ?? 10);
                $maxCategories = (int)($_POST['max_categories'] ?? 5);
                $supportsVideo = isset($_POST['supports_video']) ? 1 : 0;
                $supportsPromo = isset($_POST['supports_promo_price']) ? 1 : 0;
                
                if (empty($name)) {
                    throw new Exception('Nome do plano é obrigatório.');
                }
                
                $sql = "INSERT INTO plans (name, slug, price, max_products, max_categories, supports_video, supports_promo_price) VALUES (:name, :slug, :price, :mp, :mc, :sv, :sp)";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'price' => $price,
                    'mp' => $maxProducts,
                    'mc' => $maxCategories,
                    'sv' => $supportsVideo,
                    'sp' => $supportsPromo
                ]);
                
                $message = 'Plano criado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $name = sanitize($_POST['name'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $maxProducts = (int)($_POST['max_products'] ?? 10);
                $maxCategories = (int)($_POST['max_categories'] ?? 5);
                $supportsVideo = isset($_POST['supports_video']) ? 1 : 0;
                $supportsPromo = isset($_POST['supports_promo_price']) ? 1 : 0;
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE plans SET name = :name, price = :price, max_products = :mp, max_categories = :mc, supports_video = :sv, supports_promo_price = :sp, is_active = :active WHERE id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'price' => $price,
                    'mp' => $maxProducts,
                    'mc' => $maxCategories,
                    'sv' => $supportsVideo,
                    'sp' => $supportsPromo,
                    'active' => $isActive,
                    'id' => $id
                ]);
                
                $message = 'Plano atualizado!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar planos
$stmt = db()->query("SELECT * FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos - Master Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <h1 class="font-bold text-lg text-orange-400"><?= APP_NAME ?></h1>
                <div class="flex gap-4 text-sm">
                    <a href="index.php" class="text-gray-400 hover:text-white">Dashboard</a>
                    <a href="restaurants.php" class="text-gray-400 hover:text-white">Restaurantes</a>
                    <a href="plans.php" class="text-white">Planos</a>
                    <a href="templates.php" class="text-gray-400 hover:text-white">Templates</a>
                    <a href="directory.php" class="text-gray-400 hover:text-white">Diretório</a>
                </div>
            </div>
            <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
        </div>
    </nav>
    
    <main class="max-w-6xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Gerenciar Planos</h2>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-900/50 border-green-500 text-green-400' : 'bg-red-900/50 border-red-500 text-red-400' ?> border">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Novo Plano -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
            <h3 class="font-bold mb-4">Novo Plano</h3>
            <form method="POST" class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Nome" required class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                <input type="number" name="price" placeholder="Preço" step="0.01" required class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                <input type="number" name="max_products" placeholder="Max Pratos" value="10" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                <input type="number" name="max_categories" placeholder="Max Categorias" value="5" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="supports_video"> Vídeo
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="supports_promo_price"> Promo
                    </label>
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 rounded-lg px-4 py-2 font-medium transition">
                    + Criar
                </button>
            </form>
        </div>
        
        <!-- Lista de Planos -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Plano</th>
                        <th class="text-left px-4 py-3">Preço</th>
                        <th class="text-left px-4 py-3">Limites</th>
                        <th class="text-left px-4 py-3">Recursos</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars($plan['name']) ?></td>
                            <td class="px-4 py-3">R$ <?= number_format($plan['price'], 2, ',', '.') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-400">
                                <?= $plan['max_products'] === -1 ? '∞' : $plan['max_products'] ?> pratos,
                                <?= $plan['max_categories'] === -1 ? '∞' : $plan['max_categories'] ?> categorias
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($plan['supports_video']): ?><span class="text-xs bg-blue-900 text-blue-400 px-2 py-1 rounded mr-1">Vídeo</span><?php endif; ?>
                                <?php if ($plan['supports_promo_price']): ?><span class="text-xs bg-purple-900 text-purple-400 px-2 py-1 rounded">Promo</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded <?= $plan['is_active'] ? 'bg-green-900 text-green-400' : 'bg-gray-700 text-gray-400' ?>">
                                    <?= $plan['is_active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button onclick="editPlan(<?= htmlspecialchars(json_encode($plan)) ?>)" class="text-sm text-blue-400 hover:text-blue-300">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <!-- Modal Editar -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 w-full max-w-md">
            <h3 class="font-bold mb-4">Editar Plano</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <input type="text" name="name" id="edit_name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                <input type="number" name="price" id="edit_price" step="0.01" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" name="max_products" id="edit_max_products" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                    <input type="number" name="max_categories" id="edit_max_categories" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="supports_video" id="edit_supports_video"> Vídeo
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="supports_promo_price" id="edit_supports_promo"> Promo
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="edit_is_active"> Ativo
                    </label>
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 rounded-lg px-4 py-2">Salvar</button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-700 hover:bg-gray-600 rounded-lg px-4 py-2">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editPlan(plan) {
            document.getElementById('edit_id').value = plan.id;
            document.getElementById('edit_name').value = plan.name;
            document.getElementById('edit_price').value = plan.price;
            document.getElementById('edit_max_products').value = plan.max_products;
            document.getElementById('edit_max_categories').value = plan.max_categories;
            document.getElementById('edit_supports_video').checked = plan.supports_video == 1;
            document.getElementById('edit_supports_promo').checked = plan.supports_promo_price == 1;
            document.getElementById('edit_is_active').checked = plan.is_active == 1;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
    </script>
</body>
</html>
