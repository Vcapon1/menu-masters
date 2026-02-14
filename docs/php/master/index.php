<?php
/**
 * CARDÁPIO FLORIPA - Dashboard do Master Admin
 * 
 * Painel principal do administrador master com visão geral do sistema.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['master_admin'])) {
    header('Location: login.php');
    exit;
}

$admin = $_SESSION['master_admin'];

// Estatísticas gerais
$stats = [];

// Total de restaurantes
$stmt = db()->query("SELECT COUNT(*) as total FROM restaurants");
$stats['restaurants'] = $stmt->fetch()['total'];

// Restaurantes ativos
$stmt = db()->query("SELECT COUNT(*) as total FROM restaurants WHERE status = 'active'");
$stats['active_restaurants'] = $stmt->fetch()['total'];

// Total de pratos
$stmt = db()->query("SELECT COUNT(*) as total FROM products");
$stats['products'] = $stmt->fetch()['total'];

// Visualizações hoje
$stmt = db()->query("SELECT COUNT(*) as total FROM access_stats WHERE DATE(accessed_at) = CURDATE()");
$stats['views_today'] = $stmt->fetch()['total'];

// Visualizações mês
$stmt = db()->query("SELECT COUNT(*) as total FROM access_stats WHERE accessed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['views_month'] = $stmt->fetch()['total'];

// Restaurantes recentes
$stmt = db()->query("SELECT r.*, p.name as plan_name FROM restaurants r JOIN plans p ON r.plan_id = p.id ORDER BY r.created_at DESC LIMIT 5");
$recentRestaurants = $stmt->fetchAll();

// Restaurantes expirando
$stmt = db()->query("SELECT r.*, p.name as plan_name FROM restaurants r JOIN plans p ON r.plan_id = p.id WHERE r.expires_at IS NOT NULL AND r.expires_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND r.status = 'active' ORDER BY r.expires_at ASC LIMIT 5");
$expiringRestaurants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <h1 class="font-bold text-lg text-orange-400"><?= APP_NAME ?></h1>
                <div class="flex gap-4 text-sm">
                    <a href="index.php" class="text-white">Dashboard</a>
                    <a href="restaurants.php" class="text-gray-400 hover:text-white">Restaurantes</a>
                    <a href="plans.php" class="text-gray-400 hover:text-white">Planos</a>
                    <a href="templates.php" class="text-gray-400 hover:text-white">Templates</a>
                    <a href="directory.php" class="text-gray-400 hover:text-white">Diretório</a>
                    <a href="reports.php" class="text-gray-400 hover:text-white">Relatórios</a>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-400"><?= htmlspecialchars($admin['name']) ?></span>
                <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
            </div>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Dashboard</h2>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Restaurantes</p>
                <p class="text-3xl font-bold text-orange-400"><?= number_format($stats['restaurants']) ?></p>
                <p class="text-xs text-gray-500"><?= $stats['active_restaurants'] ?> ativos</p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Pratos</p>
                <p class="text-3xl font-bold text-blue-400"><?= number_format($stats['products']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Views Hoje</p>
                <p class="text-3xl font-bold text-green-400"><?= number_format($stats['views_today']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Views Mês</p>
                <p class="text-3xl font-bold text-purple-400"><?= number_format($stats['views_month']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Taxa de Ativação</p>
                <p class="text-3xl font-bold text-yellow-400">
                    <?= $stats['restaurants'] > 0 ? round(($stats['active_restaurants'] / $stats['restaurants']) * 100) : 0 ?>%
                </p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Recentes -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold">Restaurantes Recentes</h3>
                    <a href="restaurants.php" class="text-sm text-orange-400 hover:text-orange-300">Ver todos →</a>
                </div>
                <div class="divide-y divide-gray-700">
                    <?php foreach ($recentRestaurants as $r): ?>
                        <div class="p-4 flex items-center gap-4">
                            <?php if (!empty($r['logo'])): ?>
                                <img src="<?= htmlspecialchars($r['logo']) ?>" alt="" class="w-10 h-10 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center">🍽️</div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <p class="font-medium"><?= htmlspecialchars($r['name']) ?></p>
                                <p class="text-sm text-gray-400"><?= htmlspecialchars($r['plan_name']) ?></p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded <?= $r['status'] === 'active' ? 'bg-green-900 text-green-400' : 'bg-gray-700 text-gray-400' ?>">
                                <?= $r['status'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Expirando -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-700">
                    <h3 class="font-bold text-yellow-400">⚠️ Expirando em 7 dias</h3>
                </div>
                <div class="divide-y divide-gray-700">
                    <?php if (empty($expiringRestaurants)): ?>
                        <div class="p-8 text-center text-gray-400">
                            Nenhum restaurante expirando.
                        </div>
                    <?php else: ?>
                        <?php foreach ($expiringRestaurants as $r): ?>
                            <div class="p-4 flex items-center gap-4">
                                <div class="flex-1">
                                    <p class="font-medium"><?= htmlspecialchars($r['name']) ?></p>
                                    <p class="text-sm text-gray-400"><?= htmlspecialchars($r['plan_name']) ?></p>
                                </div>
                                <span class="text-sm text-yellow-400">
                                    <?= date('d/m/Y', strtotime($r['expires_at'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="restaurants.php?action=new" class="bg-orange-600 hover:bg-orange-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">🏪</span>
                <p class="font-medium mt-2">Novo Restaurante</p>
            </a>
            <a href="directory.php?action=new" class="bg-blue-600 hover:bg-blue-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📍</span>
                <p class="font-medium mt-2">Add ao Diretório</p>
            </a>
            <a href="reports.php" class="bg-purple-600 hover:bg-purple-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📊</span>
                <p class="font-medium mt-2">Relatórios</p>
            </a>
            <a href="stock-images.php" class="bg-green-600 hover:bg-green-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📸</span>
                <p class="font-medium mt-2">Banco de Imagens</p>
            </a>
        </div>
    </main>
</body>
</html>
