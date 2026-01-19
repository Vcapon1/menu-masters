# Estrutura PHP do Sistema de Cardápios

## Arquitetura de Arquivos

```
/public_html/
├── index.php                    # Arquivo mãe - roteador principal
├── config/
│   ├── database.php             # Conexão com banco de dados
│   └── constants.php            # Constantes do sistema
├── includes/
│   ├── functions.php            # Funções utilitárias
│   ├── auth.php                 # Autenticação
│   └── validation.php           # Validação de templates
├── templates/
│   ├── bold/
│   │   ├── template.php         # Layout do template Bold
│   │   └── style.css            # Estilos específicos
│   ├── classic/
│   │   ├── template.php
│   │   └── style.css
│   ├── visual/
│   │   ├── template.php
│   │   └── style.css
│   └── modern/
│       ├── template.php
│       └── style.css
├── uploads/
│   ├── logos/
│   ├── banners/
│   ├── products/
│   └── videos/
├── admin/                       # Área admin do restaurante
│   ├── index.php
│   ├── login.php
│   ├── categories.php
│   └── products.php
└── master/                      # Área master admin
    ├── index.php
    ├── login.php
    ├── restaurants.php
    ├── plans.php
    ├── templates.php
    └── reports.php
```

## Banco de Dados (MySQL)

