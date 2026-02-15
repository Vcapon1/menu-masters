<?php
/**
 * CARDÁPIO FLORIPA - Gerenciar Templates
 * 
 * CRUD de templates do sistema.
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
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $name = sanitize($_POST['name'] ?? '');
                $minPlanId = (int)($_POST['min_plan_id'] ?? 1);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE templates SET name = :name, min_plan_id = :mp, is_active = :active WHERE id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'mp' => $minPlanId,
                    'active' => $isActive,
                    'id' => $id
                ]);
                
                $message = 'Template atualizado!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar templates
$stmt = db()->query("SELECT t.*, p.name as min_plan_name FROM templates t JOIN plans p ON t.min_plan_id = p.id ORDER BY t.id ASC");
$templates = $stmt->fetchAll();

// Buscar planos para o select
$plans = getPlans();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates - Master Admin</title>
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
                    <a href="plans.php" class="text-gray-400 hover:text-white">Planos</a>
                    <a href="templates.php" class="text-white">Templates</a>
                    <a href="directory.php" class="text-gray-400 hover:text-white">Diretório</a>
                </div>
            </div>
            <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
        </div>
    </nav>
    
    <main class="max-w-6xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Gerenciar Templates</h2>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-900/50 border-green-500 text-green-400' : 'bg-red-900/50 border-red-500 text-red-400' ?> border">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Lista de Templates -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($templates as $template): ?>
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <!-- Preview -->
                    <div class="h-40 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
                        <span class="text-4xl">
                            <?php
                            $icons = [
                                'classic' => '📋',
                                'bold' => '🔥',
                                'appetite' => '🍽️',
                                'hero' => '🍔',
                                'visual' => '📷',
                                'modern' => '✨',
                                'elegant' => '👔',
                                'minimal' => '⚪',
                                'dark' => '🌙',
                                'zen' => '🏯'
                            ];
                            echo $icons[$template['slug']] ?? '📄';
                            ?>
                        </span>
                    </div>
                    
                    <div class="p-4">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $template['id'] ?>">
                            
                            <div>
                                <label class="text-sm text-gray-400">Nome</label>
                                <input 
                                    type="text" 
                                    name="name" 
                                    value="<?= htmlspecialchars($template['name']) ?>"
                                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 mt-1"
                                >
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">Plano Mínimo</label>
                                <select name="min_plan_id" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 mt-1">
                                    <?php foreach ($plans as $plan): ?>
                                        <option value="<?= $plan['id'] ?>" <?= $plan['id'] === $template['min_plan_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($plan['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" <?= $template['is_active'] ? 'checked' : '' ?>>
                                    <span class="text-sm">Ativo</span>
                                </label>
                                
                                <div class="flex gap-2">
                                    <a href="template-preview.php?slug=<?= $template['slug'] ?>" target="_blank" class="text-sm text-blue-400 hover:text-blue-300">
                                        Preview
                                    </a>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 rounded-lg px-4 py-2 text-sm transition">
                                Salvar Alterações
                            </button>
                        </form>
                    </div>
                    
                    <!-- Features -->
                    <div class="px-4 pb-4">
                        <div class="flex flex-wrap gap-2">
                            <?php if ($template['supports_video']): ?>
                                <span class="text-xs bg-blue-900 text-blue-400 px-2 py-1 rounded">Vídeo</span>
                            <?php endif; ?>
                            <?php if ($template['supports_promo_price']): ?>
                                <span class="text-xs bg-purple-900 text-purple-400 px-2 py-1 rounded">Promo</span>
                            <?php endif; ?>
                            <?php if ($template['has_grid_view']): ?>
                                <span class="text-xs bg-green-900 text-green-400 px-2 py-1 rounded">Grid</span>
                            <?php endif; ?>
                            <?php if ($template['has_list_view']): ?>
                                <span class="text-xs bg-yellow-900 text-yellow-400 px-2 py-1 rounded">Lista</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
