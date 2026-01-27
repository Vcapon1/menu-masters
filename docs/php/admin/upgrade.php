<?php
/**
 * CARDÁPIO FLORIPA - Upgrade de Plano
 * 
 * Página para solicitar upgrade de plano.
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

// Buscar planos
$plans = getPlans();
$currentPlanId = $restaurant['plan_id'];

// Envio de solicitação
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = (int)($_POST['plan_id'] ?? 0);
    
    // Em produção, isso enviaria um email ou criaria um ticket
    $message = 'Solicitação enviada! Entraremos em contato em breve.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-gray-400 hover:text-white">← Dashboard</a>
                <h1 class="font-bold">Upgrade de Plano</h1>
            </div>
        </div>
    </nav>
    
    <main class="max-w-4xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg bg-green-900/50 border border-green-500 text-green-400">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Plano Atual -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
            <h2 class="text-lg font-bold mb-2">Seu Plano Atual</h2>
            <div class="flex items-center gap-4">
                <span class="text-2xl font-bold text-orange-400"><?= htmlspecialchars($restaurant['plan_name']) ?></span>
                <span class="text-gray-400">
                    <?= $restaurant['max_products'] === -1 ? 'Produtos ilimitados' : $restaurant['max_products'] . ' produtos' ?>
                    •
                    <?= $restaurant['max_categories'] === -1 ? 'Categorias ilimitadas' : $restaurant['max_categories'] . ' categorias' ?>
                </span>
            </div>
        </div>
        
        <!-- Planos Disponíveis -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($plans as $plan): 
                $isCurrent = $plan['id'] === $currentPlanId;
                $isUpgrade = $plan['id'] > $currentPlanId;
            ?>
                <div class="bg-gray-800 rounded-lg border <?= $isCurrent ? 'border-orange-500' : 'border-gray-700' ?> p-6 relative">
                    <?php if ($isCurrent): ?>
                        <span class="absolute -top-3 left-4 bg-orange-500 text-white text-xs px-3 py-1 rounded-full">
                            Atual
                        </span>
                    <?php endif; ?>
                    
                    <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($plan['name']) ?></h3>
                    <p class="text-3xl font-bold text-orange-400 mb-4">
                        R$ <?= number_format($plan['price'], 2, ',', '.') ?>
                        <span class="text-sm text-gray-400 font-normal">/mês</span>
                    </p>
                    
                    <ul class="space-y-2 text-sm text-gray-300 mb-6">
                        <li>✓ <?= $plan['max_products'] === -1 ? 'Produtos ilimitados' : $plan['max_products'] . ' produtos' ?></li>
                        <li>✓ <?= $plan['max_categories'] === -1 ? 'Categorias ilimitadas' : $plan['max_categories'] . ' categorias' ?></li>
                        <?php if ($plan['supports_video']): ?>
                            <li>✓ Vídeos nos pratos</li>
                        <?php endif; ?>
                        <?php if ($plan['supports_promo_price']): ?>
                            <li>✓ Preço promocional</li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if ($isUpgrade): ?>
                        <form method="POST">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 rounded-lg transition">
                                Solicitar Upgrade
                            </button>
                        </form>
                    <?php elseif ($isCurrent): ?>
                        <button disabled class="w-full bg-gray-700 text-gray-400 py-3 rounded-lg cursor-not-allowed">
                            Plano Atual
                        </button>
                    <?php else: ?>
                        <button disabled class="w-full bg-gray-700 text-gray-400 py-3 rounded-lg cursor-not-allowed">
                            Downgrade
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Contato -->
        <div class="mt-8 bg-gray-800 rounded-lg border border-gray-700 p-6 text-center">
            <h3 class="font-bold mb-2">Precisa de ajuda?</h3>
            <p class="text-gray-400 text-sm mb-4">Entre em contato para tirar dúvidas sobre os planos</p>
            <a 
                href="https://wa.me/5548999999999?text=Olá! Gostaria de saber mais sobre os planos do <?= urlencode(APP_NAME) ?>" 
                target="_blank"
                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 px-6 py-3 rounded-lg font-medium transition"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                WhatsApp
            </a>
        </div>
    </main>
</body>
</html>
