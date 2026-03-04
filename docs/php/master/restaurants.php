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
                    'instagram' => sanitize($_POST['instagram'] ?? ''),
                    'facebook' => sanitize($_POST['facebook'] ?? ''),
                    'whatsapp' => sanitize($_POST['whatsapp'] ?? ''),
                    'google_maps_url' => sanitize($_POST['google_maps_url'] ?? ''),
                    'google_review_url' => sanitize($_POST['google_review_url'] ?? ''),
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
                    'payment_model' => sanitize($_POST['payment_model'] ?? 'commission'),
                    'platform_fee_percent' => floatval($_POST['platform_fee_percent'] ?? 6.00),
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
                            instagram, facebook, whatsapp, google_maps_url, google_review_url,
                            plan_id, template_id, status, expires_at, logo, banner, background_image,
                            primary_color, secondary_color, accent_color, button_color, button_text_color, font_color,
                            admin_username, admin_password_hash, payment_model, platform_fee_percent)
                            VALUES (:name, :slug, :email, :phone, :address, :internal_notes,
                            :instagram, :facebook, :whatsapp, :google_maps_url, :google_review_url,
                            :plan_id, :template_id, :status, :expires_at, :logo, :banner, :background_image,
                            :primary_color, :secondary_color, :accent_color, :button_color, :button_text_color, :font_color,
                            :admin_username, :admin_password_hash, :payment_model, :platform_fee_percent)";
                    
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
                            internal_notes = :internal_notes, instagram = :instagram, facebook = :facebook,
                            whatsapp = :whatsapp, google_maps_url = :google_maps_url, google_review_url = :google_review_url,
                            plan_id = :plan_id, template_id = :template_id,
                            status = :status, expires_at = :expires_at, logo = :logo, banner = :banner,
                            background_image = :background_image, primary_color = :primary_color,
                            secondary_color = :secondary_color, accent_color = :accent_color,
                            button_color = :button_color, button_text_color = :button_text_color,
                            font_color = :font_color, admin_username = :admin_username,
                            payment_model = :payment_model, platform_fee_percent = :platform_fee_percent{$passwordUpdate}
                            WHERE id = :id";
                    
                    $stmt = db()->prepare($sql);
                    $stmt->execute($data);
                    $message = 'Restaurante atualizado com sucesso!';
                }
                
                // Salvar módulos de pedido
                $restaurantIdForModes = ($action === 'create') ? db()->lastInsertId() : $id;
                
                // Salvar is_open
                $isOpenVal = isset($_POST['is_open']) ? 1 : 0;
                $openSql = "UPDATE restaurants SET is_open = :open WHERE id = :id";
                $openStmt = db()->prepare($openSql);
                $openStmt->execute(['open' => $isOpenVal, 'id' => $restaurantIdForModes]);
                
                // Salvar limites de tempo
                $timeLimits = [
                    'pending' => (int)($_POST['time_pending'] ?? 5),
                    'preparing' => (int)($_POST['time_preparing'] ?? 20),
                    'ready' => (int)($_POST['time_ready'] ?? 10),
                ];
                $timeSql = "UPDATE restaurants SET order_time_limits = :limits WHERE id = :id";
                $timeStmt = db()->prepare($timeSql);
                $timeStmt->execute(['limits' => json_encode($timeLimits), 'id' => $restaurantIdForModes]);
                
                // Limpar modos antigos e reinserir
                $delModesSql = "DELETE FROM restaurant_cart_modes WHERE restaurant_id = :rid";
                $delModesStmt = db()->prepare($delModesSql);
                $delModesStmt->execute(['rid' => $restaurantIdForModes]);
                
                $activeModes = $_POST['cart_modes'] ?? [];
                $modeConfigs = $_POST['cart_mode_config'] ?? [];
                
                foreach ($activeModes as $modeId) {
                    $config = isset($modeConfigs[$modeId]) ? json_encode($modeConfigs[$modeId]) : null;
                    $insSql = "INSERT INTO restaurant_cart_modes (restaurant_id, cart_mode_id, is_active, config) VALUES (:rid, :mid, 1, :cfg)";
                    $insStmt = db()->prepare($insSql);
                    $insStmt->execute(['rid' => $restaurantIdForModes, 'mid' => (int)$modeId, 'cfg' => $config]);
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
                
                // Montar email com layout profissional
                $menuUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . $restaurant['slug'];
                $adminUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/admin/login.php';
                $expiresDate = $restaurant['expires_at'] ? date('d/m/Y', strtotime($restaurant['expires_at'])) : 'Não definida';
                
                $subject = "🍽️ Dados do seu cardápio digital - " . $restaurant['name'];
                
                $passwordSection = $newPassword 
                    ? "<tr>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151;'>
                            <span style='color: #9CA3AF;'>Nova Senha:</span>
                        </td>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151;'>
                            <code style='background: #fbbf24; color: #1f2937; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 16px;'>{$newPassword}</code>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2' style='padding: 16px; background: #422006; border-radius: 0 0 8px 8px;'>
                            <span style='color: #fbbf24;'>⚠️ Por segurança, recomendamos alterar esta senha no primeiro acesso.</span>
                        </td>
                    </tr>"
                    : "<tr>
                        <td colspan='2' style='padding: 12px 16px; color: #9CA3AF; font-style: italic;'>
                            Sua senha permanece a mesma.
                        </td>
                    </tr>";
                
                $body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; background-color: #0c0a09;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 16px 16px 0 0; padding: 32px; text-align: center;'>
            <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;'>🍽️ Cardápio Floripa</h1>
            <p style='margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;'>Seu cardápio digital profissional</p>
        </div>
        
        <!-- Content -->
        <div style='background: #1f2937; padding: 32px; border-radius: 0 0 16px 16px;'>
            
            <h2 style='margin: 0 0 8px 0; color: #ffffff; font-size: 22px;'>Olá, {$restaurant['name']}! 👋</h2>
            <p style='margin: 0 0 24px 0; color: #9CA3AF; font-size: 15px;'>Seguem os dados do seu cardápio digital:</p>
            
            <!-- Card: Cardápio -->
            <div style='background: #111827; border: 1px solid #374151; border-radius: 12px; margin-bottom: 20px; overflow: hidden;'>
                <div style='background: #374151; padding: 12px 16px;'>
                    <h3 style='margin: 0; color: #f97316; font-size: 14px; font-weight: 600;'>🔗 SEU CARDÁPIO</h3>
                </div>
                <div style='padding: 16px;'>
                    <a href='{$menuUrl}' style='display: block; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #ffffff; text-decoration: none; padding: 14px 20px; border-radius: 8px; font-weight: 600; text-align: center; font-size: 15px;'>
                        Acessar Cardápio →
                    </a>
                    <p style='margin: 12px 0 0 0; color: #6B7280; font-size: 12px; word-break: break-all;'>{$menuUrl}</p>
                </div>
            </div>
            
            <!-- Card: Plano -->
            <div style='background: #111827; border: 1px solid #374151; border-radius: 12px; margin-bottom: 20px; overflow: hidden;'>
                <div style='background: #374151; padding: 12px 16px;'>
                    <h3 style='margin: 0; color: #f97316; font-size: 14px; font-weight: 600;'>📋 DADOS DO PLANO</h3>
                </div>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151; width: 40%;'>
                            <span style='color: #9CA3AF;'>Plano:</span>
                        </td>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151;'>
                            <span style='color: #ffffff; font-weight: 600;'>{$restaurant['plan_name']}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 16px;'>
                            <span style='color: #9CA3AF;'>Validade:</span>
                        </td>
                        <td style='padding: 12px 16px;'>
                            <span style='color: #ffffff; font-weight: 600;'>{$expiresDate}</span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Card: Acesso Admin -->
            <div style='background: #111827; border: 1px solid #374151; border-radius: 12px; margin-bottom: 20px; overflow: hidden;'>
                <div style='background: #374151; padding: 12px 16px;'>
                    <h3 style='margin: 0; color: #f97316; font-size: 14px; font-weight: 600;'>🔑 ACESSO ADMINISTRATIVO</h3>
                </div>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151; width: 40%;'>
                            <span style='color: #9CA3AF;'>Painel:</span>
                        </td>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151;'>
                            <a href='{$adminUrl}' style='color: #f97316; text-decoration: none;'>{$adminUrl}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151;'>
                            <span style='color: #9CA3AF;'>Login:</span>
                        </td>
                        <td style='padding: 12px 16px; border-bottom: 1px solid #374151;'>
                            <span style='color: #ffffff; font-weight: 600;'>{$restaurant['email']}</span>
                        </td>
                    </tr>
                    {$passwordSection}
                </table>
            </div>
            
            <!-- Footer -->
            <div style='border-top: 1px solid #374151; padding-top: 24px; margin-top: 8px; text-align: center;'>
                <p style='margin: 0 0 8px 0; color: #9CA3AF; font-size: 14px;'>Precisa de ajuda? Entre em contato conosco!</p>
                <p style='margin: 0; color: #6B7280; font-size: 13px;'>
                    Atenciosamente,<br>
                    <strong style='color: #f97316;'>Equipe Cardápio Floripa</strong>
                </p>
            </div>
            
        </div>
        
        <!-- Copyright -->
        <p style='text-align: center; color: #6B7280; font-size: 12px; margin-top: 20px;'>
            © " . date('Y') . " Cardápio Floripa. Todos os direitos reservados.
        </p>
        
    </div>
</body>
</html>
                ";
                
                // Tentar enviar email com headers simplificados
                $to = $restaurant['email'];
                $headers = [];
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-Type: text/html; charset=UTF-8";
                $headers[] = "From: noreply@cardapiofloripa.com.br";
                $headers[] = "Reply-To: noreply@cardapiofloripa.com.br";
                $headers[] = "X-Mailer: PHP/" . phpversion();
                
                $headerString = implode("\r\n", $headers);
                
                $emailSent = @mail($to, $subject, $body, $headerString);
                
                if ($emailSent) {
                    $message = 'Email enviado com sucesso para ' . $restaurant['email'] . '!';
                } else {
                    $lastError = error_get_last();
                    $errorMsg = $lastError ? $lastError['message'] : 'Função mail() indisponível no servidor';
                    $message = 'Falha ao enviar email: ' . $errorMsg;
                }
                break;
            
            case 'create_onboarding':
                // Criar restaurante para onboarding (gera token + link)
                $onbName = sanitize($_POST['onb_name'] ?? '');
                $onbPlanId = (int)($_POST['onb_plan_id'] ?? 0);
                $onbPlanValue = floatval($_POST['onb_plan_value'] ?? 0);
                $onbFeePercent = floatval($_POST['onb_fee_percent'] ?? 6.00);
                $onbOnlinePayment = isset($_POST['onb_online_payment']) ? 1 : 0;

                if (empty($onbName) || $onbPlanId === 0 || $onbPlanValue <= 0) {
                    throw new Exception('Preencha todos os campos obrigatórios.');
                }

                // Gerar slug e token
                $onbSlug = generateSlug($onbName);
                $checkSlugStmt = db()->prepare("SELECT id FROM restaurants WHERE slug = :slug");
                $checkSlugStmt->execute(['slug' => $onbSlug]);
                if ($checkSlugStmt->fetch()) {
                    $onbSlug .= '-' . substr(time(), -4);
                }

                $regToken = bin2hex(random_bytes(32));
                $tokenExpires = date('Y-m-d H:i:s', strtotime('+7 days'));

                // Pegar template padrão
                $defaultTpl = db()->prepare("SELECT id FROM templates WHERE is_active = 1 AND min_plan_id <= :pid ORDER BY id ASC LIMIT 1");
                $defaultTpl->execute(['pid' => $onbPlanId]);
                $tplRow = $defaultTpl->fetch();
                $tplId = $tplRow ? $tplRow['id'] : 1;

                $onbSql = "INSERT INTO restaurants (name, slug, email, plan_id, template_id, status, 
                           registration_token, token_expires_at, plan_value, platform_fee_percent, payment_model)
                           VALUES (:name, :slug, '', :plan_id, :template_id, 'aguardando_cadastro',
                           :token, :expires, :plan_value, :fee, :model)";
                $onbStmt = db()->prepare($onbSql);
                $onbStmt->execute([
                    'name' => $onbName,
                    'slug' => $onbSlug,
                    'plan_id' => $onbPlanId,
                    'template_id' => $tplId,
                    'token' => $regToken,
                    'expires' => $tokenExpires,
                    'plan_value' => $onbPlanValue,
                    'fee' => $onbFeePercent,
                    'model' => $onbOnlinePayment ? 'commission' : 'full',
                ]);

                $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
                $cadastroLink = $baseUrl . '/cadastro/' . $regToken;
                
                $message = "✅ Restaurante criado! Link de cadastro (válido por 7 dias):<br>
                    <div class='mt-2 p-3 bg-gray-900 rounded-lg border border-gray-700'>
                        <input type='text' value='{$cadastroLink}' readonly class='w-full bg-transparent text-purple-400 text-sm' onclick='this.select()'>
                        <button onclick=\"navigator.clipboard.writeText('{$cadastroLink}'); this.textContent='✅ Copiado!'\" class='mt-2 bg-purple-600 hover:bg-purple-700 px-4 py-1 rounded text-xs'>📋 Copiar Link</button>
                    </div>";
                break;
            
            case 'approve_lead':
                // Aprovar lead e gerar link de cadastro
                $leadId = (int)($_POST['lead_id'] ?? 0);
                $leadRest = getRestaurantById($leadId);
                if (!$leadRest || $leadRest['status'] !== 'lead') {
                    throw new Exception('Restaurante não encontrado ou não é um lead.');
                }

                $leadToken = bin2hex(random_bytes(32));
                $leadExpires = date('Y-m-d H:i:s', strtotime('+7 days'));

                $approveSql = "UPDATE restaurants SET status = 'aguardando_cadastro', 
                               registration_token = :token, token_expires_at = :expires 
                               WHERE id = :id";
                $approveStmt = db()->prepare($approveSql);
                $approveStmt->execute([
                    'token' => $leadToken,
                    'expires' => $leadExpires,
                    'id' => $leadId,
                ]);

                $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
                $cadastroLink = $baseUrl . '/cadastro/' . $leadToken;

                $message = "✅ Lead aprovado! Link de cadastro gerado:<br>
                    <div class='mt-2 p-3 bg-gray-900 rounded-lg border border-gray-700'>
                        <input type='text' value='{$cadastroLink}' readonly class='w-full bg-transparent text-purple-400 text-sm' onclick='this.select()'>
                        <button onclick=\"navigator.clipboard.writeText('{$cadastroLink}'); this.textContent='✅ Copiado!'\" class='mt-2 bg-purple-600 hover:bg-purple-700 px-4 py-1 rounded text-xs'>📋 Copiar Link</button>
                    </div>";
                break;
            case 'import_ai':
                $restaurantIdImport = (int)($_POST['restaurant_id'] ?? 0);
                $importDataRaw = $_POST['import_data'] ?? '{}';
                $importData = json_decode($importDataRaw, true);
                
                if (!$restaurantIdImport || !$importData || empty($importData['categories'])) {
                    throw new Exception('Dados de importação inválidos.');
                }
                
                $importedCats = 0;
                $importedProds = 0;
                
                foreach ($importData['categories'] as $catData) {
                    $catName = sanitize($catData['name'] ?? '');
                    if (empty($catName)) continue;
                    
                    // Check if category exists
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
                        
                        $prodDesc = sanitize($prodData['description'] ?? '');
                        $prodPrice = floatval($prodData['price'] ?? 0);
                        
                        $insProdSql = "INSERT INTO products (restaurant_id, category_id, name, description, price, is_available) 
                                       VALUES (:rid, :cid, :name, :desc, :price, 1)";
                        $insProdStmt = db()->prepare($insProdSql);
                        $insProdStmt->execute([
                            'rid' => $restaurantIdImport,
                            'cid' => $categoryId,
                            'name' => $prodName,
                            'desc' => $prodDesc,
                            'price' => $prodPrice
                        ]);
                        $importedProds++;
                    }
                }
                
                $message = "✅ Importação IA concluída: {$importedCats} categorias criadas e {$importedProds} produtos importados!";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar dados
