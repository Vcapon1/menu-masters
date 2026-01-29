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
                
                // Validar email único
                $emailCheckSql = "SELECT id FROM restaurants WHERE email = :email";
                if ($action === 'update' && $id) {
                    $emailCheckSql .= " AND id != :current_id";
                }
                $emailCheckStmt = db()->prepare($emailCheckSql);
                $emailParams = ['email' => $data['email']];
                if ($action === 'update' && $id) {
                    $emailParams['current_id'] = $id;
                }
                $emailCheckStmt->execute($emailParams);
                if ($emailCheckStmt->fetch()) {
                    throw new Exception('Este email já está cadastrado para outro restaurante.');
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
                
                // Processar senha do cliente
                $adminPassword = $_POST['admin_password'] ?? '';
                
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
                    
                    // Senha obrigatória na criação
                    if (empty($adminPassword)) {
                        throw new Exception('A senha de acesso do cliente é obrigatória.');
                    }
                    
                    $data['admin_username'] = $data['email']; // Email é o login
                    $data['admin_password_hash'] = password_hash($adminPassword, PASSWORD_BCRYPT);
                    
                    $sql = "INSERT INTO restaurants (name, slug, email, phone, address, internal_notes, 
                            plan_id, template_id, status, expires_at, logo, banner, background_image,
                            primary_color, secondary_color, accent_color, button_color, button_text_color, font_color,
                            admin_username, admin_password_hash)
                            VALUES (:name, :slug, :email, :phone, :address, :internal_notes,
                            :plan_id, :template_id, :status, :expires_at, :logo, :banner, :background_image,
                            :primary_color, :secondary_color, :accent_color, :button_color, :button_text_color, :font_color,
                            :admin_username, :admin_password_hash)";
                    
                    $stmt = db()->prepare($sql);
                    $stmt->execute($data);
                    $message = 'Restaurante criado com sucesso!';
                    
                } else {
                    $data['id'] = $id;
                    
                    // Atualizar senha se fornecida
                    $passwordUpdate = '';
                    if (!empty($adminPassword)) {
                        $passwordUpdate = ', admin_password_hash = :admin_password_hash';
                        $data['admin_password_hash'] = password_hash($adminPassword, PASSWORD_BCRYPT);
                    }
                    
                    // Sempre atualizar admin_username para email
                    $data['admin_username'] = $data['email'];
                    
                    $sql = "UPDATE restaurants SET 
                            name = :name, slug = :slug, email = :email, phone = :phone, address = :address,
                            internal_notes = :internal_notes, plan_id = :plan_id, template_id = :template_id,
                            status = :status, expires_at = :expires_at, logo = :logo, banner = :banner,
                            background_image = :background_image, primary_color = :primary_color,
                            secondary_color = :secondary_color, accent_color = :accent_color,
                            button_color = :button_color, button_text_color = :button_text_color,
                            font_color = :font_color, admin_username = :admin_username{$passwordUpdate}
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
                
            case 'send_contract':
                $id = (int)($_POST['id'] ?? 0);
                $generatePassword = ($_POST['generate_password'] ?? '0') === '1';
                
                // Buscar dados do restaurante
                $restaurant = getRestaurantById($id);
                if (!$restaurant) {
                    throw new Exception('Restaurante não encontrado.');
                }
                
                // Gerar nova senha se solicitado
                $newPassword = null;
                if ($generatePassword) {
                    $newPassword = bin2hex(random_bytes(4)); // 8 caracteres
                    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    
                    $updateSql = "UPDATE restaurants SET admin_password_hash = :hash WHERE id = :id";
                    $updateStmt = db()->prepare($updateSql);
                    $updateStmt->execute(['hash' => $passwordHash, 'id' => $id]);
                }
                
                // Montar email (simulado - requer configuração SMTP)
                $menuUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . $restaurant['slug'];
                $adminUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/admin/login.php';
                
                $subject = "Dados do seu cardápio digital - " . $restaurant['name'];
                $body = "
                    <h2>Olá, {$restaurant['name']}!</h2>
                    <p>Seguem os dados do seu cardápio digital:</p>
                    
                    <h3>🔗 Acesso ao Cardápio</h3>
                    <p><strong>URL:</strong> <a href='{$menuUrl}'>{$menuUrl}</a></p>
                    
                    <h3>📋 Dados do Plano</h3>
                    <p><strong>Plano:</strong> {$restaurant['plan_name']}</p>
                    <p><strong>Validade:</strong> " . ($restaurant['expires_at'] ? date('d/m/Y', strtotime($restaurant['expires_at'])) : 'Não definida') . "</p>
                    
                    <h3>🔑 Acesso Administrativo</h3>
                    <p><strong>URL do Painel:</strong> <a href='{$adminUrl}'>{$adminUrl}</a></p>
                    <p><strong>Login:</strong> {$restaurant['email']}</p>
                    " . ($newPassword ? "<p><strong>Nova Senha:</strong> {$newPassword}</p><p style='color: orange;'>⚠️ Recomendamos alterar esta senha no primeiro acesso.</p>" : "<p><em>Sua senha permanece a mesma.</em></p>") . "
                    
                    <hr>
                    <p>Atenciosamente,<br>Equipe Premium Menu</p>
                ";
                
                // Tentar enviar email
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: Premium Menu <noreply@premiummenu.com.br>\r\n";
                
                if (mail($restaurant['email'], $subject, $body, $headers)) {
                    $message = 'Email enviado com sucesso para ' . $restaurant['email'] . '!';
                } else {
                    // Se mail() falhar, ainda mostrar sucesso parcial
                    $message = 'Dados preparados! Configure o servidor SMTP para envio automático. Email: ' . $restaurant['email'];
                }
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

// Data padrão para expiração: 1 ano a partir de hoje
$defaultExpiresAt = date('Y-m-d', strtotime('+1 year'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurantes - Master Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Modal com scroll interno - CORRIGIDO */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 50;
            display: none;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        .modal-overlay.active {
            display: block;
        }
        .modal-container {
            background: #1f2937;
            border-radius: 0.75rem;
            width: 100%;
            max-width: 56rem;
            margin: 0 auto;
            position: relative;
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #374151;
            background: #1f2937;
            border-radius: 0.75rem 0.75rem 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #374151;
            flex-shrink: 0;
            background: #1f2937;
            border-radius: 0 0 0.75rem 0.75rem;
        }
    </style>
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
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="/<?= htmlspecialchars($r['slug']) ?>" target="_blank" 
                                   class="text-purple-400 hover:text-purple-300 mr-2" title="Ver cardápio">Ver</a>
                                <button onclick="editRestaurant(<?= htmlspecialchars(json_encode($r)) ?>)" 
                                        class="text-blue-400 hover:text-blue-300 mr-2" title="Editar">Editar</button>
                                <button onclick="sendContractData(<?= htmlspecialchars(json_encode($r)) ?>)" 
                                        class="text-green-400 hover:text-green-300 mr-2" title="Enviar dados do contrato por email">📧</button>
                                <button onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars($r['name']) ?>')" 
                                        class="text-red-400 hover:text-red-300" title="Excluir">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <!-- Modal de Exclusão com Senha -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal-container" style="max-width: 28rem;">
            <div class="modal-header">
                <h2 class="text-xl font-bold text-red-400">⚠️ Confirmar Exclusão</h2>
            </div>
            <form method="post">
                <div class="modal-body">
                    <p class="text-gray-300 mb-4">
                        Você está prestes a excluir o restaurante <strong id="delete-name"></strong>.
                        Esta ação é irreversível.
                    </p>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    
                    <div class="mb-4">
                        <label class="block text-sm mb-1">Digite sua senha para confirmar:</label>
                        <input type="password" name="password" required
                               class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2"
                               placeholder="Senha do Admin Master">
                    </div>
                </div>
                <div class="modal-footer flex gap-2">
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
    
    <!-- Modal de Enviar Dados do Contrato -->
    <div id="send-modal" class="modal-overlay">
        <div class="modal-container" style="max-width: 32rem;">
            <div class="modal-header flex justify-between items-center">
                <h2 class="text-xl font-bold text-green-400">📧 Enviar Dados do Contrato</h2>
                <button type="button" onclick="closeSendModal()" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-gray-300 mb-4">
                    Enviar dados do contrato para o restaurante <strong id="send-name" class="text-white"></strong>?
                </p>
                
                <div class="bg-gray-900 rounded-lg p-4 mb-4 text-sm">
                    <h4 class="font-medium mb-2 text-gray-200">Dados que serão enviados:</h4>
                    <ul class="space-y-1 text-gray-400">
                        <li>✅ Nome do restaurante</li>
                        <li>✅ URL do cardápio: <span id="send-url" class="text-purple-400"></span></li>
                        <li>✅ Plano contratado: <span id="send-plan" class="text-white"></span></li>
                        <li>✅ Data de expiração: <span id="send-expires" class="text-white"></span></li>
                        <li>✅ Login de acesso (email)</li>
                        <li>⚠️ <span class="text-yellow-400">Senha não inclusa por segurança</span></li>
                    </ul>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm mb-1">Email do destinatário:</label>
                    <input type="email" id="send-email" readonly
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
                
                <div class="flex items-center gap-2 mb-4">
                    <input type="checkbox" id="send-new-password" class="rounded bg-gray-700 border-gray-600">
                    <label for="send-new-password" class="text-sm">Gerar e enviar nova senha temporária</label>
                </div>
            </div>
            <div class="modal-footer flex gap-2">
                <form method="post" class="flex-1">
                    <input type="hidden" name="action" value="send_contract">
                    <input type="hidden" name="id" id="send-id">
                    <input type="hidden" name="generate_password" id="send-generate-password" value="0">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 py-2 rounded font-medium">
                        📧 Enviar Email
                    </button>
                </form>
                <button type="button" onclick="closeSendModal()" 
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Criar/Editar Restaurante -->
    <div id="form-modal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header flex justify-between items-center">
                <h2 id="form-title" class="text-xl font-bold">Novo Restaurante</h2>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="id" id="form-id">
                <input type="hidden" name="current_logo" id="form-current-logo">
                <input type="hidden" name="current_banner" id="form-current-banner">
                <input type="hidden" name="current_background_image" id="form-current-bg">
                
                <div class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
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
                            <label class="block text-sm mb-1">Email * <span class="text-gray-500 text-xs">(será o login)</span></label>
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
                        
                        <!-- Credenciais de Acesso -->
                        <div class="col-span-2">
                            <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4 mt-4">Credenciais de Acesso</h3>
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm mb-1">
                                Senha do Cliente * 
                                <span id="password-hint" class="text-gray-500 text-xs">(obrigatória na criação)</span>
                            </label>
                            <input type="password" name="admin_password" id="form-password"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2"
                                   placeholder="Senha para acesso ao painel do restaurante">
                            <p class="text-xs text-gray-500 mt-1">O cliente usará o email como login e esta senha para acessar o painel administrativo.</p>
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
                    </div>
                </div>
                
                <!-- Botões fixos no footer -->
                <div class="modal-footer flex gap-4">
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
        // Data padrão para expiração
        const defaultExpiresAt = '<?= $defaultExpiresAt ?>';
        
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
            document.getElementById('form-password').value = '';
            document.getElementById('form-password').required = true;
            document.getElementById('password-hint').textContent = '(obrigatória na criação)';
            document.getElementById('form-plan').value = '';
            document.getElementById('form-template').innerHTML = '<option value="">Selecione o plano primeiro...</option>';
            document.getElementById('form-status').value = 'pending';
            document.getElementById('form-expires').value = defaultExpiresAt;
            document.getElementById('form-current-logo').value = '';
            document.getElementById('form-current-banner').value = '';
            document.getElementById('form-current-bg').value = '';
            document.getElementById('form-primary-color').value = '#dc2626';
            document.getElementById('form-secondary-color').value = '#fbbf24';
            document.getElementById('form-accent-color').value = '#ff6b00';
            document.getElementById('form-button-color').value = '#dc2626';
            document.getElementById('form-button-text-color').value = '#ffffff';
            document.getElementById('form-font-color').value = '#ffffff';
            
            document.getElementById('form-modal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('form-modal').classList.remove('active');
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
            document.getElementById('form-password').value = '';
            document.getElementById('form-password').required = false;
            document.getElementById('password-hint').textContent = '(deixe vazio para manter a senha atual)';
            document.getElementById('form-plan').value = r.plan_id || '';
            document.getElementById('form-status').value = r.status || 'pending';
            document.getElementById('form-expires').value = r.expires_at ? r.expires_at.split(' ')[0] : defaultExpiresAt;
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
            
            document.getElementById('form-modal').classList.add('active');
        }
        
        function confirmDelete(id, name) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-name').textContent = name;
            document.getElementById('delete-modal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('active');
        }
        
        function sendContractData(r) {
            document.getElementById('send-id').value = r.id;
            document.getElementById('send-name').textContent = r.name;
            document.getElementById('send-email').value = r.email;
            document.getElementById('send-url').textContent = window.location.origin + '/' + r.slug;
            document.getElementById('send-plan').textContent = r.plan_name;
            document.getElementById('send-expires').textContent = r.expires_at ? new Date(r.expires_at).toLocaleDateString('pt-BR') : 'Não definida';
            document.getElementById('send-new-password').checked = false;
            document.getElementById('send-generate-password').value = '0';
            document.getElementById('send-modal').classList.add('active');
        }
        
        function closeSendModal() {
            document.getElementById('send-modal').classList.remove('active');
        }
        
        // Atualizar hidden field quando checkbox muda
        document.getElementById('send-new-password')?.addEventListener('change', function() {
            document.getElementById('send-generate-password').value = this.checked ? '1' : '0';
        });
        
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
        document.getElementById('send-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeSendModal();
        });
    </script>
</body>
</html>
