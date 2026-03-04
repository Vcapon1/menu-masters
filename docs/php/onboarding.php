<?php
/**
 * CARDÁPIO FLORIPA - Formulário de Onboarding
 * 
 * Página pós-pagamento para o restaurante enviar materiais.
 * Requer login (status = ativo)
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

// Verificar autenticação do restaurante
if (!isset($_SESSION['restaurant_admin'])) {
    header('Location: /admin/login.php');
    exit;
}

$restaurantId = $_SESSION['restaurant_admin']['id'];
$restaurant = getRestaurantById($restaurantId);

if (!$restaurant) {
    header('Location: /admin/login.php');
    exit;
}

// Verificar se restaurante está ativo
if ($restaurant['status'] !== 'active' && $restaurant['status'] !== 'ativo') {
    header('Location: /admin/');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Upload do cardápio atual
        $menuFile = null;
        if (isset($_FILES['menu_file']) && $_FILES['menu_file']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ['application/pdf']);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['menu_file']['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Tipo de arquivo não permitido para o cardápio. Use PDF ou imagem.');
            }
            if ($_FILES['menu_file']['size'] > 10 * 1024 * 1024) {
                throw new Exception('Arquivo do cardápio muito grande. Máximo: 10MB.');
            }
            
            $ext = pathinfo($_FILES['menu_file']['name'], PATHINFO_EXTENSION);
            $filename = 'cardapio_' . $restaurantId . '_' . time() . '.' . $ext;
            $uploadPath = UPLOAD_DIR . 'onboarding/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            move_uploaded_file($_FILES['menu_file']['tmp_name'], $uploadPath . $filename);
            $menuFile = UPLOAD_URL . 'onboarding/' . $filename;
        }

        // Upload do logotipo
        $logoFile = null;
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $logoFile = uploadImage($_FILES['logo_file'], 'logos');
        }

        // Upload de fotos (múltiplas)
        $photos = [];
        if (isset($_FILES['photos'])) {
            for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['photos']['name'][$i],
                        'type' => $_FILES['photos']['type'][$i],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                        'error' => $_FILES['photos']['error'][$i],
                        'size' => $_FILES['photos']['size'][$i],
                    ];
                    $photos[] = uploadImage($file, 'onboarding');
                }
            }
        }

        // Dados do formulário
        $neighborhoods = sanitize($_POST['delivery_neighborhoods'] ?? '');
        $deliveryFee = floatval($_POST['delivery_fee'] ?? 0);
        $openingHours = $_POST['opening_hours'] ?? '{}';
        $whatsapp = sanitize($_POST['whatsapp'] ?? '');
        $wantsOnlinePayment = isset($_POST['wants_online_payment']) ? 1 : 0;

        // Atualizar restaurante
        $updateFields = [];
        $updateParams = ['id' => $restaurantId];

        if ($menuFile) {
            $updateFields[] = "onboarding_menu_file = :menu_file";
            $updateParams['menu_file'] = $menuFile;
        }
        if ($logoFile) {
            $updateFields[] = "logo = :logo";
            $updateParams['logo'] = $logoFile;
        }
        if (!empty($photos)) {
            $updateFields[] = "onboarding_photos = :photos";
            $updateParams['photos'] = json_encode($photos);
        }

        $updateFields[] = "delivery_neighborhoods = :neighborhoods";
        $updateParams['neighborhoods'] = $neighborhoods;

        $updateFields[] = "delivery_fee = :delivery_fee";
        $updateParams['delivery_fee'] = $deliveryFee;

        $updateFields[] = "opening_hours_json = :opening_hours";
        $updateParams['opening_hours'] = $openingHours;

        $updateFields[] = "whatsapp = :whatsapp";
        $updateParams['whatsapp'] = $whatsapp;

        $updateFields[] = "status_onboarding = 'completo'";

        $sql = "UPDATE restaurants SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = db()->prepare($sql);
        $stmt->execute($updateParams);

        $message = '✅ Onboarding completo! Seu cardápio será configurado em até 7 dias úteis.';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Recarregar dados
$restaurant = getRestaurantById($restaurantId);
$isComplete = ($restaurant['status_onboarding'] ?? '') === 'completo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding - <?= htmlspecialchars($restaurant['name']) ?> | Cardápio Floripa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0c0a09 0%, #1c1917 100%); }
        .form-card { background: rgba(31, 41, 55, 0.8); backdrop-filter: blur(10px); }
        .accent-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
    </style>
</head>
<body class="text-white min-h-screen p-4">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8 pt-8">
            <h1 class="text-3xl font-bold mb-2">🍽️ Bem-vindo ao Cardápio Floripa!</h1>
            <p class="text-gray-400">Envie os materiais do seu restaurante para configurarmos tudo</p>
        </div>

        <?php if ($isComplete): ?>
            <div class="bg-green-900/50 border border-green-600 rounded-xl p-8 text-center">
                <p class="text-4xl mb-4">✅</p>
                <h2 class="text-2xl font-bold text-green-400 mb-2">Onboarding Completo!</h2>
                <p class="text-gray-300">Seus materiais foram recebidos. O prazo de 7 dias úteis para configuração do cardápio começa agora.</p>
                <a href="/admin/" class="inline-block mt-6 accent-gradient px-8 py-3 rounded-lg font-medium hover:opacity-90">
                    Ir para o Painel →
                </a>
            </div>
        <?php else: ?>

            <?php if ($message): ?>
                <div class="bg-green-900/50 border border-green-600 rounded-lg p-4 mb-6"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="form-card rounded-xl border border-gray-700 p-6 space-y-6">
                
                <!-- Upload do cardápio -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">📋 Cardápio Atual</h3>
                    <label class="block text-sm mb-1">Envie uma foto ou PDF do seu cardápio atual *</label>
                    <input type="file" name="menu_file" accept="image/*,application/pdf" required
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3">
                    <p class="text-xs text-gray-500 mt-1">PDF ou imagem, máximo 10MB</p>
                </div>

                <!-- Upload do logo -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">🎨 Logotipo</h3>
                    <label class="block text-sm mb-1">Envie o logotipo do restaurante *</label>
                    <input type="file" name="logo_file" accept="image/*" required
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3">
                    <p class="text-xs text-gray-500 mt-1">Preferencialmente PNG com fundo transparente</p>
                </div>

                <!-- Fotos -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">📸 Fotos (opcional)</h3>
                    <label class="block text-sm mb-1">Fotos dos pratos, ambiente, etc.</label>
                    <input type="file" name="photos[]" accept="image/*" multiple
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3">
                    <p class="text-xs text-gray-500 mt-1">Até 10 fotos, máximo 5MB cada</p>
                </div>

                <!-- Delivery -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">🚚 Entrega</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Bairros atendidos</label>
                            <textarea name="delivery_neighborhoods" rows="3"
                                      class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none"
                                      placeholder="Liste os bairros separados por vírgula&#10;Ex: Centro, Trindade, Itacorubi, Lagoa da Conceição"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Taxa de entrega (R$)</label>
                            <input type="number" name="delivery_fee" step="0.50" min="0" value="0"
                                   class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Horários -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">🕐 Horário de Funcionamento</h3>
                    <div class="space-y-2" id="hours-container">
                        <?php
                        $days = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
                        foreach ($days as $i => $day): ?>
                        <div class="grid grid-cols-4 gap-2 items-center">
                            <label class="text-sm"><?= $day ?></label>
                            <input type="time" name="hours[<?= $i ?>][open]" value="11:00"
                                   class="bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm focus:outline-none">
                            <input type="time" name="hours[<?= $i ?>][close]" value="23:00"
                                   class="bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm focus:outline-none">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="hours[<?= $i ?>][closed]" class="rounded bg-gray-700 border-gray-600">
                                Fechado
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="opening_hours" id="opening-hours-json">
                </div>

                <!-- WhatsApp -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">💬 WhatsApp</h3>
                    <div>
                        <label class="block text-sm mb-1">Número do WhatsApp para pedidos</label>
                        <input type="text" name="whatsapp" placeholder="(48) 99999-9999"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none"
                               maxlength="15">
                    </div>
                </div>

                <!-- Pagamento online -->
                <div class="bg-gray-900/50 rounded-lg p-4 border border-gray-700">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="wants_online_payment"
                               class="mt-1 rounded bg-gray-700 border-gray-600 text-orange-500 focus:ring-orange-500">
                        <div>
                            <span class="font-medium">Desejo ativar pagamento online agora</span>
                            <p class="text-xs text-gray-400 mt-1">Receba pagamentos via Pix e Cartão diretamente no cardápio. Configuraremos para você.</p>
                        </div>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" onclick="prepareHours()"
                        class="w-full accent-gradient text-white font-bold py-4 rounded-xl text-lg hover:opacity-90 transition">
                    ✅ Enviar Materiais
                </button>

                <p class="text-center text-xs text-gray-500">
                    O prazo de 7 dias úteis para configuração do cardápio começa após o envio completo dos materiais.
                </p>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function prepareHours() {
            const days = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
            const hours = {};
            document.querySelectorAll('#hours-container > div').forEach((row, i) => {
                const inputs = row.querySelectorAll('input[type="time"]');
                const closed = row.querySelector('input[type="checkbox"]');
                hours[days[i]] = {
                    open: inputs[0]?.value || '',
                    close: inputs[1]?.value || '',
                    closed: closed?.checked || false
                };
            });
            document.getElementById('opening-hours-json').value = JSON.stringify(hours);
        }
    </script>
</body>
</html>
