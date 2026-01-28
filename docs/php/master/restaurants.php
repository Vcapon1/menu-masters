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
    
    <script>
        // Templates disponíveis por plano (carregado do PHP)
        const templatesByPlan = <?= json_encode(
            array_reduce($plans, function($acc, $plan) {
                $acc[$plan['id']] = getTemplatesForPlan($plan['id']);
                return $acc;
            }, [])
        ) ?>;
        
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
    </script>
</body>
</html>
