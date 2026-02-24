<?php
/**
 * PREMIUM MENU - Página de Checkout
 * 
 * Tela padronizada de finalização de pedido.
 * Layout do sistema (não do template) com logo e nome do restaurante.
 * 
 * Modos: table, delivery, full
 * Dados do carrinho vêm do localStorage via JavaScript.
 * 
 * Pagamento Online: Stripe Connect com Cartão e Pix
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

// Verificar se pagamento online está habilitado
$hasStripePayment = !empty($restaurant['stripe_account_id']) && $restaurant['stripe_account_status'] === 'active';
$paymentModel = $restaurant['payment_model'] ?? 'commission';
$platformFeePercent = floatval($restaurant['platform_fee_percent'] ?? 6.00);

$modeLabels = [
    'table' => 'Pedido Mesa',
    'delivery' => 'Pedido Entrega',
    'full' => 'Pedido Completo'
];

// URL base da Edge Function
$edgeFunctionBase = defined('EDGE_FUNCTION_BASE') ? EDGE_FUNCTION_BASE : 'https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - <?= htmlspecialchars($restaurant['name']) ?></title>
    <?php if ($hasStripePayment && $mode === 'full'): ?>
    <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>
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
        .checkout-logo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .checkout-name { font-weight: 700; font-size: 1rem; }
        .checkout-mode { font-size: 0.75rem; color: rgba(255,255,255,0.5); }
        .checkout-back {
            margin-left: auto;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.85rem;
        }
        .checkout-container { max-width: 500px; margin: 0 auto; padding: 20px; }
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
        .checkout-msg { text-align: center; padding: 40px 20px; }
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
        /* Payment method selection */
        .payment-method-options {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        .payment-method-btn {
            flex: 1;
            padding: 14px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            color: #fff;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            font-family: inherit;
            font-size: 0.9rem;
        }
        .payment-method-btn.active {
            border-color: #f59e0b;
            background: rgba(245,158,11,0.1);
        }
        .payment-method-btn:hover { border-color: rgba(255,255,255,0.3); }
        .payment-method-btn .icon { font-size: 1.5rem; display: block; margin-bottom: 4px; }
        .payment-method-btn .label { font-weight: 600; }
        .payment-method-btn .desc { font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 2px; }
        /* Stripe Elements container */
        #stripe-card-element {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 12px;
        }
        #stripe-card-errors {
            color: #f87171;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
        /* Pix QR Code */
        .pix-container {
            text-align: center;
            padding: 20px;
        }
        .pix-container img {
            max-width: 200px;
            margin: 0 auto 12px;
            border-radius: 8px;
        }
        .pix-status {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
        }
        .pix-copy-btn {
            display: inline-block;
            margin-top: 8px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
        }
        .pix-copy-btn:hover { background: rgba(255,255,255,0.15); }
        .payment-processing {
            text-align: center;
            padding: 30px;
        }
        .payment-processing .spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: #f59e0b;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
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
            <input type="number" id="field-table" class="checkout-input" placeholder="Número da mesa *" min="1" required>
            <p style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-top:-8px;">Informe o número da sua mesa</p>
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
        
        <?php if ($hasStripePayment): ?>
        <!-- Seção de Pagamento Online -->
        <div class="checkout-section" id="payment-section">
            <h3>💳 Forma de Pagamento</h3>
            
            <div class="payment-method-options">
                <button type="button" class="payment-method-btn active" onclick="selectPaymentMethod('card')" id="pm-card">
                    <span class="icon">💳</span>
                    <span class="label">Cartão</span>
                    <span class="desc">Crédito/Débito</span>
                </button>
                <button type="button" class="payment-method-btn" onclick="selectPaymentMethod('pix')" id="pm-pix">
                    <span class="icon">📱</span>
                    <span class="label">Pix</span>
                    <span class="desc">Pagamento instantâneo</span>
                </button>
            </div>
            
            <!-- Formulário de Cartão (Stripe Elements) -->
            <div id="card-payment-form">
                <div id="stripe-card-element"></div>
                <div id="stripe-card-errors" role="alert"></div>
            </div>
            
            <!-- Pix (mostrado após criar PaymentIntent) -->
            <div id="pix-payment-form" style="display:none;">
                <div class="pix-container" id="pix-container">
                    <p class="pix-status">O QR Code será gerado após confirmar o pedido.</p>
                </div>
            </div>
            
            <!-- Processing -->
            <div id="payment-processing" style="display:none;">
                <div class="payment-processing">
                    <div class="spinner"></div>
                    <p>Processando pagamento...</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="checkout-section">
            <h3>Pagamento</h3>
            <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem;">
                💳 Pagamento na entrega ou retirada.
            </p>
        </div>
        <?php endif; ?>
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
            <?= ($mode === 'full' && $hasStripePayment) ? '💳 Pagar e Enviar Pedido' : '📦 Enviar Pedido' ?>
        </button>
    </div>

    <script>
        const MODE = '<?= $mode ?>';
        const RESTAURANT_ID = <?= $restaurant['id'] ?>;
        const RESTAURANT_SLUG = '<?= $restaurant['slug'] ?>';
        const HAS_STRIPE = <?= $hasStripePayment ? 'true' : 'false' ?>;
        const STRIPE_ACCOUNT_ID = '<?= htmlspecialchars($restaurant['stripe_account_id'] ?? '') ?>';
        const PAYMENT_MODEL = '<?= $paymentModel ?>';
        const PLATFORM_FEE = <?= $platformFeePercent ?>;
        const EDGE_URL = '<?= $edgeFunctionBase ?>';

        let selectedPaymentMethod = 'card';
        let stripeInstance = null;
        let stripeElements = null;
        let cardElement = null;
        let currentClientSecret = null;

        // Inicializar Stripe se disponível
        <?php if ($hasStripePayment && $mode === 'full'): ?>
        // A publishable key do Stripe precisa ser configurada
        const STRIPE_PK = '<?= defined("STRIPE_PUBLISHABLE_KEY") ? STRIPE_PUBLISHABLE_KEY : "" ?>';
        if (STRIPE_PK) {
            stripeInstance = Stripe(STRIPE_PK);
            stripeElements = stripeInstance.elements({ locale: 'pt-BR' });
            cardElement = stripeElements.create('card', {
                style: {
                    base: {
                        color: '#ffffff',
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        fontSize: '16px',
                        '::placeholder': { color: 'rgba(255,255,255,0.3)' },
                    },
                    invalid: { color: '#f87171' },
                },
            });
            cardElement.mount('#stripe-card-element');
            cardElement.on('change', (event) => {
                const display = document.getElementById('stripe-card-errors');
                display.textContent = event.error ? event.error.message : '';
            });
        }
        <?php endif; ?>

        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('pm-' + method).classList.add('active');
            document.getElementById('card-payment-form').style.display = method === 'card' ? 'block' : 'none';
            document.getElementById('pix-payment-form').style.display = method === 'pix' ? 'block' : 'none';
        }

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

            if (MODE === 'table') {
                const savedTable = checkoutData.tableNumber || localStorage.getItem('saved_table_' + RESTAURANT_ID) || '';
                if (savedTable) {
                    document.getElementById('field-table').value = savedTable;
                }
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
            if (MODE === 'table' && !payload.table_number) {
                alert('Informe o número da mesa');
                document.getElementById('field-table')?.focus();
                resetBtn();
                return;
            }
            if ((MODE === 'delivery' || MODE === 'full') && (!payload.customer_name || !payload.customer_phone)) {
                alert('Preencha os campos obrigatórios');
                resetBtn();
                return;
            }

            // Se tem pagamento Stripe no modo full
            if (MODE === 'full' && HAS_STRIPE && stripeInstance) {
                try {
                    await processStripePayment(payload);
                } catch (e) {
                    alert('Erro no pagamento: ' + e.message);
                    resetBtn();
                }
                return;
            }

            // Sem pagamento online — enviar pedido direto
            await sendOrder(payload);
        }

        async function processStripePayment(payload) {
            const btn = document.getElementById('submit-btn');
            btn.textContent = 'Processando pagamento...';

            // 1. Criar pedido primeiro (status pending)
            payload.payment_method = selectedPaymentMethod;
            payload.payment_status = 'pending';

            const orderRes = await fetch('/api/orders.php?action=create', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const orderData = await orderRes.json();

            if (!orderData.success || !orderData.order) {
                throw new Error(orderData.error || 'Erro ao criar pedido');
            }

            const orderId = orderData.order.id;
            const orderToken = orderData.order.token;

            // 2. Criar PaymentIntent via Edge Function
            const piRes = await fetch(EDGE_URL + '/stripe-create-payment', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    order_id: orderId,
                    restaurant_id: RESTAURANT_ID,
                    amount: checkoutData.total,
                    payment_method_type: selectedPaymentMethod,
                    stripe_account_id: STRIPE_ACCOUNT_ID,
                    payment_model: PAYMENT_MODEL,
                    platform_fee_percent: PLATFORM_FEE,
                    customer_name: payload.customer_name,
                    customer_email: document.getElementById('field-email')?.value || '',
                })
            });
            const piData = await piRes.json();

            if (!piData.success) {
                throw new Error(piData.error || 'Erro ao criar pagamento');
            }

            currentClientSecret = piData.client_secret;

            // 3. Confirmar pagamento conforme método
            if (selectedPaymentMethod === 'card') {
                await confirmCardPayment(orderId, orderToken);
            } else {
                await confirmPixPayment(orderId, orderToken);
            }
        }

        async function confirmCardPayment(orderId, orderToken) {
            document.getElementById('payment-processing').style.display = 'block';
            document.getElementById('card-payment-form').style.display = 'none';

            const { error, paymentIntent } = await stripeInstance.confirmCardPayment(
                currentClientSecret,
                { payment_method: { card: cardElement } }
            );

            if (error) {
                document.getElementById('payment-processing').style.display = 'none';
                document.getElementById('card-payment-form').style.display = 'block';
                throw new Error(error.message);
            }

            if (paymentIntent.status === 'succeeded') {
                // Atualizar status do pedido para paid
                await fetch('/api/orders.php?action=update_payment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        order_id: orderId,
                        payment_status: 'paid',
                        stripe_payment_id: paymentIntent.id,
                    })
                });

                showSuccess(orderId, orderToken);
            }
        }

        async function confirmPixPayment(orderId, orderToken) {
            document.getElementById('payment-processing').style.display = 'block';
            document.getElementById('pix-payment-form').style.display = 'none';

            const { error, paymentIntent } = await stripeInstance.confirmPixPayment(
                currentClientSecret,
                { payment_method: {} }
            );

            document.getElementById('payment-processing').style.display = 'none';

            if (error) {
                throw new Error(error.message);
            }

            if (paymentIntent.status === 'requires_action' && paymentIntent.next_action?.pix_display_qr_code) {
                const pixData = paymentIntent.next_action.pix_display_qr_code;
                
                document.getElementById('pix-payment-form').style.display = 'block';
                document.getElementById('pix-container').innerHTML = `
                    <img src="${pixData.image_url_png}" alt="QR Code Pix">
                    <p style="font-weight:600; margin-bottom: 8px;">Escaneie o QR Code para pagar</p>
                    <p class="pix-status">Aguardando pagamento...</p>
                    ${pixData.data ? `
                        <button class="pix-copy-btn" onclick="copyPix('${pixData.data}')">
                            📋 Copiar código Pix
                        </button>
                    ` : ''}
                    <p style="font-size:0.7rem; color:rgba(255,255,255,0.3); margin-top:12px;">
                        O QR Code expira em ${pixData.expires_at ? 'alguns minutos' : '30 minutos'}
                    </p>
                `;

                // Ocultar botão de envio enquanto aguarda Pix
                document.getElementById('checkout-footer').style.display = 'none';

                // Poll para verificar pagamento
                pollPixPayment(orderId, orderToken);
            } else if (paymentIntent.status === 'succeeded') {
                await fetch('/api/orders.php?action=update_payment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        order_id: orderId,
                        payment_status: 'paid',
                        stripe_payment_id: paymentIntent.id,
                    })
                });
                showSuccess(orderId, orderToken);
            }
        }

        async function pollPixPayment(orderId, orderToken) {
            const maxAttempts = 60; // 5 minutos
            let attempts = 0;

            const poll = setInterval(async () => {
                attempts++;
                if (attempts > maxAttempts) {
                    clearInterval(poll);
                    document.querySelector('.pix-status').textContent = 'QR Code expirado. Tente novamente.';
                    return;
                }

                try {
                    const { paymentIntent } = await stripeInstance.retrievePaymentIntent(currentClientSecret);
                    
                    if (paymentIntent.status === 'succeeded') {
                        clearInterval(poll);
                        
                        await fetch('/api/orders.php?action=update_payment', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                order_id: orderId,
                                payment_status: 'paid',
                                stripe_payment_id: paymentIntent.id,
                            })
                        });

                        showSuccess(orderId, orderToken);
                    }
                } catch (e) {
                    console.error('Poll error:', e);
                }
            }, 5000); // Check every 5 seconds
        }

        function copyPix(code) {
            navigator.clipboard.writeText(code).then(() => {
                const btn = event.target;
                btn.textContent = '✅ Copiado!';
                setTimeout(() => btn.textContent = '📋 Copiar código Pix', 2000);
            });
        }

        async function sendOrder(payload) {
            try {
                const res = await fetch('/api/orders.php?action=create', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success && data.order) {
                    showSuccess(data.order.id, data.order.token);
                } else {
                    alert(data.error || 'Erro ao enviar pedido');
                    resetBtn();
                }
            } catch (e) {
                alert('Erro de conexão. Tente novamente.');
                resetBtn();
            }
        }

        function showSuccess(orderId, orderToken) {
            // Salvar token para rastreamento
            localStorage.setItem('active_order_' + RESTAURANT_ID, JSON.stringify({
                token: orderToken,
                orderId: orderId,
                createdAt: new Date().toISOString()
            }));

            // Salvar mesa se aplicável
            const tableNum = document.getElementById('field-table')?.value;
            if (tableNum) {
                localStorage.setItem('saved_table_' + RESTAURANT_ID, tableNum);
            }

            // Limpar carrinho
            localStorage.removeItem('checkout_data');
            localStorage.removeItem('cart_' + RESTAURANT_ID);

            // Mostrar sucesso
            document.getElementById('checkout-content').style.display = 'none';
            document.getElementById('checkout-footer').style.display = 'none';
            document.getElementById('checkout-success').style.display = 'block';
            document.getElementById('success-order-id').textContent = 'Pedido #' + orderId;
            document.getElementById('success-track-link').href = '/pedido/' + orderToken;
        }

        function resetBtn() {
            const btn = document.getElementById('submit-btn');
            btn.disabled = false;
            if (MODE === 'full' && HAS_STRIPE) {
                btn.textContent = '💳 Pagar e Enviar Pedido';
            } else {
                btn.textContent = '📦 Enviar Pedido';
            }
        }
    </script>
</body>
</html>
