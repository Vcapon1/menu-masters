<?php
/**
 * CARDÁPIO FLORIPA - QR Code
 * 
 * Geração e download do QR Code do cardápio.
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
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($menuUrl);
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
                <h1 class="font-bold">QR Code</h1>
            </div>
        </div>
    </nav>
    
    <main class="max-w-2xl mx-auto px-4 py-8">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 text-center">
            <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($restaurant['name']) ?></h2>
            <p class="text-gray-400 mb-6">Escaneie o código para acessar o cardápio</p>
            
            <!-- QR Code -->
            <div class="bg-white rounded-lg p-4 inline-block mb-6">
                <img 
                    src="<?= $qrCodeUrl ?>" 
                    alt="QR Code do Cardápio"
                    class="w-64 h-64"
                >
            </div>
            
            <!-- URL -->
            <div class="mb-6">
                <p class="text-sm text-gray-400 mb-2">Link do cardápio:</p>
                <div class="flex items-center gap-2 justify-center">
                    <input 
                        type="text" 
                        value="<?= htmlspecialchars($menuUrl) ?>" 
                        readonly
                        id="menuUrl"
                        class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-center w-full max-w-md"
                    >
                    <button 
                        onclick="copyUrl()"
                        class="bg-blue-600 hover:bg-blue-700 rounded-lg px-4 py-2 transition"
                    >
                        Copiar
                    </button>
                </div>
            </div>
            
            <!-- Downloads -->
            <div class="flex gap-4 justify-center">
                <a 
                    href="<?= $qrCodeUrl ?>" 
                    download="qrcode-<?= $restaurant['slug'] ?>.png"
                    class="bg-green-600 hover:bg-green-700 rounded-lg px-6 py-3 font-medium transition"
                >
                    📥 Baixar PNG
                </a>
                <a 
                    href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&format=svg&data=<?= urlencode($menuUrl) ?>" 
                    download="qrcode-<?= $restaurant['slug'] ?>.svg"
                    class="bg-purple-600 hover:bg-purple-700 rounded-lg px-6 py-3 font-medium transition"
                >
                    📥 Baixar SVG
                </a>
            </div>
        </div>
        
        <!-- Dicas -->
        <div class="mt-8 bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="font-bold mb-4">💡 Dicas de uso</h3>
            <ul class="space-y-2 text-gray-300 text-sm">
                <li>• Imprima o QR Code e coloque nas mesas do seu estabelecimento</li>
                <li>• Use o formato SVG para impressões em alta qualidade</li>
                <li>• Adicione o QR Code em materiais de divulgação (flyers, cardápios físicos)</li>
                <li>• O cliente só precisa apontar a câmera do celular para acessar</li>
            </ul>
        </div>
    </main>
    
    <script>
        function copyUrl() {
            const input = document.getElementById('menuUrl');
            input.select();
            document.execCommand('copy');
            alert('Link copiado!');
        }
    </script>
</body>
</html>