$plans = getPlans();
$restaurants = [];

// Buscar modos de carrinho disponíveis
$cartModesSql = "SELECT * FROM cart_modes WHERE is_active = 1 ORDER BY id ASC";
$cartModesStmt = db()->query($cartModesSql);
$allCartModes = $cartModesStmt->fetchAll();

// Buscar modos ativos por restaurante (para edição)
$restaurantCartModes = [];
$rcmSql = "SELECT rcm.*, cm.slug AS mode_slug FROM restaurant_cart_modes rcm JOIN cart_modes cm ON rcm.cart_mode_id = cm.id";
$rcmStmt = db()->query($rcmSql);
foreach ($rcmStmt->fetchAll() as $rcm) {
    $restaurantCartModes[$rcm['restaurant_id']][$rcm['mode_slug']] = $rcm;
}

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
            <div class="flex gap-2">
                <button onclick="openOnboardingModal()" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm">
                    🚀 Criar Novo Restaurante (Onboarding)
                </button>
                <button onclick="openModal()" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm">
                    + Novo Restaurante (Direto)
                </button>
            </div>
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
                        <th class="px-4 py-3 text-left text-sm">Onboarding</th>
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
                                <?php 
                                    $statusLabels = [
                                        'active' => ['Ativo', 'bg-green-600'],
                                        'lead' => ['Lead', 'bg-blue-600'],
                                        'aguardando_cadastro' => ['Aguard. Cadastro', 'bg-yellow-600'],
                                        'aguardando_pagamento' => ['Aguard. Pagamento', 'bg-orange-600'],
                                        'pending' => ['Pendente', 'bg-yellow-600'],
                                        'inactive' => ['Inativo', 'bg-gray-600'],
                                        'vencido' => ['Vencido', 'bg-red-600'],
                                        'suspenso' => ['Suspenso', 'bg-red-800'],
                                    ];
                                    $sl = $statusLabels[$r['status']] ?? ['Outro', 'bg-gray-600'];
                                ?>
                                <span class="px-2 py-1 text-xs rounded <?= $sl[1] ?>"><?= $sl[0] ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                    $onb = $r['status_onboarding'] ?? 'pendente';
                                    if ($onb === 'completo'): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-green-600">✅ Completo</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded bg-gray-600">Pendente</span>
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
                                <?php if ($r['status'] === 'lead'): ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="approve_lead">
                                        <input type="hidden" name="lead_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="text-green-400 hover:text-green-300 mr-2" title="Aprovar lead e gerar link">✅ Aprovar</button>
                                    </form>
                                <?php endif; ?>
                                <button onclick="sendContractData(<?= htmlspecialchars(json_encode($r)) ?>)" 
                                        class="text-green-400 hover:text-green-300 mr-2" title="Enviar dados do contrato por email">📧</button>
                                <button onclick="openImportAI(<?= $r['id'] ?>, '<?= htmlspecialchars($r['name']) ?>')" 
                                        class="text-yellow-400 hover:text-yellow-300 mr-2" title="Importar cardápio por foto">📸 IA</button>
                                <button onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars($r['name']) ?>')" 
                                        class="text-red-400 hover:text-red-300" title="Excluir">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Onboarding: Criar Restaurante com Link -->
        <div id="onboarding-modal" class="modal-overlay">
            <div class="modal-container" style="max-width: 32rem;">
                <div class="modal-header flex justify-between items-center">
                    <h2 class="text-xl font-bold text-green-400">🚀 Novo Restaurante (Onboarding)</h2>
                    <button type="button" onclick="closeOnboardingModal()" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="create_onboarding">
                    <div class="modal-body space-y-4">
                        <p class="text-sm text-gray-400">Cria o restaurante e gera um link exclusivo para o restaurante completar o cadastro e pagar o plano.</p>
                        
                        <div>
                            <label class="block text-sm mb-1">Nome Fantasia *</label>
                            <input type="text" name="onb_name" required
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Plano *</label>
                            <select name="onb_plan_id" required
                                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                                <option value="">Selecione...</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['name']) ?> - R$ <?= number_format($plan['price'], 2, ',', '.') ?>/mês</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Valor do Plano Anual (R$) *</label>
                            <input type="number" name="onb_plan_value" step="0.01" min="1" required
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2"
                                   placeholder="Ex: 1198.80">
                            <p class="text-xs text-gray-500 mt-1">Valor total cobrado na assinatura anual</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Comissão da Plataforma (%)</label>
                            <input type="number" name="onb_fee_percent" step="0.01" min="0" max="50" value="6.00"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="onb_online_payment" id="onb-online" value="1"
                                   class="rounded bg-gray-700 border-gray-600 text-green-500">
                            <label for="onb-online" class="text-sm">Ativar pagamento online (pedidos)</label>
                        </div>
                    </div>
                    <div class="modal-footer flex gap-2">
                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 py-2 rounded font-medium">
                            🚀 Criar e Gerar Link
                        </button>
                        <button type="button" onclick="closeOnboardingModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
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
                        
                        <!-- Redes Sociais -->
                        <div class="col-span-2">
                            <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4 mt-4">Redes Sociais</h3>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Instagram</label>
                            <div class="flex items-center">
                                <span class="bg-gray-600 border border-gray-600 border-r-0 rounded-l px-3 py-2 text-gray-400 text-sm">@</span>
                                <input type="text" name="instagram" id="form-instagram" placeholder="usuario"
                                       class="w-full bg-gray-700 border border-gray-600 rounded-r px-3 py-2">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Facebook</label>
                            <input type="url" name="facebook" id="form-facebook" placeholder="https://facebook.com/..."
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">WhatsApp</label>
                            <input type="text" name="whatsapp" id="form-whatsapp" placeholder="48999999999"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                            <p class="text-xs text-gray-500 mt-1">Apenas números com DDD</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Google Maps</label>
                            <input type="url" name="google_maps_url" id="form-google-maps" placeholder="https://maps.google.com/..."
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm mb-1">Link para Avaliação no Google</label>
                            <input type="url" name="google_review_url" id="form-google-review" placeholder="https://search.google.com/local/writereview?..."
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                            <p class="text-xs text-gray-500 mt-1">Cole aqui o link direto para avaliação do Google My Business</p>
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
                            <div id="preview-logo" class="mb-2 hidden">
                                <img src="" class="w-16 h-16 rounded object-cover border border-gray-600">
                                <span class="text-xs text-gray-400 mt-1 block">Logo atual</span>
                            </div>
                            <input type="file" name="logo" accept="image/*"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Banner</label>
                            <div id="preview-banner" class="mb-2 hidden">
                                <img src="" class="w-full h-16 rounded object-cover border border-gray-600">
                                <span class="text-xs text-gray-400 mt-1 block">Banner atual</span>
                            </div>
                            <input type="file" name="banner" accept="image/*"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm mb-1">Imagem de Fundo</label>
                            <div id="preview-bg" class="mb-2 hidden">
                                <img src="" class="w-full h-24 rounded object-cover border border-gray-600">
                                <span class="text-xs text-gray-400 mt-1 block">Fundo atual</span>
                            </div>
                            <input type="file" name="background_image" accept="image/*"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                        </div>
                        
                        <!-- Módulos de Pedido -->
                        <div class="col-span-2">
                            <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4 mt-4">🛒 Módulos de Pedido</h3>
                        </div>
                        
                        <div class="col-span-2">
                            <div class="flex items-center gap-3 mb-4 p-3 bg-gray-900 rounded-lg border border-gray-700">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_open" id="form-is-open" value="1" checked
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-600 peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                </label>
                                <div>
                                    <span class="font-medium">Restaurante Aberto</span>
                                    <p class="text-xs text-gray-400">Quando fechado, o cardápio continua visível mas sem botões de pedir</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm mb-2 font-medium">Modos de pedido ativos:</label>
                            <div class="space-y-3" id="cart-modes-container">
                                <?php foreach ($allCartModes as $cm): ?>
                                <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden">
                                    <div class="flex items-center gap-3 p-3">
                                        <input type="checkbox" name="cart_modes[]" value="<?= $cm['id'] ?>" 
                                               id="cart-mode-<?= $cm['id'] ?>" 
                                               class="cart-mode-check rounded bg-gray-700 border-gray-600 text-purple-500 focus:ring-purple-500"
                                               data-mode-slug="<?= htmlspecialchars($cm['slug']) ?>"
                                               onchange="toggleModeConfig(<?= $cm['id'] ?>)">
                                        <div class="flex-1">
                                            <label for="cart-mode-<?= $cm['id'] ?>" class="font-medium cursor-pointer">
                                                <?= htmlspecialchars($cm['name']) ?>
                                            </label>
                                            <p class="text-xs text-gray-400"><?= htmlspecialchars($cm['description'] ?? '') ?></p>
                                            <?php if ($cm['min_plan_id'] > 1): ?>
                                                <span class="text-xs text-yellow-500">Plano mínimo: <?= $cm['min_plan_id'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Config específica do modo WhatsApp -->
                                    <?php if ($cm['slug'] === 'whatsapp'): ?>
                                    <div class="cart-mode-config hidden border-t border-gray-700 p-3 bg-gray-800" id="config-<?= $cm['id'] ?>">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs mb-1">Número WhatsApp *</label>
                                                <input type="text" name="cart_mode_config[<?= $cm['id'] ?>][whatsapp_number]" 
                                                       id="config-whatsapp-number-<?= $cm['id'] ?>"
                                                       placeholder="5548999999999" 
                                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                                <p class="text-xs text-gray-500 mt-1">Com código do país (55) + DDD</p>
                                            </div>
                                            <div>
                                                <label class="block text-xs mb-1">Cabeçalho da mensagem</label>
                                                <input type="text" name="cart_mode_config[<?= $cm['id'] ?>][msg_header]" 
                                                       id="config-msg-header-<?= $cm['id'] ?>"
                                                       placeholder="🍕 Novo Pedido!" value="🍕 Novo Pedido!"
                                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Config genérica para modos com painel -->
                                    <?php if (in_array($cm['slug'], ['table', 'delivery', 'full'])): ?>
                                    <div class="cart-mode-config hidden border-t border-gray-700 p-3 bg-gray-800" id="config-<?= $cm['id'] ?>">
                                        <div class="grid grid-cols-2 gap-3">
                                            <?php if ($cm['slug'] === 'table'): ?>
                                            <div>
                                                <label class="block text-xs mb-1">Total de mesas</label>
                                                <input type="number" name="cart_mode_config[<?= $cm['id'] ?>][total_tables]" 
                                                       id="config-total-tables-<?= $cm['id'] ?>"
                                                       placeholder="20" min="1" max="200"
                                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($cm['slug'] === 'delivery'): ?>
                                            <div>
                                                <label class="block text-xs mb-1">Taxa de entrega (R$)</label>
                                                <input type="number" step="0.01" name="cart_mode_config[<?= $cm['id'] ?>][delivery_fee]" 
                                                       id="config-delivery-fee-<?= $cm['id'] ?>"
                                                       placeholder="5.00" min="0"
                                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs mb-1">Pedido mínimo (R$)</label>
                                                <input type="number" step="0.01" name="cart_mode_config[<?= $cm['id'] ?>][min_order]" 
                                                       id="config-min-order-<?= $cm['id'] ?>"
                                                       placeholder="20.00" min="0"
                                                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Limites de tempo por etapa -->
                        <div class="col-span-2">
                            <label class="block text-sm mb-2 font-medium">⏱ Alertas de tempo (minutos):</label>
                            <p class="text-xs text-gray-400 mb-3">Define após quantos minutos um pedido é considerado atrasado em cada etapa</p>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs mb-1">Pendente</label>
                                    <input type="number" name="time_pending" id="form-time-pending" value="5" min="1" max="60"
                                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                    <p class="text-xs text-gray-500 mt-1">Aguardando aceitar</p>
                                </div>
                                <div>
                                    <label class="block text-xs mb-1">Preparando</label>
                                    <input type="number" name="time_preparing" id="form-time-preparing" value="20" min="1" max="120"
                                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                    <p class="text-xs text-gray-500 mt-1">Em preparo na cozinha</p>
                                </div>
                                <div>
                                    <label class="block text-xs mb-1">Pronto</label>
                                    <input type="number" name="time_ready" id="form-time-ready" value="10" min="1" max="60"
                                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm">
                                    <p class="text-xs text-gray-500 mt-1">Esperando retirada</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuração de Pagamento Stripe -->
                        <div class="col-span-2">
                            <h3 class="text-sm font-medium text-gray-400 border-b border-gray-700 pb-2 mb-4 mt-4">💳 Pagamento Online (Stripe)</h3>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Modelo de Pagamento</label>
                            <select name="payment_model" id="form-payment-model"
                                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                                <option value="commission">Comissionado (plataforma retém %)</option>
                                <option value="full">Full (100% para restaurante)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Define como o valor do pedido é dividido</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm mb-1">Taxa da Plataforma (%)</label>
                            <input type="number" name="platform_fee_percent" id="form-fee-percent" 
                                   value="6.00" step="0.01" min="0" max="50"
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                            <p class="text-xs text-gray-500 mt-1">Valor retido pela plataforma (modelo comissionado)</p>
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm mb-1">Status da Conta Stripe</label>
                            <div class="flex items-center gap-3 p-3 bg-gray-900 rounded-lg border border-gray-700">
                                <span id="stripe-status-badge" class="px-2 py-1 text-xs rounded bg-gray-600">Não configurado</span>
                                <span id="stripe-account-info" class="text-xs text-gray-400">O restaurante precisa configurar os recebimentos no painel dele.</span>
                                <input type="hidden" name="stripe_account_id" id="form-stripe-account-id">
                            </div>
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
    
    <!-- Modal Importar Cardápio por IA -->
    <div id="import-ai-modal" class="modal-overlay">
        <div class="modal-container" style="max-width: 64rem;">
            <div class="modal-header flex justify-between items-center">
                <h2 class="text-xl font-bold text-yellow-400">📸 Importar Cardápio por Foto (IA)</h2>
                <button type="button" onclick="closeImportAI()" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="import-restaurant-id">
                
                <!-- Fase 1: Upload -->
                <div id="import-phase-upload">
                    <p class="text-gray-300 mb-4">
                        Envie até <strong>5 fotos</strong> do cardápio físico do restaurante <strong id="import-restaurant-name"></strong>. 
                        A IA vai extrair categorias e produtos automaticamente.
                    </p>
                    
                    <div id="import-dropzone" class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-yellow-500 transition mb-4"
                         onclick="document.getElementById('import-files').click()"
                         ondragover="event.preventDefault(); this.classList.add('border-yellow-500')"
                         ondragleave="this.classList.remove('border-yellow-500')"
                         ondrop="handleDrop(event)">
                        <p class="text-4xl mb-2">📷</p>
                        <p class="text-gray-300">Arraste as fotos aqui ou clique para selecionar</p>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG ou WebP • Máximo 5 fotos</p>
                        <input type="file" id="import-files" accept="image/*" multiple class="hidden" onchange="handleFiles(this.files)">
                    </div>
                    
                    <div id="import-previews" class="grid grid-cols-5 gap-2 mb-4"></div>
                    
                    <button onclick="analyzeWithAI()" id="btn-analyze" disabled
                            class="w-full bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-700 disabled:text-gray-500 py-3 rounded-lg font-medium transition">
                        🤖 Analisar com IA
                    </button>
                </div>
                
                <!-- Fase 2: Loading -->
                <div id="import-phase-loading" class="hidden text-center py-12">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-yellow-400 mx-auto mb-4"></div>
                    <p class="text-xl font-medium text-yellow-400">Analisando cardápio...</p>
                    <p class="text-gray-400 mt-2">A IA está lendo as imagens e extraindo os dados. Aguarde...</p>
                </div>
                
                <!-- Fase 3: Revisão -->
                <div id="import-phase-review" class="hidden">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-gray-300">
                            Revise os dados extraídos. Edite ou desmarque itens antes de importar.
                        </p>
                        <div class="flex gap-2">
                            <button onclick="selectAll(true)" class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded">Selecionar tudo</button>
                            <button onclick="selectAll(false)" class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded">Desmarcar tudo</button>
                        </div>
                    </div>
                    
                    <div id="import-review-data" class="space-y-4 max-h-96 overflow-y-auto"></div>
                </div>
                
                <!-- Fase 4: Resultado -->
                <div id="import-phase-result" class="hidden text-center py-8">
                    <p class="text-4xl mb-4">✅</p>
                    <p id="import-result-text" class="text-xl font-medium text-green-400"></p>
                    <p class="text-gray-400 mt-2">Os dados foram salvos. Recarregue a página para ver as alterações no painel do restaurante.</p>
                </div>
                
                <!-- Fase Erro -->
                <div id="import-phase-error" class="hidden text-center py-8">
                    <p class="text-4xl mb-4">❌</p>
                    <p id="import-error-text" class="text-xl font-medium text-red-400"></p>
                    <button onclick="resetImport()" class="mt-4 bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">Tentar novamente</button>
                </div>
            </div>
            <div class="modal-footer flex gap-2" id="import-footer-review" style="display:none;">
                <button onclick="confirmImport()" class="flex-1 bg-green-600 hover:bg-green-700 py-2 rounded font-medium">
                    ✅ Importar Selecionados
                </button>
                <button onclick="closeImportAI()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <script>
        // ===== IMPORTAÇÃO POR IA =====
        const EDGE_FUNCTION_URL = '<?= defined("EDGE_FUNCTION_BASE") ? EDGE_FUNCTION_BASE : "https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1" ?>/menu-import-ai';
        let importImages = [];
        let importedData = null;
        
        function openImportAI(restaurantId, name) {
            document.getElementById('import-restaurant-id').value = restaurantId;
            document.getElementById('import-restaurant-name').textContent = name;
            resetImport();
            document.getElementById('import-ai-modal').classList.add('active');
        }
        
        function closeImportAI() {
            document.getElementById('import-ai-modal').classList.remove('active');
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
            document.getElementById('import-footer-review').style.display = phase === 'review' ? 'flex' : 'none';
        }
        
        function handleDrop(e) {
            e.preventDefault();
            e.target.closest('#import-dropzone').classList.remove('border-yellow-500');
            handleFiles(e.dataTransfer.files);
        }
        
        function handleFiles(files) {
            const maxFiles = 5;
            const remaining = maxFiles - importImages.length;
            const toAdd = Array.from(files).slice(0, remaining);
            
            toAdd.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    importImages.push(e.target.result);
                    renderPreviews();
                };
                reader.readAsDataURL(file);
            });
        }
        
        function renderPreviews() {
            const container = document.getElementById('import-previews');
            container.innerHTML = importImages.map((img, i) => `
                <div class="relative group">
                    <img src="${img}" class="w-full h-24 object-cover rounded border border-gray-600">
                    <button onclick="removeImage(${i})" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                </div>
            `).join('');
            document.getElementById('btn-analyze').disabled = importImages.length === 0;
        }
        
        function removeImage(idx) {
            importImages.splice(idx, 1);
            renderPreviews();
        }
        
        async function analyzeWithAI() {
            showPhase('loading');
            try {
                const resp = await fetch(EDGE_FUNCTION_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ images: importImages })
                });
                
                if (!resp.ok) {
                    const err = await resp.json().catch(() => ({}));
                    throw new Error(err.error || 'Erro ao processar imagens');
                }
                
                importedData = await resp.json();
                renderReview(importedData);
                showPhase('review');
            } catch (e) {
                document.getElementById('import-error-text').textContent = e.message;
                showPhase('error');
            }
        }
        
        function renderReview(data) {
            const container = document.getElementById('import-review-data');
            if (!data.categories || data.categories.length === 0) {
                container.innerHTML = '<p class="text-gray-400 text-center py-8">Nenhum dado encontrado nas imagens.</p>';
                return;
            }
            
            container.innerHTML = data.categories.map((cat, ci) => `
                <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden">
                    <div class="bg-gray-800 px-4 py-3 flex items-center gap-3 cursor-pointer" onclick="toggleCategory(${ci})">
                        <input type="checkbox" checked class="cat-check rounded bg-gray-700 border-gray-600" data-cat="${ci}" onchange="toggleCategoryItems(${ci}, this.checked)" onclick="event.stopPropagation()">
                        <span class="text-yellow-400 font-medium flex-1">
                            📂 <input type="text" value="${escHtml(cat.name)}" class="bg-transparent border-b border-gray-600 focus:border-yellow-400 outline-none px-1 w-64" data-cat-name="${ci}" onclick="event.stopPropagation()">
                        </span>
                        <span class="text-xs text-gray-400">${cat.products.length} produtos</span>
                        <span class="text-gray-500 cat-arrow" id="cat-arrow-${ci}">▼</span>
                    </div>
                    <div class="divide-y divide-gray-800" id="cat-products-${ci}">
                        ${cat.products.map((p, pi) => `
                            <div class="px-4 py-2 flex items-center gap-3">
                                <input type="checkbox" checked class="prod-check rounded bg-gray-700 border-gray-600" data-cat="${ci}" data-prod="${pi}">
                                <div class="flex-1 grid grid-cols-3 gap-2">
                                    <input type="text" value="${escHtml(p.name)}" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm" data-field="name" data-cat="${ci}" data-prod="${pi}">
                                    <input type="text" value="${escHtml(p.description || '')}" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-gray-400" data-field="desc" data-cat="${ci}" data-prod="${pi}" placeholder="Descrição">
                                    <input type="number" step="0.01" value="${p.price || 0}" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm text-right" data-field="price" data-cat="${ci}" data-prod="${pi}">
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('');
        }
        
        function escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML.replace(/"/g, '&quot;');
        }
        
        function toggleCategory(ci) {
            const el = document.getElementById('cat-products-' + ci);
            const arrow = document.getElementById('cat-arrow-' + ci);
            el.classList.toggle('hidden');
            arrow.textContent = el.classList.contains('hidden') ? '▶' : '▼';
        }
        
        function toggleCategoryItems(ci, checked) {
            document.querySelectorAll(`.prod-check[data-cat="${ci}"]`).forEach(cb => cb.checked = checked);
        }
        
        function selectAll(checked) {
            document.querySelectorAll('.cat-check, .prod-check').forEach(cb => cb.checked = checked);
        }
        
        function confirmImport() {
            // Collect selected data from editable fields
            const restaurantId = document.getElementById('import-restaurant-id').value;
            const selectedData = [];
            
            document.querySelectorAll('.cat-check').forEach(catCb => {
                const ci = catCb.dataset.cat;
                const catName = document.querySelector(`[data-cat-name="${ci}"]`).value.trim();
                if (!catName) return;
                
                const products = [];
                document.querySelectorAll(`.prod-check[data-cat="${ci}"]`).forEach(prodCb => {
                    if (!prodCb.checked) return;
                    const pi = prodCb.dataset.prod;
                    const name = document.querySelector(`[data-field="name"][data-cat="${ci}"][data-prod="${pi}"]`).value.trim();
                    const desc = document.querySelector(`[data-field="desc"][data-cat="${ci}"][data-prod="${pi}"]`).value.trim();
                    const price = parseFloat(document.querySelector(`[data-field="price"][data-cat="${ci}"][data-prod="${pi}"]`).value) || 0;
                    if (name) products.push({ name, description: desc, price });
                });
                
                if (catCb.checked || products.length > 0) {
                    selectedData.push({ name: catName, products });
                }
            });
            
            if (selectedData.length === 0) {
                alert('Selecione ao menos uma categoria ou produto.');
                return;
            }
            
            showPhase('loading');
            
            // POST to PHP for insertion
            const form = new FormData();
            form.append('action', 'import_ai');
            form.append('restaurant_id', restaurantId);
            form.append('import_data', JSON.stringify({ categories: selectedData }));
            
            fetch('restaurants.php', {
                method: 'POST',
                body: form
            })
            .then(r => r.text())
            .then(html => {
                // Count what was imported
                let totalCats = selectedData.length;
                let totalProds = selectedData.reduce((sum, c) => sum + c.products.length, 0);
                document.getElementById('import-result-text').textContent = 
                    `${totalCats} categorias e ${totalProds} produtos importados com sucesso!`;
                showPhase('result');
            })
            .catch(e => {
                document.getElementById('import-error-text').textContent = e.message;
                showPhase('error');
            });
        }
    </script>

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
        
        // Modos de carrinho por restaurante
        const restaurantCartModes = <?= json_encode($restaurantCartModes) ?>;
        
        function toggleModeConfig(modeId) {
            const checkbox = document.getElementById('cart-mode-' + modeId);
            const config = document.getElementById('config-' + modeId);
            if (config) {
                config.classList.toggle('hidden', !checkbox.checked);
            }
        }
        
        function resetCartModes() {
            document.querySelectorAll('.cart-mode-check').forEach(cb => {
                cb.checked = false;
            });
            document.querySelectorAll('.cart-mode-config').forEach(cfg => {
                cfg.classList.add('hidden');
                cfg.querySelectorAll('input').forEach(inp => inp.value = '');
            });
            document.getElementById('form-is-open').checked = true;
            document.getElementById('form-time-pending').value = '5';
            document.getElementById('form-time-preparing').value = '20';
            document.getElementById('form-time-ready').value = '10';
        }
        
        function loadCartModes(restaurantId) {
            const modes = restaurantCartModes[restaurantId] || {};
            document.querySelectorAll('.cart-mode-check').forEach(cb => {
                const slug = cb.dataset.modeSlug;
                const modeData = modes[slug];
                cb.checked = !!modeData;
                toggleModeConfig(cb.value);
                
                if (modeData && modeData.config) {
                    const config = typeof modeData.config === 'string' ? JSON.parse(modeData.config) : modeData.config;
                    const configEl = document.getElementById('config-' + cb.value);
                    if (configEl) {
                        Object.keys(config).forEach(key => {
                            const input = configEl.querySelector(`[name*="[${key}]"]`);
                            if (input) input.value = config[key];
                        });
                    }
                }
            });
        }
        
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
            document.getElementById('form-instagram').value = '';
            document.getElementById('form-facebook').value = '';
            document.getElementById('form-whatsapp').value = '';
            document.getElementById('form-google-maps').value = '';
            document.getElementById('form-google-review').value = '';
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
            
            // Stripe defaults
            document.getElementById('form-payment-model').value = 'commission';
            document.getElementById('form-fee-percent').value = '6.00';
            document.getElementById('form-stripe-account-id').value = '';
            document.getElementById('stripe-status-badge').textContent = 'Não configurado';
            document.getElementById('stripe-status-badge').className = 'px-2 py-1 text-xs rounded bg-gray-600';
            document.getElementById('stripe-account-info').textContent = 'O restaurante precisa configurar os recebimentos no painel dele.';
            
            // Esconder previews de imagem
            document.getElementById('preview-logo').classList.add('hidden');
            document.getElementById('preview-banner').classList.add('hidden');
            document.getElementById('preview-bg').classList.add('hidden');
            
            resetCartModes();
            
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
            document.getElementById('form-instagram').value = r.instagram || '';
            document.getElementById('form-facebook').value = r.facebook || '';
            document.getElementById('form-whatsapp').value = r.whatsapp || '';
            document.getElementById('form-google-maps').value = r.google_maps_url || '';
            document.getElementById('form-google-review').value = r.google_review_url || '';
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
            
            // Stripe fields
            document.getElementById('form-payment-model').value = r.payment_model || 'commission';
            document.getElementById('form-fee-percent').value = r.platform_fee_percent || '6.00';
            document.getElementById('form-stripe-account-id').value = r.stripe_account_id || '';
            
            const stripeBadge = document.getElementById('stripe-status-badge');
            const stripeInfo = document.getElementById('stripe-account-info');
            if (r.stripe_account_status === 'active') {
                stripeBadge.textContent = '✅ Ativo';
                stripeBadge.className = 'px-2 py-1 text-xs rounded bg-green-600';
                stripeInfo.textContent = 'Conta: ' + (r.stripe_account_id || '') + ' — Recebimentos ativos.';
            } else if (r.stripe_account_status === 'pending') {
                stripeBadge.textContent = '⏳ Pendente';
                stripeBadge.className = 'px-2 py-1 text-xs rounded bg-yellow-600';
                stripeInfo.textContent = 'Onboarding iniciado mas não finalizado.';
            } else if (r.stripe_account_status === 'restricted') {
                stripeBadge.textContent = '⚠️ Restrito';
                stripeBadge.className = 'px-2 py-1 text-xs rounded bg-red-600';
                stripeInfo.textContent = 'Conta restrita — verificação pendente no Stripe.';
            } else {
                stripeBadge.textContent = 'Não configurado';
                stripeBadge.className = 'px-2 py-1 text-xs rounded bg-gray-600';
                stripeInfo.textContent = 'O restaurante precisa configurar os recebimentos no painel dele.';
            }
            
            // Preview de imagens existentes
            const logoPreview = document.getElementById('preview-logo');
            if (r.logo) {
                logoPreview.querySelector('img').src = r.logo;
                logoPreview.classList.remove('hidden');
            } else {
                logoPreview.classList.add('hidden');
            }
            
            const bannerPreview = document.getElementById('preview-banner');
            if (r.banner) {
                bannerPreview.querySelector('img').src = r.banner;
                bannerPreview.classList.remove('hidden');
            } else {
                bannerPreview.classList.add('hidden');
            }
            
            const bgPreview = document.getElementById('preview-bg');
            if (r.background_image) {
                bgPreview.querySelector('img').src = r.background_image;
                bgPreview.classList.remove('hidden');
            } else {
                bgPreview.classList.add('hidden');
            }
            
            // Atualizar templates para o plano selecionado
            updateTemplateOptions(r.plan_id);
            
            // Aguardar atualização e selecionar template
            setTimeout(() => {
                document.getElementById('form-template').value = r.template_id || '';
            }, 100);
            
            // Carregar módulos de pedido
            document.getElementById('form-is-open').checked = r.is_open == 1;
            const timeLimits = r.order_time_limits ? (typeof r.order_time_limits === 'string' ? JSON.parse(r.order_time_limits) : r.order_time_limits) : {};
            document.getElementById('form-time-pending').value = timeLimits.pending || 5;
            document.getElementById('form-time-preparing').value = timeLimits.preparing || 20;
            document.getElementById('form-time-ready').value = timeLimits.ready || 10;
            loadCartModes(r.id);
            
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
