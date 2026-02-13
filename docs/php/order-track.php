<?php
/**
 * PREMIUM MENU - Página de Acompanhamento do Pedido
 * 
 * URL: /pedido/{token}
 * Mostra status em tempo real com polling AJAX
 */

require_once __DIR__ . '/includes/functions.php';

$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    http_response_code(404);
    echo 'Pedido não encontrado';
    exit;
}

$order = getOrderByToken($token);
if (!$order) {
    http_response_code(404);
    echo 'Pedido não encontrado';
    exit;
}

$restaurant = getRestaurantById($order['restaurant_id']);
$items = [];
$sql = "SELECT * FROM order_items WHERE order_id = :order_id";
$stmt = db()->prepare($sql);
$stmt->execute(['order_id' => $order['id']]);
$items = $stmt->fetchAll();

$statusLabels = [
    'pending' => 'Recebido',
    'confirmed' => 'Confirmado',
    'preparing' => 'Preparando',
    'ready' => 'Pronto',
    'delivering' => 'Em Entrega',
    'delivered' => 'Entregue',
    'cancelled' => 'Cancelado'
];

$statusFlow = ['pending', 'confirmed', 'preparing', 'ready'];
if ($order['cart_mode'] === 'delivery') {
    $statusFlow[] = 'delivering';
}
$statusFlow[] = 'delivered';

$currentStatusIndex = array_search($order['status'], $statusFlow);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?= $order['id'] ?> - <?= htmlspecialchars($restaurant['name']) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .track-container {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
        }
        .track-header {
            text-align: center;
            padding: 24px 0;
        }
        .track-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 12px;
        }
        .track-restaurant {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .track-order-id {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
        }
        .track-card {
            background: rgba(30,30,30,0.9);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 16px;
        }
        /* Timeline */
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 24px 0;
            padding: 0 8px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 24px;
            right: 24px;
            height: 3px;
            background: rgba(255,255,255,0.1);
        }
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            min-width: 48px;
        }
        .timeline-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: 3px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        .timeline-dot.active {
            background: #22c55e;
            border-color: #22c55e;
        }
        .timeline-dot.current {
            background: #f59e0b;
            border-color: #f59e0b;
            animation: pulse 2s infinite;
        }
        .timeline-dot.cancelled {
            background: #dc2626;
            border-color: #dc2626;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.4); }
            50% { box-shadow: 0 0 0 8px rgba(245,158,11,0); }
        }
        .timeline-label {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.4);
            text-align: center;
            max-width: 60px;
        }
        .timeline-label.active { color: rgba(255,255,255,0.8); }
        /* Status atual */
        .current-status {
            text-align: center;
            margin: 20px 0;
        }
        .current-status h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f59e0b;
        }
        .current-status p {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
            margin-top: 4px;
        }
        /* Itens */
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 0.9rem;
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-name { flex: 1; }
        .order-item-details { color: rgba(255,255,255,0.4); font-size: 0.75rem; }
        .order-item-price { font-weight: 600; color: #fbbf24; }
        .order-total {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            margin-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.15);
            font-size: 1.1rem;
            font-weight: 700;
        }
        .order-total span:last-child { color: #fbbf24; }
        .update-time {
            text-align: center;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.3);
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="track-container">
        <div class="track-header">
            <?php if ($restaurant['logo']): ?>
                <img src="<?= htmlspecialchars($restaurant['logo']) ?>" class="track-logo" alt="Logo">
            <?php endif; ?>
            <div class="track-restaurant"><?= htmlspecialchars($restaurant['name']) ?></div>
            <div class="track-order-id">
                Pedido #<?= $order['id'] ?>
                <?php if ($order['table_number']): ?>
                    - Mesa <?= htmlspecialchars($order['table_number']) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="track-card">
            <!-- Timeline -->
            <div class="timeline" id="timeline">
                <?php foreach ($statusFlow as $i => $step): 
                    $isActive = $currentStatusIndex !== false && $i <= $currentStatusIndex;
                    $isCurrent = $i === $currentStatusIndex;
                    $isCancelled = $order['status'] === 'cancelled';
                    $dotClass = $isCancelled && $isCurrent ? 'cancelled' : ($isCurrent ? 'current' : ($isActive ? 'active' : ''));
                ?>
                    <div class="timeline-step">
                        <div class="timeline-dot <?= $dotClass ?>">
                            <?php if ($isActive && !$isCurrent): ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                            <?php endif; ?>
                        </div>
                        <span class="timeline-label <?= $isActive ? 'active' : '' ?>">
                            <?= $statusLabels[$step] ?? $step ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="current-status" id="current-status">
                <h3><?= $statusLabels[$order['status']] ?? $order['status'] ?></h3>
                <p>Atualizado às <?= date('H:i', strtotime($order['updated_at'])) ?></p>
            </div>
        </div>

        <div class="track-card">
            <h4 style="margin-bottom: 12px; font-size: 0.9rem; color: rgba(255,255,255,0.5);">Itens do Pedido</h4>
            <?php foreach ($items as $item): ?>
                <div class="order-item">
                    <div class="order-item-name">
                        <?= $item['quantity'] ?>x <?= htmlspecialchars($item['product_name']) ?>
                        <?php if ($item['size_selected']): ?>
                            <div class="order-item-details"><?= htmlspecialchars($item['size_selected']) ?></div>
                        <?php endif; ?>
                        <?php 
                        $vars = json_decode($item['variations_selected'] ?? '[]', true);
                        if ($vars): ?>
                            <div class="order-item-details"><?= implode(', ', array_column($vars, 'option')) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="order-item-price">R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>

            <?php if ($order['notes']): ?>
                <div style="margin-top: 12px; font-size: 0.8rem; color: rgba(255,255,255,0.4);">
                    📝 <?= htmlspecialchars($order['notes']) ?>
                </div>
            <?php endif; ?>

            <div class="order-total">
                <span>Total</span>
                <span>R$ <?= number_format($order['total'], 2, ',', '.') ?></span>
            </div>
        </div>

        <div style="text-align:center; margin-top:20px;">
            <a href="/<?= htmlspecialchars($restaurant['slug']) ?>" 
               style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.6);text-decoration:none;font-size:0.9rem;padding:12px 24px;border:1px solid rgba(255,255,255,0.15);border-radius:50px;transition:all 0.2s;">
                ← Voltar ao Cardápio
            </a>
        </div>

        <div class="update-time" id="update-time">
            Atualiza automaticamente a cada 15 segundos
        </div>
    </div>

    <script>
        // Polling para atualização de status
        setInterval(async () => {
            try {
                const res = await fetch('/api/orders.php?action=status&token=<?= urlencode($token) ?>');
                const data = await res.json();
                if (data.success && data.order) {
                    const statusLabels = <?= json_encode($statusLabels) ?>;
                    const el = document.getElementById('current-status');
                    el.querySelector('h3').textContent = statusLabels[data.order.status] || data.order.status;
                    el.querySelector('p').textContent = 'Atualizado às ' + new Date(data.order.updated_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
                    
                    // Reload page if status changed to update timeline
                    if (data.order.status !== '<?= $order['status'] ?>') {
                        location.reload();
                    }
                }
            } catch (e) {}
        }, 15000);
    </script>
</body>
</html>
