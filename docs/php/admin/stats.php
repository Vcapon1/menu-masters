<?php
/**
 * CARDÁPIO FLORIPA - Estatísticas
 * 
 * Visualização de estatísticas de acesso do cardápio.
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

// Estatísticas
$stats = getRestaurantStats($restaurantId);
$topProducts = getTopProducts($restaurantId, 10);

// Acessos por dia (últimos 30 dias)
$sql = "SELECT DATE(accessed_at) as date, COUNT(*) as views
        FROM access_stats
        WHERE restaurant_id = :rid AND accessed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(accessed_at)
        ORDER BY date ASC";
$stmt = db()->prepare($sql);
$stmt->execute(['rid' => $restaurantId]);
$dailyViews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-gray-400 hover:text-white">← Dashboard</a>
                <h1 class="font-bold">Estatísticas</h1>
            </div>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Hoje</p>
                <p class="text-3xl font-bold text-orange-400"><?= number_format($stats['views_today']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Esta Semana</p>
                <p class="text-3xl font-bold text-blue-400"><?= number_format($stats['views_week']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Este Mês</p>
                <p class="text-3xl font-bold text-green-400"><?= number_format($stats['views_month']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <p class="text-gray-400 text-sm">Total</p>
                <p class="text-3xl font-bold text-purple-400"><?= number_format($stats['total_views']) ?></p>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 mb-8">
            <h2 class="font-bold mb-4">Acessos nos últimos 30 dias</h2>
            <canvas id="viewsChart" height="100"></canvas>
        </div>
        
        <!-- Top Products -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700">
                <h2 class="font-bold">Pratos Mais Vistos</h2>
            </div>
            <div class="divide-y divide-gray-700">
                <?php if (empty($topProducts)): ?>
                    <div class="p-8 text-center text-gray-400">
                        Ainda não há dados suficientes.
                    </div>
                <?php else: ?>
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="p-4 flex items-center gap-4">
                            <span class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-sm font-bold">
                                <?= $index + 1 ?>
                            </span>
                            <span class="flex-1 font-medium"><?= htmlspecialchars($product['name']) ?></span>
                            <span class="text-gray-400"><?= number_format($product['views']) ?> visualizações</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
