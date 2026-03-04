<?php
/**
 * CARDÁPIO FLORIPA - Checkout de Pagamento do Plano via Asaas
 * 
 * Página de pagamento: Pix, Boleto ou Cartão de Crédito
 * URL: /pagamento-plano/{restaurant_id}
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

// Extrair restaurant_id da URL
$requestUri = $_SERVER['REQUEST_URI'];
$restaurantId = 0;
if (preg_match('#/pagamento-plano/(\d+)#', $requestUri, $matches)) {
    $restaurantId = (int)$matches[1];
}

if (!$restaurantId) {
    http_response_code(404);
    include __DIR__ . '/templates/404.php';
    exit;
}

$restaurant = getRestaurantById($restaurantId);
if (!$restaurant || $restaurant['status'] !== 'aguardando_pagamento') {
    $pageTitle = "Pagamento não disponível";
    $pageMessage = "Este restaurante não tem pagamento pendente ou já foi ativado.";
    include __DIR__ . '/templates/expired.php';
    exit;
}

$planValue = $restaurant['plan_value'] ?? 0;
$planName = $restaurant['plan_name'] . ' - Plano Anual';

// URL base da edge function
$edgeFunctionBase = defined('EDGE_FUNCTION_BASE') ? EDGE_FUNCTION_BASE : 'https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - <?= htmlspecialchars($restaurant['name']) ?> | Cardápio Floripa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0c0a09 0%, #1c1917 100%); }
        .form-card { background: rgba(31, 41, 55, 0.8); backdrop-filter: blur(10px); }
        .accent-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .payment-option { transition: all 0.2s; }
        .payment-option.selected { border-color: #f97316; background: rgba(249, 115, 22, 0.1); }
        .spinner { border: 3px solid #374151; border-top-color: #f97316; border-radius: 50%; width: 24px; height: 24px; animation: spin 0.8s linear infinite; display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold mb-2">🍽️ Cardápio Floripa</h1>
            <p class="text-gray-400">Pagamento do plano</p>
        </div>

        <!-- Resumo do pedido -->
        <div class="accent-gradient rounded-xl p-4 mb-6">
            <h2 class="text-xl font-bold"><?= htmlspecialchars($restaurant['name']) ?></h2>
            <p class="text-sm opacity-90"><?= htmlspecialchars($planName) ?></p>
            <p class="text-2xl font-bold mt-2">R$ <?= number_format($planValue, 2, ',', '.') ?></p>
        </div>

        <!-- Seleção de forma de pagamento -->
        <div class="form-card rounded-xl border border-gray-700 p-6" id="payment-section">
            <h3 class="text-lg font-semibold mb-4">Escolha a forma de pagamento:</h3>

            <div class="space-y-3 mb-6">
                <button onclick="selectPayment('PIX')" 
                        class="payment-option w-full flex items-center gap-4 p-4 rounded-lg border border-gray-600 hover:border-orange-500 text-left"
                        id="opt-pix">
                    <span class="text-2xl">📱</span>
                    <div>
                        <span class="font-medium">Pix</span>
                        <p class="text-xs text-gray-400">Pagamento instantâneo via QR Code</p>
                    </div>
                </button>

                <button onclick="selectPayment('BOLETO')" 
                        class="payment-option w-full flex items-center gap-4 p-4 rounded-lg border border-gray-600 hover:border-orange-500 text-left"
                        id="opt-boleto">
                    <span class="text-2xl">📄</span>
                    <div>
                        <span class="font-medium">Boleto Bancário</span>
                        <p class="text-xs text-gray-400">Vencimento em 3 dias úteis</p>
                    </div>
                </button>

                <button onclick="selectPayment('CREDIT_CARD')" 
                        class="payment-option w-full flex items-center gap-4 p-4 rounded-lg border border-gray-600 hover:border-orange-500 text-left"
                        id="opt-card">
                    <span class="text-2xl">💳</span>
                    <div>
                        <span class="font-medium">Cartão de Crédito</span>
                        <p class="text-xs text-gray-400">Parcele em até 12x</p>
                    </div>
                </button>
            </div>

            <!-- Parcelamento (apenas cartão) -->
            <div id="installment-section" class="hidden mb-6">
                <label class="block text-sm mb-2">Parcelas:</label>
                <select id="installment-count" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3">
                    <?php for ($i = 1; $i <= 12; $i++): 
                        $parcela = $planValue / $i;
                    ?>
                    <option value="<?= $i ?>"><?= $i ?>x de R$ <?= number_format($parcela, 2, ',', '.') ?><?= $i === 1 ? ' (à vista)' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button onclick="processPayment()" id="btn-pay"
                    class="w-full accent-gradient text-white font-bold py-4 rounded-xl text-lg hover:opacity-90 transition disabled:opacity-50"
                    disabled>
                Pagar
            </button>
        </div>

        <!-- Loading -->
        <div id="loading-section" class="hidden form-card rounded-xl border border-gray-700 p-8 text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-300">Processando pagamento...</p>
        </div>

        <!-- Resultado PIX -->
        <div id="pix-section" class="hidden form-card rounded-xl border border-gray-700 p-6 text-center">
            <h3 class="text-lg font-semibold mb-4 text-green-400">📱 Pague via Pix</h3>
            <div class="bg-white p-4 rounded-lg inline-block mb-4">
                <img id="pix-qrcode" src="" alt="QR Code Pix" class="w-48 h-48">
            </div>
            <div class="mb-4">
                <label class="block text-sm mb-1">Ou copie o código Pix:</label>
                <div class="flex gap-2">
                    <input type="text" id="pix-copy" readonly class="flex-1 bg-gray-800 border border-gray-600 rounded px-3 py-2 text-xs">
                    <button onclick="copyPix()" class="bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded text-sm">Copiar</button>
                </div>
            </div>
            <p class="text-xs text-gray-400">Após o pagamento, a confirmação será automática.</p>
        </div>

        <!-- Resultado Boleto -->
        <div id="boleto-section" class="hidden form-card rounded-xl border border-gray-700 p-6 text-center">
            <h3 class="text-lg font-semibold mb-4 text-blue-400">📄 Boleto Gerado</h3>
            <p class="text-gray-300 mb-4">Seu boleto foi gerado com sucesso!</p>
            <a id="boleto-link" href="#" target="_blank"
               class="inline-block accent-gradient px-8 py-3 rounded-lg font-medium hover:opacity-90">
                Abrir Boleto →
            </a>
            <p class="text-xs text-gray-400 mt-4">Vencimento em 3 dias úteis. Após o pagamento, a confirmação pode levar até 2 dias úteis.</p>
        </div>

        <!-- Resultado Cartão Sucesso -->
        <div id="success-section" class="hidden form-card rounded-xl border border-gray-700 p-8 text-center">
            <p class="text-4xl mb-4">✅</p>
            <h3 class="text-2xl font-bold text-green-400 mb-2">Pagamento Confirmado!</h3>
            <p class="text-gray-300 mb-6">Seu plano foi ativado com sucesso. Agora vamos configurar seu cardápio.</p>
            <a href="/admin/login.php" class="inline-block accent-gradient px-8 py-3 rounded-lg font-medium hover:opacity-90">
                Acessar Painel →
            </a>
        </div>

        <!-- Erro -->
        <div id="error-section" class="hidden bg-red-900/50 border border-red-600 rounded-xl p-6 text-center">
            <p class="text-4xl mb-4">❌</p>
            <p id="error-message" class="text-red-400 mb-4"></p>
            <button onclick="location.reload()" class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">Tentar novamente</button>
        </div>
    </div>

    <script>
        const EDGE_URL = '<?= $edgeFunctionBase ?>/asaas-checkout-plan';
        const RESTAURANT_ID = <?= $restaurantId ?>;
        const RESTAURANT_NAME = <?= json_encode($restaurant['name']) ?>;
        const RESTAURANT_EMAIL = <?= json_encode($restaurant['email']) ?>;
        const RESTAURANT_CNPJ = <?= json_encode($restaurant['cnpj'] ?? '') ?>;
        const RESTAURANT_PHONE = <?= json_encode($restaurant['phone'] ?? '') ?>;
        const PLAN_VALUE = <?= $planValue ?>;
        const PLAN_NAME = <?= json_encode($planName) ?>;

        let selectedMethod = null;
        let asaasCustomerId = null;

        function selectPayment(method) {
            selectedMethod = method;
            document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
            document.getElementById('opt-' + method.toLowerCase().replace('credit_card', 'card')).classList.add('selected');
            document.getElementById('installment-section').classList.toggle('hidden', method !== 'CREDIT_CARD');
            document.getElementById('btn-pay').disabled = false;
        }

        async function processPayment() {
            if (!selectedMethod) return;
            
            showSection('loading');

            try {
                // 1. Criar cliente no Asaas (se ainda não criou)
                if (!asaasCustomerId) {
                    const custResp = await fetch(EDGE_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'create_customer',
                            restaurant_id: RESTAURANT_ID,
                            restaurant_name: RESTAURANT_NAME,
                            restaurant_email: RESTAURANT_EMAIL,
                            restaurant_cnpj: RESTAURANT_CNPJ,
                            restaurant_phone: RESTAURANT_PHONE,
                        })
                    });
                    const custData = await custResp.json();
                    if (!custData.success) throw new Error(custData.error || 'Erro ao criar cliente');
                    asaasCustomerId = custData.customer_id;
                }

                // 2. Criar cobrança
                const installments = selectedMethod === 'CREDIT_CARD' 
                    ? parseInt(document.getElementById('installment-count').value) 
                    : 1;

                const payResp = await fetch(EDGE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_payment',
                        asaas_customer_id: asaasCustomerId,
                        restaurant_id: RESTAURANT_ID,
                        plan_value: PLAN_VALUE,
                        plan_name: PLAN_NAME,
                        billing_type: selectedMethod,
                        installment_count: installments,
                    })
                });
                const payData = await payResp.json();
                if (!payData.success) throw new Error(payData.error || 'Erro ao criar cobrança');

                // 3. Mostrar resultado por tipo
                if (selectedMethod === 'PIX') {
                    if (payData.pix_qr_code) {
                        document.getElementById('pix-qrcode').src = 'data:image/png;base64,' + payData.pix_qr_code;
                    }
                    document.getElementById('pix-copy').value = payData.pix_copy_paste || '';
                    showSection('pix');
                } else if (selectedMethod === 'BOLETO') {
                    const link = payData.bank_slip_url || payData.invoice_url || '#';
                    document.getElementById('boleto-link').href = link;
                    showSection('boleto');
                } else if (selectedMethod === 'CREDIT_CARD') {
                    // Para cartão, redirecionar para invoice_url do Asaas (checkout seguro)
                    if (payData.invoice_url) {
                        window.location.href = payData.invoice_url;
                    } else if (payData.status === 'CONFIRMED' || payData.status === 'RECEIVED') {
                        showSection('success');
                    } else {
                        // Mostrar link de pagamento
                        if (payData.invoice_url) {
                            window.location.href = payData.invoice_url;
                        } else {
                            showSection('success');
                        }
                    }
                }

            } catch (e) {
                document.getElementById('error-message').textContent = e.message;
                showSection('error');
            }
        }

        function showSection(name) {
            ['payment', 'loading', 'pix', 'boleto', 'success', 'error'].forEach(s => {
                const el = document.getElementById(s + '-section');
                if (el) el.classList.toggle('hidden', s !== name);
            });
        }

        function copyPix() {
            const input = document.getElementById('pix-copy');
            input.select();
            document.execCommand('copy');
            alert('Código Pix copiado!');
        }
    </script>
</body>
</html>
