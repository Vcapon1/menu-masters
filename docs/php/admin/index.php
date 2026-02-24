<?php
/**
 * CARDÁPIO FLORIPA - Admin do Restaurante
 * 
 * Dashboard principal do painel administrativo do restaurante.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['restaurant_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

$restaurantId = $_SESSION['restaurant_id'];
$restaurant = getRestaurantById($restaurantId);

if (!$restaurant) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Buscar estatísticas
$stats = getRestaurantStats($restaurantId);
$categories = getCategories($restaurantId);
$products = getProducts($restaurantId, true); // incluir ocultos

// Verificar limites do plano
$totalProducts = count($products);
$totalCategories = count($categories);
$maxProducts = $restaurant['max_products'];
$maxCategories = $restaurant['max_categories'];
$canAddProducts = $maxProducts === -1 || $totalProducts < $maxProducts;
$canAddCategories = $maxCategories === -1 || $totalCategories < $maxCategories;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <?php if (!empty($restaurant['logo'])): ?>
                    <img src="<?= htmlspecialchars($restaurant['logo']) ?>" alt="Logo" class="w-10 h-10 rounded-full">
                <?php endif; ?>
                <div>
                    <h1 class="font-bold"><?= htmlspecialchars($restaurant['name']) ?></h1>
                    <span class="text-xs text-gray-400">Plano: <?= htmlspecialchars($restaurant['plan_name']) ?></span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="/<?= htmlspecialchars($restaurant['slug']) ?>" target="_blank" 
                   class="text-sm text-blue-400 hover:text-blue-300">
                    Ver Cardápio →
                </a>
                <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
            </div>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="bg-green-900/50 border border-green-600 rounded-lg p-4 mb-6"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Visualizações Hoje</p>
                <p class="text-2xl font-bold"><?= number_format($stats['views_today']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Esta Semana</p>
                <p class="text-2xl font-bold"><?= number_format($stats['views_week']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Pratos</p>
                <p class="text-2xl font-bold">
                    <?= $totalProducts ?><?= $maxProducts !== -1 ? "/$maxProducts" : '' ?>
                </p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Categorias</p>
                <p class="text-2xl font-bold">
                    <?= $totalCategories ?><?= $maxCategories !== -1 ? "/$maxCategories" : '' ?>
                </p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <a href="orders.php" class="bg-red-600 hover:bg-red-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📋</span>
                <p class="font-medium mt-2">Pedidos</p>
            </a>
            <a href="products.php" class="bg-blue-600 hover:bg-blue-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">🍽️</span>
                <p class="font-medium mt-2">Pratos</p>
            </a>
            <a href="categories.php" class="bg-green-600 hover:bg-green-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📂</span>
                <p class="font-medium mt-2">Categorias</p>
            </a>
            <a href="stats.php" class="bg-purple-600 hover:bg-purple-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📊</span>
                <p class="font-medium mt-2">Estatísticas</p>
            </a>
            <a href="qrcode.php" class="bg-orange-600 hover:bg-orange-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📱</span>
                <p class="font-medium mt-2">QR Code</p>
            </a>
        </div>
        
        <!-- Stripe Payments Card -->
        <?php 
        $hasStripe = !empty($restaurant['stripe_account_id']);
        $stripeStatus = $restaurant['stripe_account_status'] ?? '';
        $edgeFunctionBase = defined('EDGE_FUNCTION_BASE') ? EDGE_FUNCTION_BASE : 'https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1';
        ?>
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-lg">💳 Recebimentos Online</h2>
                <?php if ($stripeStatus === 'active'): ?>
                    <span class="px-3 py-1 text-xs rounded-full bg-green-600">✅ Ativo</span>
                <?php elseif ($stripeStatus === 'pending'): ?>
                    <span class="px-3 py-1 text-xs rounded-full bg-yellow-600">⏳ Pendente</span>
                <?php else: ?>
                    <span class="px-3 py-1 text-xs rounded-full bg-gray-600">Não configurado</span>
                <?php endif; ?>
            </div>
            
            <?php if ($stripeStatus === 'active'): ?>
                <p class="text-gray-400 text-sm mb-4">
                    Seus recebimentos estão configurados. Os pagamentos online dos pedidos serão depositados na sua conta.
                </p>
                <div class="flex gap-3">
                    <button onclick="openStripeDashboard()" 
                            class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm transition">
                        📊 Ver Dashboard Stripe
                    </button>
                </div>
            <?php elseif ($stripeStatus === 'pending'): ?>
                <p class="text-yellow-400 text-sm mb-4">
                    Sua conta está sendo verificada. Complete o cadastro para começar a receber pagamentos.
                </p>
                <button onclick="startOnboarding()" 
                        class="bg-yellow-600 hover:bg-yellow-700 px-4 py-2 rounded-lg text-sm transition">
                    Completar Cadastro →
                </button>
            <?php else: ?>
                <p class="text-gray-400 text-sm mb-4">
                    Configure seus recebimentos para aceitar pagamentos online via cartão de crédito e Pix diretamente no seu cardápio.
                </p>
                <button onclick="startOnboarding()" 
                        class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm transition">
                    🚀 Configurar Recebimentos
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Plan Info -->
        <?php if (!$canAddProducts || !$canAddCategories): ?>
        <div class="bg-yellow-900/50 border border-yellow-600 rounded-lg p-4 mb-8">
            <p class="text-yellow-400 font-medium">⚠️ Limite do plano atingido</p>
            <p class="text-sm text-gray-300 mt-1">
                Você atingiu o limite do seu plano. 
                <a href="upgrade.php" class="text-yellow-400 underline">Faça upgrade</a> para adicionar mais itens.
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Recent Products -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                <h2 class="font-bold">Pratos Recentes</h2>
                <?php if ($canAddProducts): ?>
                    <a href="products.php?action=new" class="text-sm bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg">
                        + Novo Prato
                    </a>
                <?php endif; ?>
            </div>
            <div class="divide-y divide-gray-700">
                <?php 
                $recentProducts = array_slice($products, 0, 5);
                foreach ($recentProducts as $product): 
                    $badges = getProductBadges($product['badges']);
                ?>
                    <div class="p-4 flex items-center gap-4">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="w-12 h-12 rounded object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-gray-700 rounded flex items-center justify-center">
                                🍽️
                            </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <p class="font-medium"><?= htmlspecialchars($product['name']) ?></p>
                            <p class="text-sm text-gray-400"><?= htmlspecialchars($product['category_name']) ?></p>
                        </div>
                        <div class="text-right">
                            <?php if (!empty($product['promo_price'])): ?>
                                <p class="text-sm line-through text-gray-500"><?= formatPrice($product['price']) ?></p>
                                <p class="font-bold text-red-400"><?= formatPrice($product['promo_price']) ?></p>
                            <?php else: ?>
                                <p class="font-bold"><?= formatPrice($product['price']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-1">
                            <?php foreach ($badges as $badge): ?>
                                <span class="text-xs px-2 py-1 rounded <?= $badge['color'] ?>"><?= $badge['label'] ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!$product['is_available']): ?>
                            <span class="text-xs px-2 py-1 rounded bg-red-900 text-red-400">Indisponível</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        const EDGE_URL = '<?= $edgeFunctionBase ?>';
        const RESTAURANT_ID = <?= $restaurantId ?>;
        const STRIPE_ACCOUNT_ID = '<?= htmlspecialchars($restaurant['stripe_account_id'] ?? '') ?>';

        async function startOnboarding() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Preparando...';

            try {
                const action = STRIPE_ACCOUNT_ID ? 'refresh_link' : 'create';
                const res = await fetch(EDGE_URL + '/stripe-onboarding', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: action,
                        restaurant_id: RESTAURANT_ID,
                        restaurant_name: '<?= htmlspecialchars($restaurant['name'], ENT_QUOTES) ?>',
                        restaurant_email: '<?= htmlspecialchars($restaurant['email'], ENT_QUOTES) ?>',
                        return_url: window.location.origin + '/admin/index.php',
                        account_id: STRIPE_ACCOUNT_ID || undefined,
                    })
                });

                const data = await res.json();
                if (data.success && (data.onboarding_url)) {
                    // Salvar account_id no banco via PHP
                    if (data.account_id) {
                        await fetch('/api/orders.php?action=save_stripe_account', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                restaurant_id: RESTAURANT_ID,
                                stripe_account_id: data.account_id,
                                stripe_account_status: 'pending'
                            })
                        });
                    }
                    window.location.href = data.onboarding_url;
                } else {
                    alert(data.error || 'Erro ao iniciar configuração');
                    btn.disabled = false;
                    btn.textContent = '🚀 Configurar Recebimentos';
                }
            } catch (e) {
                alert('Erro de conexão: ' + e.message);
                btn.disabled = false;
                btn.textContent = '🚀 Configurar Recebimentos';
            }
        }

        async function openStripeDashboard() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Abrindo...';

            try {
                const res = await fetch(EDGE_URL + '/stripe-onboarding', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'login_link',
                        account_id: STRIPE_ACCOUNT_ID,
                    })
                });

                const data = await res.json();
                if (data.success && data.url) {
                    window.open(data.url, '_blank');
                } else {
                    alert(data.error || 'Erro ao abrir dashboard');
                }
            } catch (e) {
                alert('Erro: ' + e.message);
            }
            btn.disabled = false;
            btn.textContent = '📊 Ver Dashboard Stripe';
        }

        // Verificar retorno do Stripe onboarding
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('stripe_return') === '1') {
            const accountId = urlParams.get('account_id');
            if (accountId) {
                // Verificar status da conta
                fetch(EDGE_URL + '/stripe-onboarding', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'status', account_id: accountId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        fetch('/api/orders.php?action=save_stripe_account', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                restaurant_id: RESTAURANT_ID,
                                stripe_account_id: accountId,
                                stripe_account_status: data.status
                            })
                        }).then(() => {
                            window.location.href = '/admin/index.php';
                        });
                    }
                });
            }
        }
    </script>
</body>
</html>
