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
                $maxProducts = (int)($_POST['max_products'] ?? 50);
                $maxCategories = (int)($_POST['max_categories'] ?? 10);
                $isPopular = isset($_POST['is_popular']) ? 1 : 0;
                
                // Features como JSON
                $featuresText = trim($_POST['features'] ?? '');
                $featuresArray = array_filter(array_map('trim', explode("\n", $featuresText)));
                $featuresJson = json_encode($featuresArray, JSON_UNESCAPED_UNICODE);
                
                if (empty($name)) {
                    throw new Exception('Nome do plano é obrigatório.');
                }
                
                $sql = "INSERT INTO plans (name, slug, price, max_products, max_categories, features, is_popular, is_active) 
                        VALUES (:name, :slug, :price, :mp, :mc, :features, :popular, 1)";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'price' => $price,
                    'mp' => $maxProducts,
                    'mc' => $maxCategories,
                    'features' => $featuresJson,
                    'popular' => $isPopular
                ]);
                
                $message = 'Plano criado com sucesso!';
                $messageType = 'success';
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $name = sanitize($_POST['name'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $maxProducts = (int)($_POST['max_products'] ?? 50);
                $maxCategories = (int)($_POST['max_categories'] ?? 10);
                $isPopular = isset($_POST['is_popular']) ? 1 : 0;
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                // Features como JSON
                $featuresText = trim($_POST['features'] ?? '');
                $featuresArray = array_filter(array_map('trim', explode("\n", $featuresText)));
                $featuresJson = json_encode($featuresArray, JSON_UNESCAPED_UNICODE);
                
                $sql = "UPDATE plans SET 
                        name = :name, 
                        price = :price, 
                        max_products = :mp, 
                        max_categories = :mc, 
                        features = :features,
                        is_popular = :popular,
                        is_active = :active 
                        WHERE id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'price' => $price,
                    'mp' => $maxProducts,
                    'mc' => $maxCategories,
                    'features' => $featuresJson,
                    'popular' => $isPopular,
                    'active' => $isActive,
                    'id' => $id
                ]);
                
                $message = 'Plano atualizado!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                
                // Verificar se há restaurantes usando este plano
                $checkStmt = db()->prepare("SELECT COUNT(*) FROM restaurants WHERE plan_id = :id");
                $checkStmt->execute(['id' => $id]);
                $count = $checkStmt->fetchColumn();
                
                if ($count > 0) {
                    throw new Exception("Não é possível excluir: {$count} restaurante(s) usam este plano.");
                }
                
                $stmt = db()->prepare("DELETE FROM plans WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                $message = 'Plano excluído!';
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
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <input type="text" name="name" placeholder="Nome *" required class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                    <input type="number" name="price" placeholder="Preço *" step="0.01" required class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                    <input type="number" name="max_products" placeholder="Max Pratos" value="50" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                    <input type="number" name="max_categories" placeholder="Max Categorias" value="10" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_popular"> Popular
                        </label>
                    </div>
                </div>
                <div>
                    <label class="text-sm text-gray-400 block mb-1">Recursos (um por linha)</label>
                    <textarea name="features" rows="3" placeholder="Recurso 1&#10;Recurso 2&#10;Recurso 3" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2"></textarea>
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 rounded-lg px-6 py-2 font-medium transition">
                    + Criar Plano
                </button>
            </form>
        </div>
        
        <!-- Lista de Planos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($plans as $plan): 
                $features = json_decode($plan['features'] ?? '[]', true) ?: [];
            ?>
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden relative <?= $plan['is_popular'] ? 'ring-2 ring-purple-500' : '' ?>">
                    <?php if ($plan['is_popular']): ?>
                        <div class="absolute -top-0 left-1/2 -translate-x-1/2 bg-purple-600 text-white text-xs px-3 py-1 rounded-b-lg">
                            Mais Popular
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-6 pt-8">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold"><?= htmlspecialchars($plan['name']) ?></h3>
                                <p class="text-2xl font-bold text-green-400 mt-1">
                                    R$ <?= number_format($plan['price'], 2, ',', '.') ?>
                                    <span class="text-sm text-gray-400 font-normal">/mês</span>
                                </p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded <?= $plan['is_active'] ? 'bg-green-900 text-green-400' : 'bg-gray-700 text-gray-400' ?>">
                                <?= $plan['is_active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                        
                        <div class="text-sm text-gray-400 mb-4">
                            <p><?= $plan['max_products'] == -1 ? '∞' : $plan['max_products'] ?> pratos</p>
                            <p><?= $plan['max_categories'] == -1 ? '∞' : $plan['max_categories'] ?> categorias</p>
                        </div>
                        
                        <?php if (!empty($features)): ?>
                            <ul class="text-sm space-y-1 mb-4">
                                <?php foreach ($features as $feature): ?>
                                    <li class="flex items-center gap-2">
                                        <span class="text-green-400">✓</span>
                                        <?= htmlspecialchars($feature) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <div class="flex gap-2 mt-4 pt-4 border-t border-gray-700">
                            <button onclick="editPlan(<?= htmlspecialchars(json_encode($plan)) ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 rounded-lg px-4 py-2 text-sm transition">
                                Editar
                            </button>
                            <button onclick="deletePlan(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['name']) ?>')" class="bg-red-600 hover:bg-red-700 rounded-lg px-4 py-2 text-sm transition">
                                Excluir
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    
    <!-- Modal Editar -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <h3 class="font-bold text-lg mb-4">Editar Plano</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div>
                    <label class="text-sm text-gray-400">Nome *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                </div>
                
                <div>
                    <label class="text-sm text-gray-400">Preço (R$) *</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-400">Max Pratos (-1 = ∞)</label>
                        <input type="number" name="max_products" id="edit_max_products" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Max Categorias (-1 = ∞)</label>
                        <input type="number" name="max_categories" id="edit_max_categories" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                    </div>
                </div>
                
                <div>
                    <label class="text-sm text-gray-400">Recursos (um por linha)</label>
                    <textarea name="features" id="edit_features" rows="4" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1"></textarea>
                </div>
                
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_popular" id="edit_is_popular"> Popular
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="edit_is_active"> Ativo
                    </label>
                </div>
                
                <div class="flex gap-4 pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 rounded-lg px-4 py-2 transition">Salvar</button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-700 hover:bg-gray-600 rounded-lg px-4 py-2 transition">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Form hidden para delete -->
    <form id="deleteForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>
    
    <script>
        function editPlan(plan) {
            document.getElementById('edit_id').value = plan.id;
            document.getElementById('edit_name').value = plan.name;
            document.getElementById('edit_price').value = plan.price;
            document.getElementById('edit_max_products').value = plan.max_products;
            document.getElementById('edit_max_categories').value = plan.max_categories;
            document.getElementById('edit_is_popular').checked = plan.is_popular == 1;
            document.getElementById('edit_is_active').checked = plan.is_active == 1;
            
            // Parse features JSON
            let features = [];
            try {
                features = JSON.parse(plan.features || '[]');
            } catch(e) {}
            document.getElementById('edit_features').value = features.join('\n');
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
        
        function deletePlan(id, name) {
            if (confirm('Tem certeza que deseja excluir o plano "' + name + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
