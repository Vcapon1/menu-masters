<?php
/**
 * CARDÁPIO FLORIPA - Admin do Restaurante
 * 
 * Dashboard principal do painel administrativo do restaurante.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['restaurant_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Processar importação IA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'import_ai') {
            $restaurantIdImport = (int)($_POST['restaurant_id'] ?? 0);
            if ($restaurantIdImport !== (int)$_SESSION['restaurant_id']) {
                throw new Exception('Permissão negada.');
            }
            $importDataRaw = $_POST['import_data'] ?? '{}';
            $importData = json_decode($importDataRaw, true);
            
            if (!$importData || empty($importData['categories'])) {
                throw new Exception('Dados de importação inválidos.');
            }
            
            $importedCats = 0;
            $importedProds = 0;
            
            foreach ($importData['categories'] as $catData) {
                $catName = sanitize($catData['name'] ?? '');
                if (empty($catName)) continue;
                
                $catSql = "SELECT id FROM categories WHERE restaurant_id = :rid AND name = :name LIMIT 1";
                $catStmt = db()->prepare($catSql);
                $catStmt->execute(['rid' => $restaurantIdImport, 'name' => $catName]);
                $existingCat = $catStmt->fetch();
                
                if ($existingCat) {
                    $categoryId = $existingCat['id'];
                } else {
                    $insCatSql = "INSERT INTO categories (restaurant_id, name, sort_order) VALUES (:rid, :name, :sort)";
                    $insCatStmt = db()->prepare($insCatSql);
                    $maxSort = db()->query("SELECT COALESCE(MAX(sort_order),0) FROM categories WHERE restaurant_id = $restaurantIdImport")->fetchColumn();
                    $insCatStmt->execute(['rid' => $restaurantIdImport, 'name' => $catName, 'sort' => $maxSort + 1]);
                    $categoryId = db()->lastInsertId();
                    $importedCats++;
                }
                
                foreach ($catData['products'] ?? [] as $prodData) {
                    $prodName = sanitize($prodData['name'] ?? '');
                    if (empty($prodName)) continue;
                    $insProdSql = "INSERT INTO products (restaurant_id, category_id, name, description, price, is_available) VALUES (:rid, :cid, :name, :desc, :price, 1)";
                    $insProdStmt = db()->prepare($insProdSql);
                    $insProdStmt->execute([
                        'rid' => $restaurantIdImport,
                        'cid' => $categoryId,
                        'name' => $prodName,
                        'desc' => sanitize($prodData['description'] ?? ''),
                        'price' => floatval($prodData['price'] ?? 0)
                    ]);
                    $importedProds++;
                }
            }
            
            $message = "✅ Importação IA: {$importedCats} categorias criadas e {$importedProds} produtos importados!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$restaurantId = $_SESSION['restaurant_id'];
$restaurant = getRestaurantById($restaurantId);

if (!$restaurant) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Buscar estatísticas
$stats = getRestaurantStats($restaurantId);
$categories = getCategories($restaurantId);
$products = getProducts($restaurantId, true); // incluir ocultos

// Verificar limites do plano
$totalProducts = count($products);
$totalCategories = count($categories);
$maxProducts = $restaurant['max_products'];
$maxCategories = $restaurant['max_categories'];
$canAddProducts = $maxProducts === -1 || $totalProducts < $maxProducts;
$canAddCategories = $maxCategories === -1 || $totalCategories < $maxCategories;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= htmlspecialchars($restaurant['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <?php if (!empty($restaurant['logo'])): ?>
                    <img src="<?= htmlspecialchars($restaurant['logo']) ?>" alt="Logo" class="w-10 h-10 rounded-full">
                <?php endif; ?>
                <div>
                    <h1 class="font-bold"><?= htmlspecialchars($restaurant['name']) ?></h1>
                    <span class="text-xs text-gray-400">Plano: <?= htmlspecialchars($restaurant['plan_name']) ?></span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="/<?= htmlspecialchars($restaurant['slug']) ?>" target="_blank" 
                   class="text-sm text-blue-400 hover:text-blue-300">
                    Ver Cardápio →
                </a>
                <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
            </div>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="bg-green-900/50 border border-green-600 rounded-lg p-4 mb-6"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Visualizações Hoje</p>
                <p class="text-2xl font-bold"><?= number_format($stats['views_today']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Esta Semana</p>
                <p class="text-2xl font-bold"><?= number_format($stats['views_week']) ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Pratos</p>
                <p class="text-2xl font-bold">
                    <?= $totalProducts ?><?= $maxProducts !== -1 ? "/$maxProducts" : '' ?>
                </p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Categorias</p>
                <p class="text-2xl font-bold">
                    <?= $totalCategories ?><?= $maxCategories !== -1 ? "/$maxCategories" : '' ?>
                </p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <a href="orders.php" class="bg-red-600 hover:bg-red-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📋</span>
                <p class="font-medium mt-2">Pedidos</p>
            </a>
            <a href="products.php" class="bg-blue-600 hover:bg-blue-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">🍽️</span>
                <p class="font-medium mt-2">Pratos</p>
            </a>
            <a href="categories.php" class="bg-green-600 hover:bg-green-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📂</span>
                <p class="font-medium mt-2">Categorias</p>
            </a>
            <a href="stats.php" class="bg-purple-600 hover:bg-purple-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📊</span>
                <p class="font-medium mt-2">Estatísticas</p>
            </a>
            <a href="qrcode.php" class="bg-orange-600 hover:bg-orange-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📱</span>
                <p class="font-medium mt-2">QR Code</p>
            </a>
            <button onclick="openImportAI(<?= $restaurantId ?>, '<?= htmlspecialchars($restaurant['name']) ?>')" 
                    class="bg-yellow-600 hover:bg-yellow-700 rounded-lg p-4 text-center transition">
                <span class="text-2xl">📸</span>
                <p class="font-medium mt-2">Importar IA</p>
            </button>
        </div>
        
        <!-- Plan Info -->
        <?php if (!$canAddProducts || !$canAddCategories): ?>
        <div class="bg-yellow-900/50 border border-yellow-600 rounded-lg p-4 mb-8">
            <p class="text-yellow-400 font-medium">⚠️ Limite do plano atingido</p>
            <p class="text-sm text-gray-300 mt-1">
                Você atingiu o limite do seu plano. 
                <a href="upgrade.php" class="text-yellow-400 underline">Faça upgrade</a> para adicionar mais itens.
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Recent Products -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                <h2 class="font-bold">Pratos Recentes</h2>
                <?php if ($canAddProducts): ?>
                    <a href="products.php?action=new" class="text-sm bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg">
                        + Novo Prato
                    </a>
                <?php endif; ?>
            </div>
            <div class="divide-y divide-gray-700">
                <?php 
                $recentProducts = array_slice($products, 0, 5);
                foreach ($recentProducts as $product): 
                    $badges = getProductBadges($product['badges']);
                ?>
                    <div class="p-4 flex items-center gap-4">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="w-12 h-12 rounded object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-gray-700 rounded flex items-center justify-center">
                                🍽️
                            </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <p class="font-medium"><?= htmlspecialchars($product['name']) ?></p>
                            <p class="text-sm text-gray-400"><?= htmlspecialchars($product['category_name']) ?></p>
                        </div>
                        <div class="text-right">
                            <?php if (!empty($product['promo_price'])): ?>
                                <p class="text-sm line-through text-gray-500"><?= formatPrice($product['price']) ?></p>
                                <p class="font-bold text-red-400"><?= formatPrice($product['promo_price']) ?></p>
                            <?php else: ?>
                                <p class="font-bold"><?= formatPrice($product['price']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-1">
                            <?php foreach ($badges as $badge): ?>
                                <span class="text-xs px-2 py-1 rounded <?= $badge['color'] ?>"><?= $badge['label'] ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!$product['is_available']): ?>
                            <span class="text-xs px-2 py-1 rounded bg-red-900 text-red-400">Indisponível</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Modal Importar Cardápio por IA -->
    <div id="import-ai-modal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:50;display:none;overflow-y:auto;padding:2rem 1rem;">
        <div style="background:#1f2937;border-radius:0.75rem;width:100%;max-width:64rem;margin:0 auto;">
            <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #374151;display:flex;justify-content:space-between;align-items:center;">
                <h2 class="text-xl font-bold text-yellow-400">📸 Importar Cardápio por Foto (IA)</h2>
                <button onclick="closeImportAI()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div style="padding:1.5rem;">
                <input type="hidden" id="import-restaurant-id">
                
                <div id="import-phase-upload">
                    <p class="text-gray-300 mb-4">Envie até <strong>5 fotos</strong> do cardápio físico. A IA vai extrair categorias e produtos.</p>
                    <div id="import-dropzone" class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-yellow-500 transition mb-4"
                         onclick="document.getElementById('import-files').click()"
                         ondragover="event.preventDefault();this.classList.add('border-yellow-500')"
                         ondragleave="this.classList.remove('border-yellow-500')"
                         ondrop="handleDrop(event)">
                        <p class="text-4xl mb-2">📷</p>
                        <p class="text-gray-300">Arraste as fotos aqui ou clique para selecionar</p>
                        <input type="file" id="import-files" accept="image/*" multiple class="hidden" onchange="handleFiles(this.files)">
                    </div>
                    <div id="import-previews" class="grid grid-cols-5 gap-2 mb-4"></div>
                    <button onclick="analyzeWithAI()" id="btn-analyze" disabled class="w-full bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-700 disabled:text-gray-500 py-3 rounded-lg font-medium">🤖 Analisar com IA</button>
                </div>
                
                <div id="import-phase-loading" class="hidden text-center py-12">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-yellow-400 mx-auto mb-4"></div>
                    <p class="text-xl font-medium text-yellow-400">Analisando cardápio...</p>
                </div>
                
                <div id="import-phase-review" class="hidden">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-gray-300">Revise os dados extraídos.</p>
                        <div class="flex gap-2">
                            <button onclick="selectAll(true)" class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded">Tudo</button>
                            <button onclick="selectAll(false)" class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded">Nenhum</button>
                        </div>
                    </div>
                    <div id="import-review-data" class="space-y-4 max-h-96 overflow-y-auto"></div>
                    <div class="flex gap-2 mt-4">
                        <button onclick="confirmImport()" class="flex-1 bg-green-600 hover:bg-green-700 py-2 rounded font-medium">✅ Importar Selecionados</button>
                        <button onclick="closeImportAI()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded">Cancelar</button>
                    </div>
                </div>
                
                <div id="import-phase-result" class="hidden text-center py-8">
                    <p class="text-4xl mb-4">✅</p>
                    <p id="import-result-text" class="text-xl font-medium text-green-400"></p>
                </div>
                
                <div id="import-phase-error" class="hidden text-center py-8">
                    <p class="text-4xl mb-4">❌</p>
                    <p id="import-error-text" class="text-xl font-medium text-red-400"></p>
                    <button onclick="resetImport()" class="mt-4 bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">Tentar novamente</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    const EDGE_FUNCTION_URL = 'https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1/menu-import-ai';
    let importImages = [];
    let importedData = null;
    
    function openImportAI(restaurantId, name) {
        document.getElementById('import-restaurant-id').value = restaurantId;
        resetImport();
        document.getElementById('import-ai-modal').style.display = 'block';
    }
    function closeImportAI() {
        document.getElementById('import-ai-modal').style.display = 'none';
        resetImport();
    }
    function resetImport() {
        importImages = [];
        importedData = null;
        document.getElementById('import-previews').innerHTML = '';
        document.getElementById('btn-analyze').disabled = true;
        showPhase('upload');
    }
    function showPhase(phase) {
        ['upload','loading','review','result','error'].forEach(p => {
            document.getElementById('import-phase-' + p).classList.toggle('hidden', p !== phase);
        });
    }
    function handleDrop(e) {
        e.preventDefault();
        handleFiles(e.dataTransfer.files);
    }
    function handleFiles(files) {
        Array.from(files).slice(0, 5 - importImages.length).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = (e) => { importImages.push(e.target.result); renderPreviews(); };
            reader.readAsDataURL(file);
        });
    }
    function renderPreviews() {
        document.getElementById('import-previews').innerHTML = importImages.map((img, i) => 
            `<div class="relative group"><img src="${img}" class="w-full h-24 object-cover rounded border border-gray-600"><button onclick="importImages.splice(${i},1);renderPreviews()" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 text-xs opacity-0 group-hover:opacity-100">×</button></div>`
        ).join('');
        document.getElementById('btn-analyze').disabled = importImages.length === 0;
    }
    async function analyzeWithAI() {
        showPhase('loading');
        try {
            const resp = await fetch(EDGE_FUNCTION_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({images:importImages}) });
            if (!resp.ok) { const err = await resp.json().catch(()=>({})); throw new Error(err.error||'Erro'); }
            importedData = await resp.json();
            renderReview(importedData);
            showPhase('review');
        } catch(e) { document.getElementById('import-error-text').textContent=e.message; showPhase('error'); }
    }
    function escHtml(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML.replace(/"/g,'&quot;');}
    function renderReview(data) {
        const c = document.getElementById('import-review-data');
        if (!data.categories||!data.categories.length){c.innerHTML='<p class="text-gray-400 text-center py-8">Nenhum dado encontrado.</p>';return;}
        c.innerHTML = data.categories.map((cat,ci) => `
            <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden">
                <div class="bg-gray-800 px-4 py-3 flex items-center gap-3">
                    <input type="checkbox" checked class="cat-check rounded bg-gray-700 border-gray-600" data-cat="${ci}" onchange="document.querySelectorAll('.prod-check[data-cat=\\'${ci}\\']').forEach(c=>c.checked=this.checked)">
                    <span class="text-yellow-400 font-medium flex-1">📂 <input type="text" value="${escHtml(cat.name)}" class="bg-transparent border-b border-gray-600 outline-none px-1 w-48" data-cat-name="${ci}"></span>
                    <span class="text-xs text-gray-400">${cat.products.length} produtos</span>
                </div>
                <div class="divide-y divide-gray-800">
                    ${cat.products.map((p,pi) => `
                        <div class="px-4 py-2 flex items-center gap-3">
                            <input type="checkbox" checked class="prod-check rounded bg-gray-700 border-gray-600" data-cat="${ci}" data-prod="${pi}">
                            <div class="flex-1 grid grid-cols-3 gap-2">
                                <input type="text" value="${escHtml(p.name)}" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm" data-field="name" data-cat="${ci}" data-prod="${pi}">
                                <input type="text" value="${escHtml(p.description||'')}" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-gray-400" data-field="desc" data-cat="${ci}" data-prod="${pi}">
                                <input type="number" step="0.01" value="${p.price||0}" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right" data-field="price" data-cat="${ci}" data-prod="${pi}">
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');
    }
    function selectAll(v){document.querySelectorAll('.cat-check,.prod-check').forEach(c=>c.checked=v);}
    function confirmImport() {
        const rid = document.getElementById('import-restaurant-id').value;
        const selected = [];
        document.querySelectorAll('.cat-check').forEach(cb => {
            const ci=cb.dataset.cat, catName=document.querySelector(`[data-cat-name="${ci}"]`).value.trim();
            if(!catName)return;
            const prods=[];
            document.querySelectorAll(`.prod-check[data-cat="${ci}"]`).forEach(pb => {
                if(!pb.checked)return;
                const pi=pb.dataset.prod;
                prods.push({name:document.querySelector(`[data-field="name"][data-cat="${ci}"][data-prod="${pi}"]`).value.trim(),description:document.querySelector(`[data-field="desc"][data-cat="${ci}"][data-prod="${pi}"]`).value.trim(),price:parseFloat(document.querySelector(`[data-field="price"][data-cat="${ci}"][data-prod="${pi}"]`).value)||0});
            });
            if(cb.checked||prods.length)selected.push({name:catName,products:prods.filter(p=>p.name)});
        });
        if(!selected.length){alert('Selecione ao menos um item.');return;}
        showPhase('loading');
        const form=new FormData();
        form.append('action','import_ai');
        form.append('restaurant_id',rid);
        form.append('import_data',JSON.stringify({categories:selected}));
        // POST to admin handler or master handler
        fetch(window.location.href,{method:'POST',body:form}).then(()=>{
            let tc=selected.length,tp=selected.reduce((s,c)=>s+c.products.length,0);
            document.getElementById('import-result-text').textContent=`${tc} categorias e ${tp} produtos importados!`;
            showPhase('result');
        }).catch(e=>{document.getElementById('import-error-text').textContent=e.message;showPhase('error');});
    }
    document.getElementById('import-ai-modal').addEventListener('click',function(e){if(e.target===this)closeImportAI();});
    </script>
</body>
</html>