```sql
-- Tabela de Restaurantes
CREATE TABLE restaurants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    address TEXT,
    template VARCHAR(50) DEFAULT 'classic',
    logo VARCHAR(255),
    banner VARCHAR(255),
    background_video VARCHAR(255),
    primary_color VARCHAR(7) DEFAULT '#dc2626',
    secondary_color VARCHAR(7) DEFAULT '#fbbf24',
    font_color VARCHAR(7) DEFAULT '#ffffff',
    plan_id INT,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE,
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Tabela de Planos
CREATE TABLE plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Templates
CREATE TABLE templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    preview_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Categorias
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Tabela de Produtos
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    video VARCHAR(255),
    badges JSON,
    is_available BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

## Arquivo Principal (index.php)

```php
<?php
/**
 * index.php - Arquivo Mãe / Roteador Principal
 * 
 * Recebe o ID/slug do restaurante via GET e carrega o template apropriado
 * URL: https://seudominio.com/?r=burger-house
 * ou:  https://seudominio.com/burger-house (com .htaccess)
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtém o identificador do restaurante
$restaurantSlug = isset($_GET['r']) ? sanitize($_GET['r']) : null;

if (!$restaurantSlug) {
    // Redireciona para landing page ou mostra erro
    header('Location: /landing');
    exit;
}

// Busca dados do restaurante
$restaurant = getRestaurantBySlug($restaurantSlug);

if (!$restaurant || $restaurant['status'] !== 'active') {
    http_response_code(404);
    include 'templates/404.php';
    exit;
}

// Verifica se o plano expirou
if (strtotime($restaurant['expires_at']) < time()) {
    include 'templates/expired.php';
    exit;
}

// Busca categorias e produtos
$categories = getCategories($restaurant['id']);
$products = getProducts($restaurant['id']);

// Prepara variáveis de cores para CSS dinâmico
$cssVars = generateCssVariables($restaurant);

// Define o caminho do template
$templatePath = 'templates/' . $restaurant['template'] . '/template.php';
$templateStyle = 'templates/' . $restaurant['template'] . '/style.css';

// Verifica se o template existe
if (!file_exists($templatePath)) {
    $templatePath = 'templates/classic/template.php';
    $templateStyle = 'templates/classic/style.css';
}

// Inclui o template
include $templatePath;
?>
```

## Funções Utilitárias (includes/functions.php)

```php
<?php
/**
 * functions.php - Funções utilitárias do sistema
 */

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function getRestaurantBySlug($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCategories($restaurantId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM categories 
        WHERE restaurant_id = ? AND is_active = 1 
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$restaurantId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProducts($restaurantId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.restaurant_id = ? AND p.is_available = 1
        ORDER BY c.sort_order ASC, p.sort_order ASC
    ");
    $stmt->execute([$restaurantId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateCssVariables($restaurant) {
    return "
        <style>
            :root {
                --primary-color: {$restaurant['primary_color']};
                --secondary-color: {$restaurant['secondary_color']};
                --font-color: {$restaurant['font_color']};
                --primary-rgb: " . hexToRgb($restaurant['primary_color']) . ";
                --secondary-rgb: " . hexToRgb($restaurant['secondary_color']) . ";
            }
        </style>
    ";
}

function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return implode(', ', [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ]);
}

function formatPrice($price) {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

function getProductBadges($badges) {
    if (!$badges) return [];
    return json_decode($badges, true) ?? [];
}
?>
```

## Exemplo de Template (templates/bold/template.php)

```php
<?php
/**
 * Template Bold - Alto Contraste
 * 
 * Variáveis disponíveis:
 * - $restaurant: array com dados do restaurante
 * - $categories: array de categorias
 * - $products: array de produtos
 * - $cssVars: string com CSS variables dinâmicas
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> - Cardápio Digital</title>
    
    <!-- CSS Variables Dinâmicas -->
    <?= $cssVars ?>
    
    <!-- CSS do Template -->
    <link rel="stylesheet" href="<?= $templateStyle ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($restaurant['name']) ?>">
    <meta property="og:image" content="<?= $restaurant['banner'] ?>">
</head>
<body>
    <!-- Vídeo de Fundo (se houver) -->
    <?php if (!empty($restaurant['background_video'])): ?>
    <video class="video-background" autoplay loop muted playsinline>
        <source src="<?= $restaurant['background_video'] ?>" type="video/mp4">
    </video>
    <?php endif; ?>

    <header class="header">
        <img src="<?= $restaurant['logo'] ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>" class="logo">
        <h1><?= htmlspecialchars($restaurant['name']) ?></h1>
        <?php if (!empty($restaurant['address'])): ?>
        <p class="address"><?= htmlspecialchars($restaurant['address']) ?></p>
        <?php endif; ?>
    </header>

    <!-- Navegação de Categorias -->
    <nav class="categories-nav">
        <button class="category-btn active" data-category="all">Todos</button>
        <?php foreach ($categories as $category): ?>
        <button class="category-btn" data-category="<?= $category['id'] ?>">
            <?= htmlspecialchars($category['name']) ?>
        </button>
        <?php endforeach; ?>
    </nav>

    <!-- Toggle de Visualização -->
    <div class="view-toggle">
        <button class="view-btn active" data-view="list">Lista</button>
        <button class="view-btn" data-view="grid">Grade</button>
    </div>

    <!-- Lista de Produtos -->
    <main class="products-container" id="products">
        <?php foreach ($products as $product): ?>
        <article class="product-card" data-category="<?= $product['category_id'] ?>">
            <?php if (!empty($product['image'])): ?>
            <img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
            <?php endif; ?>
            
            <div class="product-info">
                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                
                <?php if (!empty($product['description'])): ?>
                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                <?php endif; ?>
                
                <!-- Badges -->
                <?php 
                $badges = getProductBadges($product['badges']);
                if (!empty($badges)): 
                ?>
                <div class="product-badges">
                    <?php foreach ($badges as $badge): ?>
                    <span class="badge badge-<?= $badge ?>"><?= ucfirst($badge) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <p class="product-price"><?= formatPrice($product['price']) ?></p>
            </div>
        </article>
        <?php endforeach; ?>
    </main>

    <script>
        // Filtro de categorias
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                document.querySelectorAll('.product-card').forEach(card => {
                    if (category === 'all' || card.dataset.category === category) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Toggle de visualização
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('products').className = 
                    'products-container view-' + view;
            });
        });
    </script>
</body>
</html>
```

## Validação de Upload de Templates (master/templates.php)

```php
<?php
/**
 * Sistema de upload e validação de templates
 */

function validateTemplate($zipFile) {
    $errors = [];
    $required_files = ['template.php', 'style.css'];
    
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== TRUE) {
        return ['Arquivo ZIP inválido'];
    }
    
    // Verifica arquivos obrigatórios
    foreach ($required_files as $file) {
        if ($zip->locateName($file) === false) {
            $errors[] = "Arquivo obrigatório ausente: {$file}";
        }
    }
    
    // Verifica se template.php contém as variáveis obrigatórias
    $templateContent = $zip->getFromName('template.php');
    $requiredVars = ['$restaurant', '$categories', '$products', '$cssVars'];
    
    foreach ($requiredVars as $var) {
        if (strpos($templateContent, $var) === false) {
            $errors[] = "Variável obrigatória ausente no template: {$var}";
        }
    }
    
    // Verifica por código malicioso básico
    $dangerousFunctions = ['eval', 'exec', 'system', 'shell_exec', 'passthru'];
    foreach ($dangerousFunctions as $func) {
        if (stripos($templateContent, $func . '(') !== false) {
            $errors[] = "Função proibida detectada: {$func}";
        }
    }
    
    $zip->close();
    return $errors;
}

function installTemplate($zipFile, $templateSlug) {
    $targetDir = '../templates/' . $templateSlug . '/';
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($targetDir);
        $zip->close();
        return true;
    }
    
    return false;
}
?>
```

## Arquivo .htaccess para URLs Amigáveis

```apache
RewriteEngine On

# Redireciona /slug para /?r=slug
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9-]+)$ index.php?r=$1 [L,QSA]

# Proteção de diretórios sensíveis
RewriteRule ^config/ - [F,L]
RewriteRule ^includes/ - [F,L]

# Força HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Resumo da Arquitetura

1. **URL de acesso**: `https://seudominio.com/burger-house` ou `?r=burger-house`
2. **index.php** recebe o slug, busca dados do restaurante no banco
3. Carrega categorias e produtos associados
4. Gera CSS variables dinâmicas com as cores do restaurante
5. Inclui o arquivo `templates/{template}/template.php`
6. O template renderiza usando as variáveis `$restaurant`, `$categories`, `$products`

### Vantagens desta arquitetura:
- ✅ Templates isolados e reutilizáveis
- ✅ Cores dinâmicas via CSS variables (não precisa CSS separado por restaurante)
- ✅ Fácil adicionar novos templates
- ✅ Validação de segurança no upload
- ✅ URLs amigáveis
- ✅ Escalável para muitos restaurantes
