<?php
/**
 * CARDÁPIO FLORIPA - Relatórios
 * 
 * Relatórios gerais do sistema.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['master_admin'])) {
    header('Location: login.php');
    exit;
}

$admin = $_SESSION['master_admin'];

// Período
$period = $_GET['period'] ?? '30';
$periodDays = (int)$period;

// Estatísticas por período
$sql = "SELECT 
            COUNT(DISTINCT restaurant_id) as active_restaurants,
            COUNT(*) as total_views,
            COUNT(CASE WHEN access_type = 'menu_view' THEN 1 END) as menu_views,
            COUNT(CASE WHEN access_type = 'product_view' THEN 1 END) as product_views
        FROM access_stats
        WHERE accessed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
$stmt = db()->prepare($sql);
$stmt->bindValue('days', $periodDays, PDO::PARAM_INT);
$stmt->execute();
$periodStats = $stmt->fetch();

// Top restaurantes
$sql = "SELECT r.name, COUNT(s.id) as views
        FROM access_stats s
        JOIN restaurants r ON s.restaurant_id = r.id
        WHERE s.accessed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY r.id, r.name
        ORDER BY views DESC
        LIMIT 10";
$stmt = db()->prepare($sql);
$stmt->bindValue('days', $periodDays, PDO::PARAM_INT);
$stmt->execute();
$topRestaurants = $stmt->fetchAll();

// Visualizações por dia
$sql = "SELECT DATE(accessed_at) as date, COUNT(*) as views
        FROM access_stats
        WHERE accessed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY DATE(accessed_at)
        ORDER BY date ASC";
$stmt = db()->prepare($sql);
$stmt->bindValue('days', $periodDays, PDO::PARAM_INT);
$stmt->execute();
$dailyViews = $stmt->fetchAll();

// Planos mais usados
$sql = "SELECT p.name, COUNT(r.id) as count
        FROM plans p
        LEFT JOIN restaurants r ON p.id = r.plan_id AND r.status = 'active'
        GROUP BY p.id, p.name
        ORDER BY count DESC";
$stmt = db()->query($sql);
$planUsage = $stmt->fetchAll();

// Templates mais usados
$sql = "SELECT t.name, COUNT(r.id) as count
        FROM templates t
        LEFT JOIN restaurants r ON t.id = r.template_id AND r.status = 'active'
        GROUP BY t.id, t.name
        ORDER BY count DESC";
$stmt = db()->query($sql);
$templateUsage = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Master Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                    <a href="templates.php" class="text-gray-400 hover:text-white">Templates</a>
                    <a href="directory.php" class="text-gray-400 hover:text-white">Diretório</a>
                    <a href="reports.php" class="text-white">Relatórios</a>
                </div>
            </div>
            <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Relatórios</h2>
            
            <!-- Filtro de período -->
            <div class="flex gap-2">
                <a href="?period=7" class="px-4 py-2 rounded-lg <?= $period === '7' ? 'bg-orange-600' : 'bg-gray-700 hover:bg-gray-600' ?> transition">
                    7 dias
                </a>
                <a href="?period=30" class="px-4 py-2 rounded-lg <?= $period === '30' ? 'bg-orange-600' : 'bg-gray-700 hover:bg-gray-600' ?> transition">
                    30 dias
                </a>
                <a href="?period=90" class="px-4 py-2 rounded-lg <?= $period === '90' ? 'bg-orange-600' : 'bg-gray-700 hover:bg-gray-600' ?> transition">
                    90 dias
                </a>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Restaurantes Ativos</p>
                <p class="text-3xl font-bold text-orange-400"><?= number_format($periodStats['active_restaurants']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Total de Views</p>
                <p class="text-3xl font-bold text-blue-400"><?= number_format($periodStats['total_views']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Views do Menu</p>
                <p class="text-3xl font-bold text-green-400"><?= number_format($periodStats['menu_views']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Views de Produtos</p>
                <p class="text-3xl font-bold text-purple-400"><?= number_format($periodStats['product_views']) ?></p>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 mb-8">
            <h3 class="font-bold mb-4">Visualizações por Dia</h3>
            <canvas id="viewsChart" height="100"></canvas>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Top Restaurantes -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-700">
                    <h3 class="font-bold">Top Restaurantes</h3>
                </div>
                <div class="divide-y divide-gray-700">
                    <?php foreach ($topRestaurants as $index => $r): ?>
                        <div class="p-4 flex items-center gap-4">
                            <span class="w-6 h-6 bg-gray-700 rounded-full flex items-center justify-center text-xs">
                                <?= $index + 1 ?>
                            </span>
                            <span class="flex-1 truncate"><?= htmlspecialchars($r['name']) ?></span>
                            <span class="text-gray-400 text-sm"><?= number_format($r['views']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Uso de Planos -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-700">
                    <h3 class="font-bold">Planos Mais Usados</h3>
                </div>
                <div class="divide-y divide-gray-700">
                    <?php foreach ($planUsage as $p): ?>
                        <div class="p-4 flex items-center justify-between">
                            <span><?= htmlspecialchars($p['name']) ?></span>
                            <span class="bg-orange-900 text-orange-400 px-3 py-1 rounded-full text-sm">
                                <?= $p['count'] ?> restaurantes
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Uso de Templates -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-700">
                    <h3 class="font-bold">Templates Mais Usados</h3>
                </div>
                <div class="divide-y divide-gray-700">
                    <?php foreach ($templateUsage as $t): ?>
                        <div class="p-4 flex items-center justify-between">
                            <span><?= htmlspecialchars($t['name']) ?></span>
                            <span class="bg-blue-900 text-blue-400 px-3 py-1 rounded-full text-sm">
                                <?= $t['count'] ?> restaurantes
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        const ctx = document.getElementById('viewsChart').getContext('2d');
        const data = <?= json_encode($dailyViews) ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                }),
                datasets: [{
                    label: 'Visualizações',
                    data: data.map(d => d.views),
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: { color: '#9ca3af' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af' }
                    }
                }
            }
        });
    </script>
</body>
</html>
