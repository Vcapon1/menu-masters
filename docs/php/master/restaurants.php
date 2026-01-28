<?php
/**
 * PREMIUM MENU - Master Admin: Gerenciamento de Restaurantes
 * 
 * CRUD completo para restaurantes com validação de senha para exclusão.
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação master
if (!isset($_SESSION['master_admin'])) {
    header('Location: login.php');
    exit;
}

$admin = $_SESSION['master_admin'];

$message = '';
$error = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
            case 'update':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
                
                $data = [
                    'name' => sanitize($_POST['name'] ?? ''),
                    'slug' => sanitize($_POST['slug'] ?? ''),
                    'email' => sanitize($_POST['email'] ?? ''),
                    'phone' => sanitize($_POST['phone'] ?? ''),
                    'address' => sanitize($_POST['address'] ?? ''),
                    'internal_notes' => sanitize($_POST['internal_notes'] ?? ''),
                    'plan_id' => (int)($_POST['plan_id'] ?? 0),
                    'template_id' => (int)($_POST['template_id'] ?? 0),
                    'status' => sanitize($_POST['status'] ?? 'pending'),
                    'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
                    'primary_color' => sanitize($_POST['primary_color'] ?? '#dc2626'),
                    'secondary_color' => sanitize($_POST['secondary_color'] ?? '#fbbf24'),
                    'accent_color' => sanitize($_POST['accent_color'] ?? '#ff6b00'),
                    'button_color' => sanitize($_POST['button_color'] ?? '#dc2626'),
                    'button_text_color' => sanitize($_POST['button_text_color'] ?? '#ffffff'),
                    'font_color' => sanitize($_POST['font_color'] ?? '#ffffff'),
                ];
                
                // Validar campos obrigatórios
                if (empty($data['name']) || empty($data['email']) || $data['plan_id'] === 0) {
                    throw new Exception('Preencha todos os campos obrigatórios.');
                }
                
                // Validar se template está disponível para o plano
                if (!isTemplateAvailableForPlan($data['template_id'], $data['plan_id'])) {
                    throw new Exception('Template não disponível para o plano selecionado.');
                }
                
                // Upload de mídia
                $data['logo'] = $_POST['current_logo'] ?? null;
                $data['banner'] = $_POST['current_banner'] ?? null;
                $data['background_image'] = $_POST['current_background_image'] ?? null;
                
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $data['logo'] = uploadImage($_FILES['logo'], 'logos');
                }
                if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                    $data['banner'] = uploadImage($_FILES['banner'], 'banners');
                }
                if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                    $data['background_image'] = uploadImage($_FILES['background_image'], 'backgrounds');
                }
                
                if ($action === 'create') {
                    // Gerar slug se não fornecido
                    if (empty($data['slug'])) {
                        $data['slug'] = generateSlug($data['name']);
                    }
                    
                    // Verificar slug único
                    $checkSql = "SELECT id FROM restaurants WHERE slug = :slug";
                    $checkStmt = db()->prepare($checkSql);
                    $checkStmt->execute(['slug' => $data['slug']]);
                    if ($checkStmt->fetch()) {
                        $data['slug'] .= '-' . time();
                    }
                    
                    $sql = "INSERT INTO restaurants (name, slug, email, phone, address, internal_notes, 
                            plan_id, template_id, status, expires_at, logo, banner, background_image,
                            primary_color, secondary_color, accent_color, button_color, button_text_color, font_color)
                            VALUES (:name, :slug, :email, :phone, :address, :internal_notes,
                            :plan_id, :template_id, :status, :expires_at, :logo, :banner, :background_image,
                            :primary_color, :secondary_color, :accent_color, :button_color, :button_text_color, :font_color)";
                    
                    $stmt = db()->prepare($sql);
                    $stmt->execute($data);
                    $message = 'Restaurante criado com sucesso!';
                    
                } else {
                    $data['id'] = $id;
                    
                    $sql = "UPDATE restaurants SET 
                            name = :name, slug = :slug, email = :email, phone = :phone, address = :address,
                            internal_notes = :internal_notes, plan_id = :plan_id, template_id = :template_id,
                            status = :status, expires_at = :expires_at, logo = :logo, banner = :banner,
                            background_image = :background_image, primary_color = :primary_color,
                            secondary_color = :secondary_color, accent_color = :accent_color,
                            button_color = :button_color, button_text_color = :button_text_color,
                            font_color = :font_color
                            WHERE id = :id";
                    
                    $stmt = db()->prepare($sql);
                    $stmt->execute($data);
                    $message = 'Restaurante atualizado com sucesso!';
                }
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $password = $_POST['password'] ?? '';
                
                // VERIFICAR SENHA DO ADMIN MASTER
                if (!verifyMasterPassword($password)) {
                    throw new Exception('Senha incorreta. Exclusão cancelada.');
                }
                
                // Excluir restaurante (cascata deleta categorias e produtos)
                $sql = "DELETE FROM restaurants WHERE id = :id";
                $stmt = db()->prepare($sql);
                $stmt->execute(['id' => $id]);
                $message = 'Restaurante excluído com sucesso!';
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar dados
$plans = getPlans();
$restaurants = [];

$sql = "SELECT r.*, p.name AS plan_name, t.name AS template_name,
               DATEDIFF(r.expires_at, CURDATE()) AS days_left
        FROM restaurants r
        JOIN plans p ON r.plan_id = p.id
        JOIN templates t ON r.template_id = t.id
        ORDER BY 
            CASE 
                WHEN r.expires_at < CURDATE() THEN 0
                WHEN DATEDIFF(r.expires_at, CURDATE()) <= 30 THEN 1
                ELSE 2
            END,
            r.expires_at ASC";

$stmt = db()->query($sql);
$restaurants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurantes - Master Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-purple-900 border-b border-purple-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-gray-300 hover:text-white">← Dashboard</a>
                <h1 class="font-bold">Gerenciar Restaurantes</h1>
            </div>
            <button onclick="openModal()" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm">
                + Novo Restaurante
            </button>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="bg-green-900/50 border border-green-600 rounded-lg p-4 mb-6">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Tabela de Restaurantes -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm">Logo</th>
                        <th class="px-4 py-3 text-left text-sm">Restaurante</th>
                        <th class="px-4 py-3 text-left text-sm">Plano</th>
                        <th class="px-4 py-3 text-left text-sm">Template</th>
                        <th class="px-4 py-3 text-left text-sm">Status</th>
                        <th class="px-4 py-3 text-left text-sm">Validade</th>
                        <th class="px-4 py-3 text-right text-sm">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($restaurants as $r): 
                        $daysLeft = $r['days_left'];
                        $expStatus = 'ok';
                        if ($daysLeft === null) {
                            $expStatus = 'unknown';
                        } elseif ($daysLeft < 0) {
                            $expStatus = 'expired';
                        } elseif ($daysLeft <= 30) {
                            $expStatus = 'warning';
                        }
                    ?>
                        <tr>
                            <td class="px-4 py-3">
                                <img src="<?= htmlspecialchars($r['logo'] ?: '/placeholder.svg') ?>" 
                                     alt="Logo" class="w-10 h-10 rounded-full object-cover">
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium"><?= htmlspecialchars($r['name']) ?></p>
                                <p class="text-xs text-gray-400">/<?= htmlspecialchars($r['slug']) ?></p>
                                <?php if (!empty($r['internal_notes'])): ?>
                                    <p class="text-xs text-yellow-500 truncate max-w-xs" title="<?= htmlspecialchars($r['internal_notes']) ?>">
                                        📝 <?= htmlspecialchars(substr($r['internal_notes'], 0, 50)) ?>...
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm"><?= htmlspecialchars($r['plan_name']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm"><?= htmlspecialchars($r['template_name']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($r['status'] === 'active'): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-green-600">Ativo</span>
                                <?php elseif ($r['status'] === 'pending'): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-yellow-600">Pendente</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded bg-gray-600">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($expStatus === 'expired'): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-red-600">Expirado</span>
                                <?php elseif ($expStatus === 'warning'): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-yellow-600"><?= $daysLeft ?> dias</span>
                                <?php elseif ($expStatus === 'ok'): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-green-600/50"><?= $daysLeft ?> dias</span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="/<?= htmlspecialchars($r['slug']) ?>" target="_blank" 
                                   class="text-purple-400 hover:text-purple-300 mr-2">Ver</a>
                                <button onclick="editRestaurant(<?= htmlspecialchars(json_encode($r)) ?>)" 
                                        class="text-blue-400 hover:text-blue-300 mr-2">Editar</button>
                                <button onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars($r['name']) ?>')" 
                                        class="text-red-400 hover:text-red-300">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <!-- Modal de Exclusão com Senha -->
    <div id="delete-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h2 class="text-xl font-bold mb-4 text-red-400">⚠️ Confirmar Exclusão</h2>
            <p class="text-gray-300 mb-4">
                Você está prestes a excluir o restaurante <strong id="delete-name"></strong>.
                Esta ação é irreversível.
            </p>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete-id">
                
                <div class="mb-4">
                    <label class="block text-sm mb-1">Digite sua senha para confirmar:</label>
                    <input type="password" name="password" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2"
                           placeholder="Senha do Admin Master">
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 py-2 rounded">
                        Confirmar Exclusão
                    </button>
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Criar/Editar Restaurante -->
    <div id="form-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h2 id="form-title" class="text-xl font-bold mb-6">Novo Restaurante</h2>
            
            <form method="post" enctype="multipart/form-data" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="id" id="form-id">
                <input type="hidden" name="current_logo" id="form-current-logo">
                <input type="hidden" name="current_banner" id="form-current-banner">
                <input type="hidden" name="current_background_image" id="form-current-bg">
                
                <!-- Dados Básicos -->
                <div class="col-span-2">
                    <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4">Dados Básicos</h3>
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Nome *</label>
                    <input type="text" name="name" id="form-name" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Slug (URL)</label>
                    <input type="text" name="slug" id="form-slug" placeholder="gerado-automaticamente"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Email *</label>
                    <input type="email" name="email" id="form-email" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Telefone</label>
                    <input type="text" name="phone" id="form-phone"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm mb-1">Endereço</label>
                    <input type="text" name="address" id="form-address"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm mb-1">Notas Internas (oculto do restaurante)</label>
                    <textarea name="internal_notes" id="form-notes" rows="2"
                              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2"></textarea>
                </div>
                
                <!-- Plano e Template -->
                <div class="col-span-2">
                    <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4 mt-4">Plano e Template</h3>
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Plano *</label>
                    <select name="plan_id" id="form-plan" required
                            class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        <option value="">Selecione o plano...</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['name']) ?> - R$ <?= number_format($plan['price'], 2, ',', '.') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Template *</label>
                    <select name="template_id" id="form-template" required
                            class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        <option value="">Selecione o plano primeiro...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Status</label>
                    <select name="status" id="form-status"
                            class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        <option value="pending">Pendente</option>
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Data de Expiração</label>
                    <input type="date" name="expires_at" id="form-expires"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
                
                <!-- Mídia -->
                <div class="col-span-2">
                    <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4 mt-4">Mídia</h3>
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Logo</label>
                    <input type="file" name="logo" accept="image/*"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                </div>
                
                <div>
                    <label class="block text-sm mb-1">Banner</label>
                    <input type="file" name="banner" accept="image/*"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm mb-1">Imagem de Fundo</label>
                    <input type="file" name="background_image" accept="image/*"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                </div>
                
                <!-- Cores -->
                <div class="col-span-2">
                    <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4 mt-4">Personalização de Cores</h3>
                </div>
                
                <div class="grid grid-cols-3 gap-4 col-span-2">
                    <div>
                        <label class="block text-sm mb-1">Cor Primária</label>
                        <input type="color" name="primary_color" id="form-primary-color" value="#dc2626"
                               class="w-full h-10 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Cor Secundária</label>
                        <input type="color" name="secondary_color" id="form-secondary-color" value="#fbbf24"
                               class="w-full h-10 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Cor de Destaque</label>
                        <input type="color" name="accent_color" id="form-accent-color" value="#ff6b00"
                               class="w-full h-10 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Cor do Botão</label>
                        <input type="color" name="button_color" id="form-button-color" value="#dc2626"
                               class="w-full h-10 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Texto do Botão</label>
                        <input type="color" name="button_text_color" id="form-button-text-color" value="#ffffff"
                               class="w-full h-10 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Cor da Fonte</label>
                        <input type="color" name="font_color" id="form-font-color" value="#ffffff"
                               class="w-full h-10 rounded cursor-pointer">
                    </div>
                </div>
                
                <!-- Botões -->
                <div class="col-span-2 flex gap-4 mt-6">
                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 py-3 rounded-lg font-medium">
                        Salvar
                    </button>
                    <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Templates disponíveis por plano (carregado do PHP)
        const templatesByPlan = <?= json_encode(
            array_reduce($plans, function($acc, $plan) {
                $acc[$plan['id']] = getTemplatesForPlan($plan['id']);
                return $acc;
            }, [])
        ) ?>;
        
        function openModal() {
            // Reset form
            document.getElementById('form-action').value = 'create';
            document.getElementById('form-title').textContent = 'Novo Restaurante';
            document.getElementById('form-id').value = '';
            document.getElementById('form-name').value = '';
            document.getElementById('form-slug').value = '';
            document.getElementById('form-email').value = '';
            document.getElementById('form-phone').value = '';
            document.getElementById('form-address').value = '';
            document.getElementById('form-notes').value = '';
            document.getElementById('form-plan').value = '';
            document.getElementById('form-template').innerHTML = '<option value="">Selecione o plano primeiro...</option>';
            document.getElementById('form-status').value = 'pending';
            document.getElementById('form-expires').value = '';
            document.getElementById('form-current-logo').value = '';
            document.getElementById('form-current-banner').value = '';
            document.getElementById('form-current-bg').value = '';
            document.getElementById('form-primary-color').value = '#dc2626';
            document.getElementById('form-secondary-color').value = '#fbbf24';
            document.getElementById('form-accent-color').value = '#ff6b00';
            document.getElementById('form-button-color').value = '#dc2626';
            document.getElementById('form-button-text-color').value = '#ffffff';
            document.getElementById('form-font-color').value = '#ffffff';
            
            document.getElementById('form-modal').classList.remove('hidden');
            document.getElementById('form-modal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('form-modal').classList.add('hidden');
            document.getElementById('form-modal').classList.remove('flex');
        }
        
        function editRestaurant(r) {
            document.getElementById('form-action').value = 'update';
            document.getElementById('form-title').textContent = 'Editar Restaurante';
            document.getElementById('form-id').value = r.id;
            document.getElementById('form-name').value = r.name || '';
            document.getElementById('form-slug').value = r.slug || '';
            document.getElementById('form-email').value = r.email || '';
            document.getElementById('form-phone').value = r.phone || '';
            document.getElementById('form-address').value = r.address || '';
            document.getElementById('form-notes').value = r.internal_notes || '';
            document.getElementById('form-plan').value = r.plan_id || '';
            document.getElementById('form-status').value = r.status || 'pending';
            document.getElementById('form-expires').value = r.expires_at ? r.expires_at.split(' ')[0] : '';
            document.getElementById('form-current-logo').value = r.logo || '';
            document.getElementById('form-current-banner').value = r.banner || '';
            document.getElementById('form-current-bg').value = r.background_image || '';
            document.getElementById('form-primary-color').value = r.primary_color || '#dc2626';
            document.getElementById('form-secondary-color').value = r.secondary_color || '#fbbf24';
            document.getElementById('form-accent-color').value = r.accent_color || '#ff6b00';
            document.getElementById('form-button-color').value = r.button_color || '#dc2626';
            document.getElementById('form-button-text-color').value = r.button_text_color || '#ffffff';
            document.getElementById('form-font-color').value = r.font_color || '#ffffff';
            
            // Atualizar templates para o plano selecionado
            updateTemplateOptions(r.plan_id);
            
            // Aguardar atualização e selecionar template
            setTimeout(() => {
                document.getElementById('form-template').value = r.template_id || '';
            }, 100);
            
            document.getElementById('form-modal').classList.remove('hidden');
            document.getElementById('form-modal').classList.add('flex');
        }
        
        function confirmDelete(id, name) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-name').textContent = name;
            document.getElementById('delete-modal').classList.remove('hidden');
            document.getElementById('delete-modal').classList.add('flex');
        }
        
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
            document.getElementById('delete-modal').classList.remove('flex');
        }
        
        function updateTemplateOptions(planId) {
            const templateSelect = document.getElementById('form-template');
            const templates = templatesByPlan[planId] || [];
            
            templateSelect.innerHTML = '<option value="">Selecione o template...</option>';
            templates.forEach(t => {
                const option = document.createElement('option');
                option.value = t.id;
                option.textContent = t.name + (t.description ? ` - ${t.description}` : '');
                templateSelect.appendChild(option);
            });
        }
        
        // Quando trocar plano, atualizar templates disponíveis
        document.getElementById('form-plan')?.addEventListener('change', function() {
            updateTemplateOptions(this.value);
        });
        
        // Fechar modal clicando fora
        document.getElementById('form-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.getElementById('delete-modal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>
