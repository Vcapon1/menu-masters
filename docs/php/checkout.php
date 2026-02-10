<?php
/**
 * PREMIUM MENU - Página de Checkout
 * 
 * Tela padronizada de finalização de pedido.
 * Layout do sistema (não do template) com logo e nome do restaurante.
 * 
 * Modos: table, delivery, full
 * Dados do carrinho vêm do localStorage via JavaScript.
 */

require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['restaurant']) ? sanitize($_GET['restaurant']) : '';
$mode = isset($_GET['mode']) ? sanitize($_GET['mode']) : '';

if (empty($slug) || empty($mode)) {
    header('Location: /');
    exit;
}

$restaurant = getRestaurantBySlug($slug);
if (!$restaurant) {
    http_response_code(404);
    echo 'Restaurante não encontrado';
    exit;
}

$modeLabels = [
    'table' => 'Pedido Mesa',
    'delivery' => 'Pedido Entrega',
    'full' => 'Pedido Completo'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - <?= htmlspecialchars($restaurant['name']) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            padding-bottom: 120px;
        }
        .checkout-header {
            background: rgba(30,30,30,0.95);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .checkout-logo {
            width: 40px; height: 40px; border-radius: 50%; object-fit: cover;
        }
        .checkout-name { font-weight: 700; font-size: 1rem; }
        .checkout-mode { font-size: 0.75rem; color: rgba(255,255,255,0.5); }
        .checkout-back {
            margin-left: auto;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.85rem;
        }
        .checkout-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .checkout-section {
            background: rgba(30,30,30,0.9);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
        }
        .checkout-section h3 {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .checkout-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.9rem;
        }
        .checkout-item:last-child { border-bottom: none; }
        .checkout-item-details { font-size: 0.75rem; color: rgba(255,255,255,0.4); }
        .checkout-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 700;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.15);
        }
        .checkout-total span:last-child { color: #fbbf24; }
        .checkout-input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 14px 16px;
            color: #fff;
            font-size: 0.9rem;
            margin-bottom: 12px;
            font-family: inherit;
        }
        .checkout-input::placeholder { color: rgba(255,255,255,0.3); }
        .checkout-input:focus { outline: none; border-color: #f59e0b; }
        .checkout-submit {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 20px;
            background: rgba(10,10,10,0.95);
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .checkout-submit button {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            display: block;
            padding: 16px;
            background: #f59e0b;
            color: #000;
            border: none;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }
        .checkout-submit button:hover { filter: brightness(1.1); }
        .checkout-submit button:disabled { opacity: 0.5; cursor: not-allowed; }
        .checkout-msg {
            text-align: center;
            padding: 40px 20px;
        }
        .checkout-msg h2 { font-size: 1.5rem; margin-bottom: 8px; }
        .checkout-msg a {
            display: inline-block;
            margin-top: 16px;
            padding: 12px 24px;
            background: #f59e0b;
            color: #000;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
        }
        .input-required { color: #f87171; }
    </style>
</head>
<body>
    <header class="checkout-header">
        <?php if ($restaurant['logo']): ?>
            <img src="<?= htmlspecialchars($restaurant['logo']) ?>" class="checkout-logo" alt="">
        <?php endif; ?>
        <div>
            <div class="checkout-name"><?= htmlspecialchars($restaurant['name']) ?></div>
            <div class="checkout-mode"><?= $modeLabels[$mode] ?? $mode ?></div>
        </div>
        <a href="javascript:history.back()" class="checkout-back">← Voltar</a>
    </header>

    <div class="checkout-container" id="checkout-content">
        <!-- Resumo do Pedido -->
        <div class="checkout-section">
            <h3>Resumo do Pedido</h3>
            <div id="checkout-items"></div>
            <div class="checkout-total">
                <span>Total</span>
                <span id="checkout-total">R$ 0,00</span>
            </div>
        </div>

        <!-- Campos por modo -->
        <?php if ($mode === 'table'): ?>
        <div class="checkout-section">
            <h3>Dados da Mesa</h3>
            <input type="text" id="field-table" class="checkout-input" placeholder="Número da mesa" readonly>
        </div>
        <?php endif; ?>

        <?php if ($mode === 'delivery'): ?>
        <div class="checkout-section">
            <h3>Dados para Entrega</h3>
            <input type="text" id="field-name" class="checkout-input" placeholder="Seu nome *" required>
            <input type="tel" id="field-phone" class="checkout-input" placeholder="Telefone *" required>
            <input type="text" id="field-address" class="checkout-input" placeholder="Endereço completo *" required>
        </div>
        <?php endif; ?>

        <?php if ($mode === 'full'): ?>
        <div class="checkout-section">
            <h3>Seus Dados</h3>
            <input type="text" id="field-name" class="checkout-input" placeholder="Nome completo *" required>
            <input type="tel" id="field-phone" class="checkout-input" placeholder="Telefone *" required>
            <input type="email" id="field-email" class="checkout-input" placeholder="Email *" required>
            <input type="text" id="field-address" class="checkout-input" placeholder="Endereço completo *" required>
        </div>
        <div class="checkout-section">
            <h3>Pagamento</h3>
            <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem;">
                💳 Integração de pagamento será implementada em breve.
            </p>
        </div>
        <?php endif; ?>

        <div class="checkout-section">
            <h3>Observações</h3>
            <textarea id="field-notes" class="checkout-input" rows="2" placeholder="Alguma observação?"></textarea>
        </div>
    </div>

    <!-- Resultado de sucesso (oculto inicialmente) -->
    <div id="checkout-success" class="checkout-msg" style="display:none">
        <h2>✅ Pedido Enviado!</h2>
        <p>Seu pedido foi recebido com sucesso.</p>
        <p id="success-order-id" style="color: rgba(255,255,255,0.5); margin-top: 8px;"></p>
        <a id="success-track-link" href="#">Acompanhar Pedido</a>
    </div>

    <div class="checkout-submit" id="checkout-footer">
        <button onclick="submitOrder()" id="submit-btn">
            <?= $mode === 'full' ? '💳 Pagar e Enviar Pedido' : '📦 Enviar Pedido' ?>
        </button>
    </div>

    <script>
        const MODE = '<?= $mode ?>';
        const RESTAURANT_ID = <?= $restaurant['id'] ?>;
        const RESTAURANT_SLUG = '<?= $restaurant['slug'] ?>';

        // Carregar dados do localStorage
        let checkoutData = {};
        try {
            checkoutData = JSON.parse(localStorage.getItem('checkout_data') || '{}');
        } catch(e) {}

        if (!checkoutData.items || checkoutData.items.length === 0) {
            document.getElementById('checkout-content').innerHTML = `
                <div class="checkout-msg">
                    <h2>Carrinho vazio</h2>
                    <p>Volte ao cardápio e adicione itens ao carrinho.</p>
                    <a href="/${RESTAURANT_SLUG}">Voltar ao Cardápio</a>
                </div>
            `;
            document.getElementById('checkout-footer').style.display = 'none';
        } else {
            // Renderizar itens
            const itemsEl = document.getElementById('checkout-items');
            itemsEl.innerHTML = checkoutData.items.map(item => {
                const vars = item.variations?.length ? item.variations.map(v => v.option).join(', ') : '';
                return `<div class="checkout-item">
                    <div>
                        ${item.quantity}x ${item.productName}
                        ${item.sizeSelected ? `<div class="checkout-item-details">${item.sizeSelected}</div>` : ''}
                        ${vars ? `<div class="checkout-item-details">${vars}</div>` : ''}
                    </div>
                    <span>R$ ${item.subtotal.toFixed(2).replace('.', ',')}</span>
                </div>`;
            }).join('');

            document.getElementById('checkout-total').textContent = 'R$ ' + checkoutData.total.toFixed(2).replace('.', ',');

            // Preencher mesa se disponível
            if (MODE === 'table' && checkoutData.tableNumber) {
                document.getElementById('field-table').value = checkoutData.tableNumber;
            }
        }

        async function submitOrder() {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            const payload = {
                restaurant_id: RESTAURANT_ID,
                cart_mode: MODE,
                table_number: document.getElementById('field-table')?.value || checkoutData.tableNumber || '',
                customer_name: document.getElementById('field-name')?.value || '',
                customer_phone: document.getElementById('field-phone')?.value || '',
                customer_address: document.getElementById('field-address')?.value || '',
                notes: document.getElementById('field-notes')?.value || checkoutData.generalNotes || '',
                items: checkoutData.items.map(item => ({
                    product_id: item.productId,
                    product_name: item.productName,
                    quantity: item.quantity,
                    size_selected: item.sizeSelected || null,
                    size_price: item.sizePrice || null,
                    variations_selected: item.variations || [],
                    unit_price: item.unitPrice,
                    subtotal: item.subtotal,
                    notes: item.notes || ''
                }))
            };

            // Validação básica
            if (MODE === 'delivery' || MODE === 'full') {
                if (!payload.customer_name || !payload.customer_phone) {
                    alert('Preencha os campos obrigatórios');
                    btn.disabled = false;
                    btn.textContent = MODE === 'full' ? '💳 Pagar e Enviar Pedido' : '📦 Enviar Pedido';
                    return;
                }
            }

            try {
                const res = await fetch('/api/orders.php?action=create', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success && data.order) {
                    // Limpar carrinho
                    localStorage.removeItem('checkout_data');
                    localStorage.removeItem('cart_' + RESTAURANT_ID);

                    // Mostrar sucesso
                    document.getElementById('checkout-content').style.display = 'none';
                    document.getElementById('checkout-footer').style.display = 'none';
                    document.getElementById('checkout-success').style.display = 'block';
                    document.getElementById('success-order-id').textContent = 'Pedido #' + data.order.id;
                    document.getElementById('success-track-link').href = '/pedido/' + data.order.token;
                } else {
                    alert(data.error || 'Erro ao enviar pedido');
                    btn.disabled = false;
                    btn.textContent = MODE === 'full' ? '💳 Pagar e Enviar Pedido' : '📦 Enviar Pedido';
                }
            } catch (e) {
                alert('Erro de conexão. Tente novamente.');
                btn.disabled = false;
                btn.textContent = MODE === 'full' ? '💳 Pagar e Enviar Pedido' : '📦 Enviar Pedido';
            }
        }
    </script>
</body>
</html>
