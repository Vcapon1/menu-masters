<?php
/**
 * CARDÁPIO FLORIPA - Quero ser Parceiro
 * 
 * Formulário público para restaurantes interessados.
 * Cria registro com status = lead
 */

require_once __DIR__ . '/includes/functions.php';

$message = '';
$error = '';
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = sanitize($_POST['name'] ?? '');
        $responsavel = sanitize($_POST['responsavel'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $city = sanitize($_POST['city'] ?? 'Florianópolis');
        $notes = sanitize($_POST['notes'] ?? '');

        if (empty($name)) throw new Exception('Nome do restaurante é obrigatório.');
        if (empty($responsavel)) throw new Exception('Nome do responsável é obrigatório.');
        if (empty($phone)) throw new Exception('Telefone é obrigatório.');
        if (empty($email) || !isValidEmail($email)) throw new Exception('E-mail válido é obrigatório.');

        // Verificar se já existe cadastro com esse email
        $checkSql = "SELECT id, status FROM restaurants WHERE email = :email LIMIT 1";
        $checkStmt = db()->prepare($checkSql);
        $checkStmt->execute(['email' => $email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'lead') {
                throw new Exception('Já recebemos seu interesse! Entraremos em contato em breve.');
            } else {
                throw new Exception('Este e-mail já está cadastrado na plataforma.');
            }
        }

        // Pegar o plano básico como padrão
        $defaultPlan = db()->query("SELECT id FROM plans WHERE is_active = 1 ORDER BY price ASC LIMIT 1")->fetch();
        $defaultTemplate = db()->query("SELECT id FROM templates WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetch();

        if (!$defaultPlan || !$defaultTemplate) {
            throw new Exception('Erro interno. Tente novamente.');
        }

        // Criar restaurante como lead
        $slug = generateSlug($name);
        $checkSlug = db()->prepare("SELECT id FROM restaurants WHERE slug = :slug");
        $checkSlug->execute(['slug' => $slug]);
        if ($checkSlug->fetch()) {
            $slug .= '-' . substr(time(), -4);
        }

        $sql = "INSERT INTO restaurants (name, slug, email, phone, address, internal_notes, plan_id, template_id, status) 
                VALUES (:name, :slug, :email, :phone, :city, :notes, :plan_id, :template_id, 'lead')";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'email' => $email,
            'phone' => $phone,
            'city' => $city,
            'notes' => "Lead via formulário parceiro. Responsável: $responsavel. Cidade: $city. " . ($notes ? "Obs: $notes" : ""),
            'plan_id' => $defaultPlan['id'],
            'template_id' => $defaultTemplate['id'],
        ]);

        $submitted = true;
        $message = 'Interesse registrado com sucesso! Entraremos em contato em breve.';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quero ser Parceiro | Cardápio Floripa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0c0a09 0%, #1c1917 100%); }
        .form-card { background: rgba(31, 41, 55, 0.8); backdrop-filter: blur(10px); }
        .accent-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2">🍽️ Cardápio Floripa</h1>
            <p class="text-gray-400">Transforme seu cardápio em uma experiência digital</p>
        </div>

        <?php if ($submitted): ?>
            <div class="form-card rounded-xl border border-gray-700 p-8 text-center">
                <p class="text-4xl mb-4">🎉</p>
                <h2 class="text-2xl font-bold text-green-400 mb-2">Recebemos seu interesse!</h2>
                <p class="text-gray-300 mb-6">Nossa equipe entrará em contato em breve para apresentar nossos planos e benefícios.</p>
                <a href="/" class="inline-block accent-gradient px-8 py-3 rounded-lg font-medium hover:opacity-90">
                    ← Voltar para o site
                </a>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="bg-red-900/50 border border-red-600 rounded-lg p-4 mb-6">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="form-card rounded-xl border border-gray-700 p-6 space-y-4">
                <h2 class="text-xl font-bold text-center mb-2">Quero ser Parceiro</h2>
                <p class="text-sm text-gray-400 text-center mb-4">Preencha seus dados e entraremos em contato</p>

                <div>
                    <label class="block text-sm mb-1">Nome do Restaurante *</label>
                    <input type="text" name="name" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:border-orange-500">
                </div>

                <div>
                    <label class="block text-sm mb-1">Nome do Responsável *</label>
                    <input type="text" name="responsavel" required
                           value="<?= htmlspecialchars($_POST['responsavel'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:border-orange-500">
                </div>

                <div>
                    <label class="block text-sm mb-1">Telefone / WhatsApp *</label>
                    <input type="text" name="phone" required placeholder="(48) 99999-9999"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:border-orange-500"
                           maxlength="15">
                </div>

                <div>
                    <label class="block text-sm mb-1">E-mail *</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:border-orange-500">
                </div>

                <div>
                    <label class="block text-sm mb-1">Cidade</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? 'Florianópolis') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:border-orange-500">
                </div>

                <div>
                    <label class="block text-sm mb-1">Observações</label>
                    <textarea name="notes" rows="3"
                              class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:border-orange-500"
                              placeholder="Conte-nos um pouco sobre seu restaurante..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" 
                        class="w-full accent-gradient text-white font-bold py-4 rounded-xl text-lg hover:opacity-90 transition">
                    Enviar Interesse
                </button>

                <p class="text-center text-xs text-gray-500">
                    <a href="/" class="text-orange-400 hover:text-orange-300">← Voltar para o site</a>
                </p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
