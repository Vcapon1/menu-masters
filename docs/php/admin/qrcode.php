<?php
/**
 * PREMIUM MENU - QR Code com Seletor de Modo
 * 
 * Gera QR Codes dinâmicos baseados no modo de carrinho selecionado.
 * A mesa é informada pelo cliente no momento do pedido.
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

$menuUrl = APP_URL . '/' . $restaurant['slug'];

// Buscar modos de carrinho habilitados
$cartModes = getRestaurantCartModes($restaurantId);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-gray-400 hover:text-white">← Dashboard</a>
                <h1 class="font-bold">QR Code do Cardápio</h1>
            </div>
        </div>
    </nav>
    
    <main class="max-w-2xl mx-auto px-4 py-8">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8">
            <h2 class="text-xl font-bold mb-2"><?= htmlspecialchars($restaurant['name']) ?></h2>
            <p class="text-gray-400 mb-6">Selecione o tipo de link para gerar o QR Code</p>
            
            <!-- Seletor de Modo -->
            <div class="mb-6">
                <h3 class="text-sm text-gray-400 uppercase tracking-wide mb-3">Tipo de Link</h3>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 bg-gray-700 rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-600 transition">
                        <input type="radio" name="cart_mode" value="" checked onchange="updateQRCode()" class="text-blue-500">
                        <div>
                            <span class="font-medium">Apenas cardápio</span>
                            <p class="text-xs text-gray-400">Sem sistema de pedidos</p>
                        </div>
                    </label>
                    <?php foreach ($cartModes as $cm): ?>
                    <label class="flex items-center gap-3 bg-gray-700 rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-600 transition">
                        <input type="radio" name="cart_mode" value="<?= htmlspecialchars($cm['slug']) ?>" onchange="updateQRCode()" class="text-blue-500">
                        <div>
                            <span class="font-medium"><?= htmlspecialchars($cm['name']) ?></span>
                            <?php if ($cm['slug'] === 'table'): ?>
                                <p class="text-xs text-gray-400">Cliente informa a mesa no pedido</p>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- URL Gerada -->
            <div class="mb-6">
                <h3 class="text-sm text-gray-400 uppercase tracking-wide mb-2">URL Gerada</h3>
                <div class="flex items-center gap-2">
                    <input 
                        type="text" 
                        id="menuUrl"
                        value="<?= htmlspecialchars($menuUrl) ?>" 
                        readonly
                        class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-sm w-full"
                    >
                    <button 
                        onclick="copyUrl()"
                        class="bg-blue-600 hover:bg-blue-700 rounded-lg px-4 py-2 transition text-sm whitespace-nowrap"
                    >
                        Copiar
                    </button>
                </div>
            </div>

            <!-- QR Code Preview -->
            <div class="text-center mb-6">
                <div class="bg-white rounded-lg p-4 inline-block">
                    <img 
                        id="qrPreview"
                        src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= urlencode($menuUrl) ?>" 
                        alt="QR Code do Cardápio"
                        class="w-64 h-64"
                    >
                </div>
            </div>
            
            <!-- Downloads -->
            <div class="flex gap-4 justify-center">
                <a 
                    id="downloadPng"
                    href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= urlencode($menuUrl) ?>" 
                    download="qrcode-<?= $restaurant['slug'] ?>.png"
                    class="bg-green-600 hover:bg-green-700 rounded-lg px-6 py-3 font-medium transition text-sm"
                >
                    📥 Baixar PNG
                </a>
                <a 
                    id="downloadSvg"
                    href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&format=svg&data=<?= urlencode($menuUrl) ?>" 
                    download="qrcode-<?= $restaurant['slug'] ?>.svg"
                    class="bg-purple-600 hover:bg-purple-700 rounded-lg px-6 py-3 font-medium transition text-sm"
                >
                    📥 Baixar SVG
                </a>
            </div>
        </div>
        
        <!-- Dicas -->
        <div class="mt-8 bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="font-bold mb-4">💡 Dicas de uso</h3>
            <ul class="space-y-2 text-gray-300 text-sm">
                <li>• <strong>Mesa:</strong> Um único QR Code para todas as mesas — o cliente informa o número no pedido</li>
                <li>• <strong>WhatsApp:</strong> Use em flyers e redes sociais para receber pedidos direto</li>
                <li>• <strong>Entrega:</strong> Para pedidos com entrega no endereço do cliente</li>
                <li>• Use o formato SVG para impressões em alta qualidade</li>
                <li>• O cliente só precisa apontar a câmera do celular para acessar</li>
            </ul>
        </div>
    </main>
    
    <script>
        const BASE_URL = '<?= $menuUrl ?>';
        const SLUG = '<?= $restaurant['slug'] ?>';

        function updateQRCode() {
            const selected = document.querySelector('input[name="cart_mode"]:checked').value;
            let url = BASE_URL;
            
            if (selected) {
                url += '?cart=' + selected;
            }

            document.getElementById('menuUrl').value = url;
            
            const encodedUrl = encodeURIComponent(url);
            document.getElementById('qrPreview').src = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' + encodedUrl;
            document.getElementById('downloadPng').href = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' + encodedUrl;
            document.getElementById('downloadSvg').href = 'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&format=svg&data=' + encodedUrl;
            
            // Update download filenames
            const suffix = selected ? '-' + selected : '';
            document.getElementById('downloadPng').download = 'qrcode-' + SLUG + suffix + '.png';
            document.getElementById('downloadSvg').download = 'qrcode-' + SLUG + suffix + '.svg';
        }

        function copyUrl() {
            const input = document.getElementById('menuUrl');
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                const btn = event.target;
                btn.textContent = '✓ Copiado!';
                setTimeout(() => btn.textContent = 'Copiar', 2000);
            }).catch(() => {
                document.execCommand('copy');
            });
        }
    </script>
</body>
</html>
