<?php
/**
 * CARDÁPIO FLORIPA - Funções Utilitárias
 * 
 * Funções auxiliares para sanitização, formatação e operações comuns.
 */

require_once __DIR__ . '/../config/database.php';

// =====================================================
// SANITIZAÇÃO E VALIDAÇÃO
// =====================================================

/**
 * Sanitiza string de entrada
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza array de entrada
 */
function sanitizeArray(array $input): array {
    return array_map(function($value) {
        return is_string($value) ? sanitize($value) : $value;
    }, $input);
}

/**
 * Valida email
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida cor hexadecimal
 */
function isValidHexColor(string $color): bool {
    return preg_match('/^#[a-fA-F0-9]{6}$/', $color) === 1;
}

/**
 * Gera slug a partir de string
 */
function generateSlug(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

// =====================================================
// FUNÇÕES DE RESTAURANTE
// =====================================================

/**
 * Busca restaurante por slug
 */
function getRestaurantBySlug(string $slug): ?array {
    $sql = "SELECT r.*, 
                   p.name AS plan_name, p.slug AS plan_slug,
                   p.max_products, p.max_categories,
                   t.name AS template_name, t.slug AS template_slug,
                   t.supports_video, t.supports_promo_price,
                   t.has_grid_view, t.has_list_view
            FROM restaurants r
            JOIN plans p ON r.plan_id = p.id
            JOIN templates t ON r.template_id = t.id
            WHERE r.slug = :slug AND r.status = 'active'";
    
    $stmt = db()->prepare($sql);
    $stmt->execute(['slug' => $slug]);
    
    return $stmt->fetch() ?: null;
}

/**
 * Busca restaurante por ID
 */
function getRestaurantById(int $id): ?array {
    $sql = "SELECT r.*, 
                   p.name AS plan_name, p.slug AS plan_slug,
                   t.name AS template_name, t.slug AS template_slug,
                   t.supports_video, t.supports_promo_price,
                    p.max_products,
                   p.max_categories
            FROM restaurants r
            JOIN plans p ON r.plan_id = p.id
            JOIN templates t ON r.template_id = t.id
            WHERE r.id = :id;
    
    $stmt = db()->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch() ?: null;
}

/**
 * Busca categorias do restaurante
 */
function getCategories(int $restaurantId): array {
    $sql = "SELECT * FROM categories 
            WHERE restaurant_id = :restaurant_id AND is_active = 1
            ORDER BY sort_order ASC, name ASC";
    
    $stmt = db()->prepare($sql);
    $stmt->execute(['restaurant_id' => $restaurantId]);
    
    return $stmt->fetchAll();
}

/**
 * Busca pratos do restaurante
 */
function getProducts(int $restaurantId, bool $includeHidden = false): array {
    $sql = "SELECT p.*, c.name AS category_name 
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.restaurant_id = :restaurant_id";
    
    if (!$includeHidden) {
        $sql .= " AND (p.is_available = 1 OR p.hide_when_unavailable = 0)";
    }
    
    $sql .= " ORDER BY p.sort_order ASC, p.name ASC";
    
    $stmt = db()->prepare($sql);
    $stmt->execute(['restaurant_id' => $restaurantId]);
    
    return $stmt->fetchAll();
}

/**
 * Busca pratos por categoria
 */
function getProductsByCategory(int $categoryId, bool $includeHidden = false): array {
    $sql = "SELECT * FROM products WHERE category_id = :category_id";
    
    if (!$includeHidden) {
        $sql .= " AND (is_available = 1 OR hide_when_unavailable = 0)";
    }
    
    $sql .= " ORDER BY sort_order ASC, name ASC";
    
    $stmt = db()->prepare($sql);
    $stmt->execute(['category_id' => $categoryId]);
    
    return $stmt->fetchAll();
}

// =====================================================
// FUNÇÕES DE PLANO E TEMPLATE
// =====================================================

/**
 * Busca todos os planos ativos
 */
function getPlans(): array {
    $sql = "SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC";
    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

/**
 * Busca templates disponíveis para um plano
 */
function getTemplatesForPlan(int $planId): array {
    $sql = "SELECT t.* 
            FROM templates t
            WHERE t.min_plan_id <= :plan_id AND t.is_active = 1
            ORDER BY t.name ASC";
    
    $stmt = db()->prepare($sql);
    $stmt->execute(['plan_id' => $planId]);
    
    return $stmt->fetchAll();
}

/**
 * Verifica se template está disponível para plano
 */
function isTemplateAvailableForPlan(int $templateId, int $planId): bool {
    $sql = "SELECT 1 FROM templates WHERE id = :template_id AND min_plan_id <= :plan_id AND is_active = 1";
    $stmt = db()->prepare($sql);
    $stmt->execute(['template_id' => $templateId, 'plan_id' => $planId]);
    return $stmt->fetch() !== false;
}

// =====================================================
// GERAÇÃO DE CSS DINÂMICO
// =====================================================

/**
 * Gera variáveis CSS baseadas nas cores do restaurante
 */
function generateCssVariables(array $restaurant): string {
    $primaryRgb = hexToRgb($restaurant['primary_color']);
    $secondaryRgb = hexToRgb($restaurant['secondary_color']);
    $accentRgb = hexToRgb($restaurant['accent_color']);
    $buttonRgb = hexToRgb($restaurant['button_color']);
    
    $bgImage = !empty($restaurant['background_image']) 
        ? "url('" . $restaurant['background_image'] . "')" 
        : "none";
    
    $css = "
    :root {
        --primary-color: {$restaurant['primary_color']};
        --primary-rgb: {$primaryRgb};
        --secondary-color: {$restaurant['secondary_color']};
        --secondary-rgb: {$secondaryRgb};
        --accent-color: {$restaurant['accent_color']};
        --accent-rgb: {$accentRgb};
        --button-color: {$restaurant['button_color']};
        --button-rgb: {$buttonRgb};
        --button-text-color: {$restaurant['button_text_color']};
        --font-color: {$restaurant['font_color']};
        --background-color: {$restaurant['background_color']};
        --background-image: {$bgImage};
    }
    ";
    
    return $css;
}

/**
 * Converte cor hexadecimal para RGB
 */
function hexToRgb(string $hex): string {
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    return "{$r}, {$g}, {$b}";
}

// =====================================================
// FORMATAÇÃO
// =====================================================

/**
 * Formata preço para exibição
 */
function formatPrice(float $price): string {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

/**
 * Retorna badges do produto
 */
function getProductBadges(?string $badgesJson): array {
    if (empty($badgesJson)) return [];
    
    $badges = json_decode($badgesJson, true);
    
    $badgeConfig = [
        'promo' => ['label' => 'Promoção', 'color' => 'bg-red-500'],
        'vegan' => ['label' => 'Vegano', 'color' => 'bg-green-500'],
        'spicy' => ['label' => 'Picante', 'color' => 'bg-orange-500'],
        'new' => ['label' => 'Novo', 'color' => 'bg-blue-500'],
        'chef' => ['label' => 'Chef', 'color' => 'bg-purple-500'],
        'gluten_free' => ['label' => 'Sem Glúten', 'color' => 'bg-teal-500'],
        'lactose_free' => ['label' => 'Sem Lactose', 'color' => 'bg-cyan-500'],
    ];
    
    $result = [];
    foreach ($badges as $badge) {
        if (isset($badgeConfig[$badge])) {
            $result[] = $badgeConfig[$badge];
        }
    }
    
    return $result;
}

// =====================================================
// ESTATÍSTICAS
// =====================================================

/**
 * Registra acesso ao cardápio
 */
function logMenuAccess(int $restaurantId, ?int $productId = null, string $type = 'menu_view'): void {
    $sql = "INSERT INTO access_stats (restaurant_id, product_id, access_type, ip_address, user_agent, referer)
            VALUES (:restaurant_id, :product_id, :type, :ip, :ua, :referer)";
    
    $stmt = db()->prepare($sql);
    $stmt->execute([
        'restaurant_id' => $restaurantId,
        'product_id' => $productId,
        'type' => $type,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
    ]);
}

/**
 * Obtém estatísticas resumidas do restaurante
 */
function getRestaurantStats(int $restaurantId): array {
    $sql = "SELECT 
                COUNT(*) AS total_views,
                COUNT(CASE WHEN access_type = 'menu_view' THEN 1 END) AS menu_views,
                COUNT(CASE WHEN access_type = 'product_view' THEN 1 END) AS product_views,
                COUNT(CASE WHEN DATE(accessed_at) = CURDATE() THEN 1 END) AS views_today,
                COUNT(CASE WHEN accessed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) AS views_week,
                COUNT(CASE WHEN accessed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) AS views_month
            FROM access_stats
            WHERE restaurant_id = :restaurant_id";
    
    $stmt = db()->prepare($sql);
    $stmt->execute(['restaurant_id' => $restaurantId]);
    
    return $stmt->fetch() ?: [
        'total_views' => 0,
        'menu_views' => 0,
        'product_views' => 0,
        'views_today' => 0,
        'views_week' => 0,
        'views_month' => 0,
    ];
}

/**
 * Top produtos mais acessados
 */
function getTopProducts(int $restaurantId, int $limit = 5): array {
    $sql = "SELECT p.id, p.name, COUNT(s.id) AS views
            FROM access_stats s
            JOIN products p ON s.product_id = p.id
            WHERE s.restaurant_id = :restaurant_id 
              AND s.product_id IS NOT NULL
              AND s.accessed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id, p.name
            ORDER BY views DESC
            LIMIT :limit";
    
    $stmt = db()->prepare($sql);
    $stmt->bindValue('restaurant_id', $restaurantId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// =====================================================
// UPLOAD DE ARQUIVOS
// =====================================================

/**
 * Processa upload de imagem
 */
function uploadImage(array $file, string $folder = 'images'): ?string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    
    // Validar tipo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        throw new Exception('Tipo de imagem não permitido.');
    }
    
    // Validar tamanho
    if ($file['size'] > MAX_IMAGE_SIZE) {
        throw new Exception('Imagem muito grande. Máximo: ' . (MAX_IMAGE_SIZE / 1024 / 1024) . 'MB');
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $extension;
    
    // Criar diretório se não existir
    $uploadPath = UPLOAD_DIR . $folder . '/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Mover arquivo
    $destination = $uploadPath . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Erro ao salvar imagem.');
    }
    
    return UPLOAD_URL . $folder . '/' . $filename;
}

/**
 * Processa upload de vídeo
 */
function uploadVideo(array $file, string $folder = 'videos'): ?string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    
    // Validar tipo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
        throw new Exception('Tipo de vídeo não permitido. Use MP4 ou WebM.');
    }
    
    // Validar tamanho
    if ($file['size'] > MAX_VIDEO_SIZE) {
        throw new Exception('Vídeo muito grande. Máximo: ' . (MAX_VIDEO_SIZE / 1024 / 1024) . 'MB');
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('vid_') . '.' . $extension;
    
    // Criar diretório se não existir
    $uploadPath = UPLOAD_DIR . $folder . '/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Mover arquivo
    $destination = $uploadPath . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Erro ao salvar vídeo.');
    }
    
    return UPLOAD_URL . $folder . '/' . $filename;
}

// =====================================================
// AUTENTICAÇÃO
// =====================================================

/**
 * Verifica senha do admin master
 */
function verifyMasterPassword(string $password): bool {
    $sql = "SELECT password_hash FROM master_admins WHERE is_active = 1 LIMIT 1";
    $stmt = db()->query($sql);
    $admin = $stmt->fetch();
    
    if (!$admin) return false;
    
    return password_verify($password, $admin['password_hash']);
}

/**
 * Verifica credenciais do restaurante
 * Aceita login por username ou email
 */
function verifyRestaurantLogin(string $username, string $password): ?array {
    // Buscar por admin_username OU email (para maior flexibilidade)
    $sql = "SELECT id, name, slug, admin_password_hash, status 
        FROM restaurants 
        WHERE (admin_username = :username1 OR email = :username2)";
    $stmt = db()->prepare($sql);
    $stmt->execute([
        'username1' => $username,
        'username2' => $username
    ]);
    $restaurant = $stmt->fetch();
    
    // Verificar se encontrou
    if (!$restaurant) {
        return null;
    }
    
    // Verificar senha
    if (!password_verify($password, $restaurant['admin_password_hash'])) {
        return null;
    }
    
    // Verificar status (retornar null se não ativo para bloquear acesso)
    if ($restaurant['status'] !== 'active') {
        return null;
    }
    
    return $restaurant;
}
