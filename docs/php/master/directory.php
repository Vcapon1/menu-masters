<?php
/**
 * CARDÁPIO FLORIPA - Gerenciar Diretório
 * 
 * CRUD do guia gastronômico.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['master_admin'])) {
    header('Location: login.php');
    exit;
}

$admin = $_SESSION['master_admin'];

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $name = sanitize($_POST['name'] ?? '');
                $slug = generateSlug($name);
                $description = sanitize($_POST['description'] ?? '');
                $address = sanitize($_POST['address'] ?? '');
                $neighborhood = sanitize($_POST['neighborhood'] ?? '');
                $phone = sanitize($_POST['phone'] ?? '');
                $cuisineTypes = json_encode(array_filter(explode(',', $_POST['cuisine_types'] ?? '')));
                $priceRange = sanitize($_POST['price_range'] ?? '$$');
                $isClient = isset($_POST['is_client']) ? 1 : 0;
                $linkedRestaurantId = !empty($_POST['linked_restaurant_id']) ? (int)$_POST['linked_restaurant_id'] : null;
                
                if (empty($name)) {
                    throw new Exception('Nome é obrigatório.');
                }
                
                $sql = "INSERT INTO directory_restaurants (name, slug, description, address, neighborhood, phone, cuisine_types, price_range, is_client, linked_restaurant_id, city, is_featured) VALUES (:name, :slug, :desc, :addr, :nb, :phone, :ct, :pr, :ic, :lri, 'Florianópolis', 0)";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'desc' => $description,
                    'addr' => $address,
                    'nb' => $neighborhood,
                    'phone' => $phone,
                    'ct' => $cuisineTypes,
                    'pr' => $priceRange,
                    'ic' => $isClient,
                    'lri' => $linkedRestaurantId
                ]);
                
                $message = 'Restaurante adicionado ao diretório!';
                $messageType = 'success';
                break;
                
            case 'update_status':
                $id = (int)($_POST['id'] ?? 0);
                $status = sanitize($_POST['status'] ?? 'pending');
                
                $sql = "UPDATE directory_restaurants SET status = :status WHERE id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute(['status' => $status, 'id' => $id]);
                
                $message = 'Status atualizado!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                
                $sql = "DELETE FROM directory_restaurants WHERE id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute(['id' => $id]);
                
                $message = 'Restaurante removido do diretório!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Filtros
$statusFilter = $_GET['status'] ?? 'all';

// Buscar restaurantes do diretório
$sql = "SELECT * FROM directory_restaurants";
if ($statusFilter !== 'all') {
    $sql .= " WHERE status = :status";
}
$sql .= " ORDER BY is_featured DESC, name ASC";

$stmt = db()->prepare($sql);
if ($statusFilter !== 'all') {
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt->execute();
}
$directoryRestaurants = $stmt->fetchAll();

// Buscar restaurantes clientes para vincular (com tratamento de erro)
$clientRestaurants = [];
try {
    $stmt = db()->query("SELECT id, name FROM restaurants WHERE status = 'active' ORDER BY name ASC");
    $clientRestaurants = $stmt->fetchAll();
} catch (Exception $e) {
    // Tabela restaurants pode não existir ainda
}

// Buscar tipos de cozinha únicos do banco
$existingCuisines = [];
try {
    $stmt = db()->query("SELECT DISTINCT cuisine_types FROM directory_restaurants WHERE cuisine_types IS NOT NULL AND cuisine_types != '[]'");
    $allCuisines = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Extrair valores únicos do JSON
    foreach ($allCuisines as $jsonCuisines) {
        $cuisines = json_decode($jsonCuisines, true) ?? [];
        foreach ($cuisines as $c) {
            $c = trim($c);
            if ($c && !in_array($c, $existingCuisines)) {
                $existingCuisines[] = $c;
            }
        }
    }
    sort($existingCuisines);
} catch (Exception $e) {
    // Fallback silencioso
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diretório - Master Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <h1 class="font-bold text-lg text-orange-400"><?= APP_NAME ?></h1>
                <div class="flex gap-4 text-sm">
                    <a href="index.php" class="text-gray-400 hover:text-white">Dashboard</a>
                    <a href="restaurants.php" class="text-gray-400 hover:text-white">Restaurantes</a>
                    <a href="plans.php" class="text-gray-400 hover:text-white">Planos</a>
                    <a href="templates.php" class="text-gray-400 hover:text-white">Templates</a>
                    <a href="directory.php" class="text-white">Diretório</a>
                </div>
            </div>
            <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Guia Gastronômico</h2>
            <button onclick="document.getElementById('newModal').classList.remove('hidden')" class="bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-lg font-medium transition">
                + Novo Restaurante
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-900/50 border-green-500 text-green-400' : 'bg-red-900/50 border-red-500 text-red-400' ?> border">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="flex gap-2 mb-6">
            <a href="?status=all" class="px-4 py-2 rounded-lg <?= $statusFilter === 'all' ? 'bg-orange-600' : 'bg-gray-700 hover:bg-gray-600' ?> transition">
                Todos
            </a>
            <a href="?status=active" class="px-4 py-2 rounded-lg <?= $statusFilter === 'active' ? 'bg-green-600' : 'bg-gray-700 hover:bg-gray-600' ?> transition">
                Ativos
            </a>
            <a href="?status=pending" class="px-4 py-2 rounded-lg <?= $statusFilter === 'pending' ? 'bg-yellow-600' : 'bg-gray-700 hover:bg-gray-600' ?> transition">
                Pendentes
            </a>
            <a href="?status=inactive" class="px-4 py-2 rounded-lg <?= $statusFilter === 'inactive' ? 'bg-red-600' : 'bg-gray-700 hover:bg-gray-600' ?> transition">
                Inativos
            </a>
        </div>
        
        <!-- Lista -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Restaurante</th>
                        <th class="text-left px-4 py-3">Bairro</th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Preço</th>
                        <th class="text-left px-4 py-3">Cliente?</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($directoryRestaurants as $dr): 
                        $cuisines = json_decode($dr['cuisine_types'], true) ?? [];
                    ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <?php if ($dr['is_featured']): ?>
                                        <span class="text-yellow-400">⭐</span>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($dr['name']) ?></p>
                                        <p class="text-xs text-gray-400"><?= htmlspecialchars($dr['address']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($dr['neighborhood']) ?></td>
                            <td class="px-4 py-3 text-sm"><?= implode(', ', $cuisines) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($dr['price_range']) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($dr['is_client']): ?>
                                    <span class="text-xs bg-green-900 text-green-400 px-2 py-1 rounded">Sim</span>
                                <?php else: ?>
                                    <span class="text-xs bg-gray-700 text-gray-400 px-2 py-1 rounded">Não</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $dr['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm">
                                        <option value="active" <?= $dr['status'] === 'active' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="pending" <?= $dr['status'] === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                        <option value="inactive" <?= $dr['status'] === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" class="inline" onsubmit="return confirm('Remover do diretório?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $dr['id'] ?>">
                                    <button type="submit" class="text-sm text-red-400 hover:text-red-300">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <!-- Modal Novo -->
    <div id="newModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h3 class="font-bold mb-4">Adicionar ao Diretório</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="text-sm text-gray-400">Nome *</label>
                    <input type="text" name="name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                </div>
                
                <div>
                    <label class="text-sm text-gray-400">Descrição</label>
                    <textarea name="description" rows="2" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-400">Endereço</label>
                        <input type="text" name="address" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Bairro</label>
                        <input type="text" name="neighborhood" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-400">Telefone</label>
                        <input type="text" name="phone" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Faixa de Preço</label>
                        <select name="price_range" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                            <option value="$">$ - Econômico</option>
                            <option value="$$" selected>$$ - Moderado</option>
                            <option value="$$$">$$$ - Elevado</option>
                            <option value="$$$$">$$$$ - Premium</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Tipos de Cozinha</label>
                    
                    <!-- Tags selecionadas -->
                    <div id="selected-cuisines" class="flex flex-wrap gap-2 mb-2 min-h-[32px]">
                        <!-- Tags serão inseridas via JS -->
                    </div>
                    
                    <!-- Campo para adicionar novo -->
                    <div class="flex gap-2">
                        <input type="text" id="new-cuisine-input" 
                               placeholder="Digite ou selecione abaixo" 
                               class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm">
                        <button type="button" onclick="addCuisine()" 
                                class="bg-orange-600 hover:bg-orange-700 px-3 py-2 rounded-lg text-sm">
                            Adicionar
                        </button>
                    </div>
                    
                    <!-- Sugestões existentes -->
                    <div class="mt-3">
                        <span class="text-xs text-gray-500">Sugestões:</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <?php foreach ($existingCuisines as $cuisine): ?>
                                <button type="button" 
                                        onclick="addCuisineFromSuggestion('<?= htmlspecialchars($cuisine, ENT_QUOTES) ?>')"
                                        class="cuisine-suggestion px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-xs transition">
                                    <?= htmlspecialchars($cuisine) ?>
                                </button>
                            <?php endforeach; ?>
                            <?php if (empty($existingCuisines)): ?>
                                <span class="text-xs text-gray-500 italic">Nenhum tipo cadastrado ainda</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Campo hidden para enviar ao form -->
                    <input type="hidden" name="cuisine_types" id="cuisine-types-hidden" value="">
                </div>
                
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_client">
                        <span class="text-sm">É cliente <?= APP_NAME ?>?</span>
                    </label>
                </div>
                
                <div>
                    <label class="text-sm text-gray-400">Vincular a Restaurante (opcional)</label>
                    <select name="linked_restaurant_id" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 mt-1">
                        <option value="">Nenhum</option>
                        <?php foreach ($clientRestaurants as $cr): ?>
                            <option value="<?= $cr['id'] ?>"><?= htmlspecialchars($cr['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 rounded-lg px-4 py-2">Adicionar</button>
                    <button type="button" onclick="document.getElementById('newModal').style.display='none'" class="flex-1 bg-gray-700 hover:bg-gray-600 rounded-lg px-4 py-2">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Fechar modal ao clicar fora
        document.getElementById('newModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
        
        // Sistema de seleção de tipos de cozinha
        let selectedCuisines = [];
        
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        function updateCuisinesDisplay() {
            const container = document.getElementById('selected-cuisines');
            const hidden = document.getElementById('cuisine-types-hidden');
            
            container.innerHTML = selectedCuisines.map(c => `
                <span class="bg-orange-600/20 text-orange-400 px-2 py-1 rounded text-sm flex items-center gap-1">
                    ${escapeHtml(c)}
                    <button type="button" onclick="removeCuisine('${escapeHtml(c).replace(/'/g, "\\'")}'" 
                            class="hover:text-red-400 ml-1">&times;</button>
                </span>
            `).join('');
            
            hidden.value = selectedCuisines.join(',');
            
            // Esconder sugestões já selecionadas
            document.querySelectorAll('.cuisine-suggestion').forEach(btn => {
                if (selectedCuisines.includes(btn.textContent.trim())) {
                    btn.classList.add('hidden');
                } else {
                    btn.classList.remove('hidden');
                }
            });
        }
        
        function addCuisine() {
            const input = document.getElementById('new-cuisine-input');
            const value = input.value.trim();
            
            if (value && !selectedCuisines.includes(value)) {
                selectedCuisines.push(value);
                updateCuisinesDisplay();
            }
            input.value = '';
            input.focus();
        }
        
        function addCuisineFromSuggestion(cuisine) {
            if (!selectedCuisines.includes(cuisine)) {
                selectedCuisines.push(cuisine);
                updateCuisinesDisplay();
            }
        }
        
        function removeCuisine(cuisine) {
            selectedCuisines = selectedCuisines.filter(c => c !== cuisine);
            updateCuisinesDisplay();
        }
        
        // Permitir adicionar com Enter
        document.getElementById('new-cuisine-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCuisine();
            }
        });
        
        // Limpar seleção ao abrir modal
        document.querySelector('[onclick*="newModal"]')?.addEventListener('click', function() {
            selectedCuisines = [];
            updateCuisinesDisplay();
        });
    </script>
</body>
</html>
