<?php
/**
 * PREMIUM MENU - Painel de Pedidos do Restaurante
 * 
 * Painel em tempo real com polling AJAX para gerenciar pedidos.
 * Toggle ABERTO/FECHADO, filtros por status, alertas de tempo.
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

$statusLabels = [
    'pending' => 'Pendente',
    'confirmed' => 'Confirmado',
    'preparing' => 'Preparando',
    'ready' => 'Pronto',
    'delivering' => 'Em Entrega',
    'delivered' => 'Entregue',
    'cancelled' => 'Cancelado'
];

$statusColors = [
    'pending' => '#f59e0b',
    'confirmed' => '#3b82f6',
    'preparing' => '#8b5cf6',
    'ready' => '#22c55e',
    'delivering' => '#06b6d4',
    'delivered' => '#6b7280',
    'cancelled' => '#ef4444'
];

$timeLimits = json_decode($restaurant['order_time_limits'] ?? '{}', true) ?: [
    'pending' => 5,
    'preparing' => 20,
    'ready' => 10
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .order-card { transition: all 0.3s; }
        .order-card.alert-yellow { border-left: 4px solid #f59e0b; }
        .order-card.alert-red { border-left: 4px solid #ef4444; animation: pulse-border 2s infinite; }
        @keyframes pulse-border { 0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.3); } 50% { box-shadow: 0 0 0 6px rgba(239,68,68,0); } }
        .toggle-open { transition: all 0.3s; }
        .toggle-open.open { background: #22c55e; }
        .toggle-open.closed { background: #ef4444; }
        .status-select { appearance: none; cursor: pointer; }
        .filter-btn.active { background: rgba(139,92,246,0.3); border-color: #8b5cf6; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Header com Toggle ABERTO/FECHADO -->
    <nav class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <?php if ($restaurant['logo']): ?>
                    <img src="<?= htmlspecialchars($restaurant['logo']) ?>" class="w-8 h-8 rounded-full object-cover">
                <?php endif; ?>
                <h1 class="font-bold text-sm md:text-base"><?= htmlspecialchars($restaurant['name']) ?></h1>
            </div>
            
            <div class="flex items-center gap-4">
                <button id="toggle-open" onclick="toggleOpen()" 
                        class="toggle-open <?= $restaurant['is_open'] ? 'open' : 'closed' ?> px-4 py-2 rounded-full text-sm font-bold flex items-center gap-2">
                    <span id="open-dot" class="w-3 h-3 rounded-full <?= $restaurant['is_open'] ? 'bg-green-200' : 'bg-red-200' ?>"></span>
                    <span id="open-text"><?= $restaurant['is_open'] ? 'ABERTO' : 'FECHADO' ?></span>
                </button>
                
                <a href="index.php" class="text-gray-400 hover:text-white text-sm">← Painel</a>
            </div>
        </div>
    </nav>

    <!-- Filtros -->
    <div class="max-w-6xl mx-auto px-4 py-4">
        <div class="flex gap-2 overflow-x-auto pb-2" id="filters">
            <button class="filter-btn active px-3 py-1.5 rounded-full border border-gray-600 text-sm whitespace-nowrap" 
                    data-status="" onclick="filterOrders('')">
                Todos <span id="count-all" class="ml-1 text-xs opacity-60"></span>
            </button>
            <button class="filter-btn px-3 py-1.5 rounded-full border border-gray-600 text-sm whitespace-nowrap" 
                    data-status="pending" onclick="filterOrders('pending')">
                🟡 Pendentes <span id="count-pending" class="ml-1 text-xs opacity-60"></span>
            </button>
            <button class="filter-btn px-3 py-1.5 rounded-full border border-gray-600 text-sm whitespace-nowrap" 
                    data-status="preparing" onclick="filterOrders('preparing')">
                🟣 Preparando <span id="count-preparing" class="ml-1 text-xs opacity-60"></span>
            </button>
            <button class="filter-btn px-3 py-1.5 rounded-full border border-gray-600 text-sm whitespace-nowrap" 
                    data-status="ready" onclick="filterOrders('ready')">
                🟢 Pronto <span id="count-ready" class="ml-1 text-xs opacity-60"></span>
            </button>
            <button class="filter-btn px-3 py-1.5 rounded-full border border-gray-600 text-sm whitespace-nowrap" 
                    data-status="delivered" onclick="filterOrders('delivered')">
                Entregue
            </button>
            <button class="filter-btn px-3 py-1.5 rounded-full border border-gray-600 text-sm whitespace-nowrap" 
                    data-status="cancelled" onclick="filterOrders('cancelled')">
                Cancelado
            </button>
        </div>
        <div class="flex gap-2 mt-2">
            <button id="btn-archive-all" onclick="archiveAllDelivered()" 
                    class="px-3 py-1.5 rounded-full border border-yellow-600 text-yellow-400 text-sm hover:bg-yellow-900/30 transition-colors whitespace-nowrap">
                📦 Arquivar Entregues/Cancelados
            </button>
            <button id="btn-view-archived" onclick="toggleArchivedView()" 
                    class="px-3 py-1.5 rounded-full border border-gray-600 text-gray-400 text-sm hover:bg-gray-700 transition-colors whitespace-nowrap">
                📁 Ver Arquivados
            </button>
        </div>
    </div>

    <!-- Lista de Pedidos -->
    <main class="max-w-6xl mx-auto px-4 pb-20">
        <div id="orders-list" class="space-y-4">
            <p class="text-center text-gray-500 py-12">Carregando pedidos...</p>
        </div>
    </main>

    <!-- Audio para notificação -->
    <audio id="notification-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgipusr5VdMC1ckqy0s5ZYMC1al6u0spNVLitZmKu1sJJTKyxYmKy0sZJSKyxYl6y0sZNSKyxYl6y1sJNSKy1Zl620sJNSLC1ZmK20sJJSLC1Zl620sJNSLC1Zl621r5JSLC1Zl6y1r5NSLC5Zl621r5NSLC5Zl6y1r5NTLC5Zl621sJNTLC5Zl621sJNTLC9Zl621sJNTLC9Zl621sJNTLC9Zl621sJNTLC9Zl621sJNTLC9Zl621sJNT" type="audio/wav">
    </audio>

    <script>
        const STATUS_LABELS = <?= json_encode($statusLabels) ?>;
        const STATUS_COLORS = <?= json_encode($statusColors) ?>;
        const TIME_LIMITS = <?= json_encode($timeLimits) ?>;
        
        let currentFilter = '';
        let lastOrderCount = 0;
        let isOpen = <?= $restaurant['is_open'] ? 'true' : 'false' ?>;
        let viewingArchived = false;

        async function toggleOpen() {
            isOpen = !isOpen;
            try {
                await fetch('/api/orders.php?action=toggle_open', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({is_open: isOpen})
                });
                
                const btn = document.getElementById('toggle-open');
                const dot = document.getElementById('open-dot');
                const text = document.getElementById('open-text');
                
                btn.className = 'toggle-open ' + (isOpen ? 'open' : 'closed') + ' px-4 py-2 rounded-full text-sm font-bold flex items-center gap-2';
                dot.className = 'w-3 h-3 rounded-full ' + (isOpen ? 'bg-green-200' : 'bg-red-200');
                text.textContent = isOpen ? 'ABERTO' : 'FECHADO';
            } catch (e) {
                console.error(e);
            }
        }

        function filterOrders(status) {
            currentFilter = status;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status === status);
            });
            loadOrders();
        }

        async function loadOrders() {
            try {
                const url = '/api/orders.php?action=list' + (currentFilter ? '&status=' + currentFilter : '') + (viewingArchived ? '&archived=1' : '');
                const res = await fetch(url);
                const data = await res.json();
                
                if (!data.success) return;
                
                const orders = data.orders || [];
                
                // Contadores
                const counts = {};
                orders.forEach(o => {
                    counts[o.status] = (counts[o.status] || 0) + 1;
                });
                
                ['pending', 'preparing', 'ready'].forEach(s => {
                    const el = document.getElementById('count-' + s);
                    if (el) el.textContent = counts[s] ? `(${counts[s]})` : '';
                });
                const allEl = document.getElementById('count-all');
                if (allEl) allEl.textContent = orders.length ? `(${orders.length})` : '';
                
                // Notificar se novos pedidos
                if (orders.length > lastOrderCount && lastOrderCount > 0) {
                    try { document.getElementById('notification-sound').play(); } catch(e) {}
                }
                lastOrderCount = orders.length;
                
                renderOrders(orders);
            } catch (e) {
                console.error(e);
            }
        }

        function renderOrders(orders) {
            const container = document.getElementById('orders-list');
            
            if (orders.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-12">Nenhum pedido encontrado</p>';
                return;
            }
            
            container.innerHTML = orders.map(order => {
                const now = new Date();
                const createdAt = new Date(order.created_at);
                const minutesAgo = Math.floor((now - createdAt) / 60000);
                const timeLimit = TIME_LIMITS[order.status] || 999;
                const isOverdue = minutesAgo > timeLimit;
                const isWarning = minutesAgo > timeLimit * 0.7;
                const alertClass = isOverdue ? 'alert-red' : (isWarning ? 'alert-yellow' : '');
                
                const modeLabels = {table: '🪑 Mesa', delivery: '🛵 Entrega', full: '💳 Completo', whatsapp: '📱 WhatsApp'};
                const modeLabel = modeLabels[order.cart_mode] || order.cart_mode;
                
                const itemsHtml = (order.items || []).map(item => {
                    const vars = JSON.parse(item.variations_selected || '[]');
                    const varsText = vars.length ? ` - ${vars.map(v => v.option).join(', ')}` : '';
                    const sizeText = item.size_selected ? ` (${item.size_selected})` : '';
                    return `<div class="flex justify-between text-sm py-1">
                        <span>${item.quantity}x ${item.product_name}${sizeText}${varsText}</span>
                        <span class="text-gray-400">R$ ${parseFloat(item.subtotal).toFixed(2).replace('.', ',')}</span>
                    </div>`;
                }).join('');
                
                const statusOptions = Object.entries(STATUS_LABELS).map(([val, label]) => 
                    `<option value="${val}" ${val === order.status ? 'selected' : ''} style="background:#1f2937">${label}</option>`
                ).join('');
                
                return `
                <div class="order-card ${alertClass} bg-gray-800 rounded-lg border border-gray-700 p-4">
                    <div class="flex flex-wrap justify-between items-start gap-2 mb-3">
                        <div>
                            <span class="font-bold text-lg">#${order.id}</span>
                            <span class="ml-2 text-sm text-gray-400">${modeLabel}</span>
                            ${order.table_number ? `<span class="ml-2 px-2 py-0.5 bg-blue-900 text-blue-300 rounded text-xs">Mesa ${order.table_number}</span>` : ''}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">${createdAt.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'})} · há ${minutesAgo} min</span>
                            <select onchange="updateStatus(${order.id}, this.value)" 
                                    class="status-select bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm"
                                    style="border-left: 3px solid ${STATUS_COLORS[order.status] || '#666'}">
                                ${statusOptions}
                            </select>
                        </div>
                    </div>
                    
                    ${order.customer_name ? `
                    <div class="text-sm text-gray-300 mb-2">
                        👤 ${order.customer_name}
                        ${order.customer_phone ? ` · 📞 ${order.customer_phone}` : ''}
                        ${order.customer_address ? `<br>📍 ${order.customer_address}` : ''}
                    </div>` : ''}
                    
                    <div class="border-t border-gray-700 pt-2 mt-2">
                        ${itemsHtml}
                        ${order.notes ? `<div class="text-xs text-gray-500 mt-2">📝 ${order.notes}</div>` : ''}
                    </div>
                    
                    <div class="flex justify-between items-center mt-3 pt-2 border-t border-gray-700">
                        <span class="text-sm text-gray-400">Total</span>
                        <div class="flex items-center gap-3">
                            ${(order.status === 'delivered' || order.status === 'cancelled') && !viewingArchived ? `
                                <button onclick="archiveOrder(${order.id})" class="text-xs text-gray-500 hover:text-yellow-400 transition-colors" title="Arquivar">📦 Arquivar</button>
                            ` : ''}
                            ${viewingArchived ? `
                                <button onclick="unarchiveOrder(${order.id})" class="text-xs text-gray-500 hover:text-blue-400 transition-colors" title="Desarquivar">↩ Restaurar</button>
                            ` : ''}
                            <span class="font-bold text-yellow-400">R$ ${parseFloat(order.total).toFixed(2).replace('.', ',')}</span>
                        </div>
                    </div>
                    
                    ${isOverdue ? `
                    <div class="mt-2 p-2 bg-red-900/50 border border-red-700 rounded text-xs text-red-300">
                        ⚠ ${STATUS_LABELS[order.status] || order.status} há ${minutesAgo} min (limite: ${timeLimit} min)
                    </div>` : ''}
                </div>`;
            }).join('');
        }

        async function updateStatus(orderId, status) {
            try {
                await fetch('/api/orders.php?action=update_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({order_id: orderId, status: status})
                });
                loadOrders();
            } catch (e) {
                console.error(e);
            }
        }

        async function archiveOrder(orderId) {
            try {
                await fetch('/api/orders.php?action=archive', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({order_id: orderId, archive: true})
                });
                loadOrders();
            } catch (e) { console.error(e); }
        }

        async function unarchiveOrder(orderId) {
            try {
                await fetch('/api/orders.php?action=archive', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({order_id: orderId, archive: false})
                });
                loadOrders();
            } catch (e) { console.error(e); }
        }

        async function archiveAllDelivered() {
            if (!confirm('Arquivar todos os pedidos entregues e cancelados?')) return;
            try {
                await fetch('/api/orders.php?action=archive_delivered', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'}
                });
                loadOrders();
            } catch (e) { console.error(e); }
        }

        function toggleArchivedView() {
            viewingArchived = !viewingArchived;
            const btn = document.getElementById('btn-view-archived');
            if (btn) {
                btn.textContent = viewingArchived ? '← Voltar aos Ativos' : '📁 Ver Arquivados';
                btn.classList.toggle('border-blue-500', viewingArchived);
                btn.classList.toggle('text-blue-400', viewingArchived);
            }
            loadOrders();
        }

        // Carregar pedidos inicialmente e polling a cada 10 segundos
        loadOrders();
        setInterval(loadOrders, 10000);
    </script>
</body>
</html>
