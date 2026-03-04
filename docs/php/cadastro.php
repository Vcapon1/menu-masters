<?php
/**
 * CARDÁPIO FLORIPA - Cadastro do Restaurante via Token
 * 
 * Página acessada pelo restaurante via link exclusivo gerado pelo Master Admin.
 * URL: /cadastro/{token}
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

// Extrair token da URL
$requestUri = $_SERVER['REQUEST_URI'];
$token = '';
if (preg_match('#/cadastro/([a-f0-9]{64})#', $requestUri, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(404);
    include __DIR__ . '/templates/404.php';
    exit;
}

// Validar token
$sql = "SELECT r.*, p.name AS plan_name, p.price AS plan_price 
        FROM restaurants r 
        JOIN plans p ON r.plan_id = p.id 
        WHERE r.registration_token = :token";
$stmt = db()->prepare($sql);
$stmt->execute(['token' => $token]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    $pageTitle = "Link Inválido";
    $pageMessage = "Este link de cadastro não é válido ou já foi utilizado.";
    include __DIR__ . '/templates/expired.php';
    exit;
}

// Verificar expiração
if ($restaurant['token_expires_at'] && strtotime($restaurant['token_expires_at']) < time()) {
    $pageTitle = "Link Expirado";
    $pageMessage = "Este link de cadastro expirou. Entre em contato com a plataforma para obter um novo link.";
    include __DIR__ . '/templates/expired.php';
    exit;
}

// Verificar se já completou cadastro
if (!in_array($restaurant['status'], ['aguardando_cadastro', 'lead'])) {
    if ($restaurant['status'] === 'aguardando_pagamento') {
        // Redirecionar para pagamento
        header('Location: /pagamento/' . $token);
        exit;
    }
    $pageTitle = "Cadastro já realizado";
    $pageMessage = "Este cadastro já foi completado. Acesse o painel administrativo para gerenciar seu cardápio.";
    include __DIR__ . '/templates/expired.php';
    exit;
}

$error = '';
$success = false;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos
        $razaoSocial = sanitize($_POST['razao_social'] ?? '');
        $cnpj = sanitize($_POST['cnpj'] ?? '');
        $responsavelNome = sanitize($_POST['responsavel_nome'] ?? '');
        $responsavelCpf = sanitize($_POST['responsavel_cpf'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $instagram = sanitize($_POST['instagram'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $acceptTerms = isset($_POST['accept_terms']);

        // Validações
        if (empty($razaoSocial)) throw new Exception('Razão social é obrigatória.');
        if (empty($cnpj)) throw new Exception('CNPJ é obrigatório.');
        if (strlen(preg_replace('/[^\d]/', '', $cnpj)) !== 14) throw new Exception('CNPJ inválido.');
        if (empty($responsavelNome)) throw new Exception('Nome do responsável é obrigatório.');
        if (empty($responsavelCpf)) throw new Exception('CPF do responsável é obrigatório.');
        if (strlen(preg_replace('/[^\d]/', '', $responsavelCpf)) !== 11) throw new Exception('CPF inválido.');
        if (empty($phone)) throw new Exception('Telefone é obrigatório.');
        if (empty($email) || !isValidEmail($email)) throw new Exception('E-mail válido é obrigatório.');
        if (empty($password) || strlen($password) < 6) throw new Exception('Senha deve ter no mínimo 6 caracteres.');
        if ($password !== $passwordConfirm) throw new Exception('As senhas não coincidem.');
        if (!$acceptTerms) throw new Exception('Você precisa aceitar os termos para continuar.');

        // Verificar email único
        $emailCheck = db()->prepare("SELECT id FROM restaurants WHERE email = :email AND id != :id");
        $emailCheck->execute(['email' => $email, 'id' => $restaurant['id']]);
        if ($emailCheck->fetch()) {
            throw new Exception('Este e-mail já está em uso por outro restaurante.');
        }

        // Atualizar restaurante com dados do cadastro
        $updateSql = "UPDATE restaurants SET 
            razao_social = :razao_social,
            cnpj = :cnpj,
            responsavel_nome = :responsavel_nome,
            responsavel_cpf = :responsavel_cpf,
            phone = :phone,
            email = :email,
            address = :address,
            instagram = :instagram,
            admin_username = :admin_username,
            admin_password_hash = :password_hash,
            accepted_terms_at = NOW(),
            status = 'aguardando_pagamento',
            registration_token = NULL
        WHERE id = :id";

        $updateStmt = db()->prepare($updateSql);
        $updateStmt->execute([
            'razao_social' => $razaoSocial,
            'cnpj' => $cnpj,
            'responsavel_nome' => $responsavelNome,
            'responsavel_cpf' => $responsavelCpf,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'instagram' => $instagram,
            'admin_username' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'id' => $restaurant['id'],
        ]);

        $success = true;

        // Redirecionar para pagamento (a página de pagamento buscará pelo ID)
        header('Location: /pagamento-plano/' . $restaurant['id']);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$planValue = $restaurant['plan_value'] ?? $restaurant['plan_price'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?= htmlspecialchars($restaurant['name']) ?> | Cardápio Floripa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0c0a09 0%, #1c1917 100%); }
        .form-card { background: rgba(31, 41, 55, 0.8); backdrop-filter: blur(10px); }
        .accent-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        input:focus, select:focus, textarea:focus {
            border-color: #f97316 !important;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2);
        }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2">🍽️ Cardápio Floripa</h1>
            <p class="text-gray-400">Complete seu cadastro para ativar seu cardápio digital</p>
        </div>

        <!-- Info do restaurante -->
        <div class="accent-gradient rounded-xl p-4 mb-6 text-center">
            <h2 class="text-xl font-bold"><?= htmlspecialchars($restaurant['name']) ?></h2>
            <p class="text-sm opacity-90">
                Plano: <?= htmlspecialchars($restaurant['plan_name']) ?> — 
                R$ <?= number_format($planValue, 2, ',', '.') ?>/ano
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de cadastro -->
        <form method="post" class="form-card rounded-xl border border-gray-700 p-6 space-y-6">
            
            <!-- Dados da Empresa -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-orange-400">🏢 Dados da Empresa</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm mb-1">Razão Social *</label>
                        <input type="text" name="razao_social" required
                               value="<?= htmlspecialchars($_POST['razao_social'] ?? '') ?>"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">CNPJ *</label>
                        <input type="text" name="cnpj" required placeholder="00.000.000/0000-00"
                               value="<?= htmlspecialchars($_POST['cnpj'] ?? '') ?>"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none"
                               maxlength="18" oninput="maskCNPJ(this)">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Instagram</label>
                        <div class="flex">
                            <span class="bg-gray-700 border border-gray-600 border-r-0 rounded-l-lg px-3 py-3 text-gray-400">@</span>
                            <input type="text" name="instagram"
                                   value="<?= htmlspecialchars($_POST['instagram'] ?? '') ?>"
                                   class="w-full bg-gray-800 border border-gray-600 rounded-r-lg px-4 py-3 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dados do Responsável -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-orange-400">👤 Responsável Legal</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Nome Completo *</label>
                        <input type="text" name="responsavel_nome" required
                               value="<?= htmlspecialchars($_POST['responsavel_nome'] ?? '') ?>"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">CPF *</label>
                        <input type="text" name="responsavel_cpf" required placeholder="000.000.000-00"
                               value="<?= htmlspecialchars($_POST['responsavel_cpf'] ?? '') ?>"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none"
                               maxlength="14" oninput="maskCPF(this)">
                    </div>
                </div>
            </div>

            <!-- Contato -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-orange-400">📞 Contato</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Telefone / WhatsApp *</label>
                        <input type="text" name="phone" required placeholder="(48) 99999-9999"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none"
                               maxlength="15" oninput="maskPhone(this)">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">E-mail * <span class="text-gray-500 text-xs">(será seu login)</span></label>
                        <input type="email" name="email" required
                               value="<?= htmlspecialchars($_POST['email'] ?? $restaurant['email']) ?>"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm mb-1">Endereço</label>
                        <input type="text" name="address"
                               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none"
                               placeholder="Rua, número, bairro, cidade">
                    </div>
                </div>
            </div>

            <!-- Senha -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-orange-400">🔐 Criar Senha de Acesso</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Senha *</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none"
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Confirmar Senha *</label>
                        <input type="password" name="password_confirm" required minlength="6"
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Termos -->
            <div class="bg-gray-900/50 rounded-lg p-4 border border-gray-700">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="accept_terms" required
                           class="mt-1 rounded bg-gray-700 border-gray-600 text-orange-500 focus:ring-orange-500">
                    <span class="text-sm text-gray-300">
                        Declaro que li e aceito os 
                        <a href="#" class="text-orange-400 underline hover:text-orange-300">termos de uso</a> 
                        e o contrato anual de 12 meses no valor de 
                        <strong class="text-white">R$ <?= number_format($planValue, 2, ',', '.') ?></strong>.
                    </span>
                </label>
            </div>

            <!-- Submit -->
            <button type="submit" 
                    class="w-full accent-gradient text-white font-bold py-4 rounded-xl text-lg hover:opacity-90 transition">
                Continuar para Pagamento →
            </button>

            <p class="text-center text-xs text-gray-500">
                Ao continuar, você será redirecionado para a página de pagamento seguro.
            </p>
        </form>
    </div>

    <script>
        function maskCNPJ(el) {
            let v = el.value.replace(/\D/g, '');
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
            el.value = v;
        }
        function maskCPF(el) {
            let v = el.value.replace(/\D/g, '');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            el.value = v;
        }
        function maskPhone(el) {
            let v = el.value.replace(/\D/g, '');
            v = v.replace(/^(\d{2})(\d)/, '($1) $2');
            v = v.replace(/(\d{5})(\d)/, '$1-$2');
            el.value = v;
        }
    </script>
</body>
</html>
