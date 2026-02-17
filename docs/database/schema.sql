-- =====================================================
-- CARDÁPIO FLORIPA - SCHEMA DO BANCO DE DADOS MySQL
-- =====================================================
-- Execute este script para criar todas as tabelas
-- Versão: 1.0
-- =====================================================

-- Configurações iniciais
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABELA: plans (Planos de assinatura)
-- =====================================================
CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` ENUM('monthly', 'yearly') NOT NULL DEFAULT 'monthly',
  `max_products` INT NOT NULL DEFAULT 50 COMMENT '-1 para ilimitado',
  `max_categories` INT NOT NULL DEFAULT 10 COMMENT '-1 para ilimitado',
  `features` JSON COMMENT 'Lista de recursos do plano',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais dos planos
INSERT INTO `plans` (`name`, `slug`, `price`, `billing_cycle`, `max_products`, `max_categories`, `features`, `is_active`, `is_popular`) VALUES
('Básico', 'basic', 49.90, 'monthly', 50, 5, '["Até 50 pratos", "5 categorias", "1 template", "Suporte por email"]', 1, 0),
('Premium', 'premium', 99.90, 'monthly', 200, 20, '["Até 200 pratos", "20 categorias", "Todos os templates", "QR Code ilimitado", "Suporte prioritário", "Vídeos nos pratos"]', 1, 1),
('Personalité', 'personalite', 199.90, 'monthly', -1, -1, '["Pratos ilimitados", "Categorias ilimitadas", "Templates exclusivos", "API acesso", "Suporte 24/7", "Multi-loja", "Personalização total"]', 1, 0);

-- =====================================================
-- TABELA: templates (Templates de cardápio)
-- =====================================================
CREATE TABLE IF NOT EXISTS `templates` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `preview_image` VARCHAR(500),
  `min_plan_id` INT UNSIGNED NOT NULL COMMENT 'Plano mínimo necessário para usar este template',
  `has_grid_view` TINYINT(1) NOT NULL DEFAULT 1,
  `has_list_view` TINYINT(1) NOT NULL DEFAULT 1,
  `supports_video` TINYINT(1) NOT NULL DEFAULT 0,
  `supports_promo_price` TINYINT(1) NOT NULL DEFAULT 0,
  `default_colors` JSON COMMENT 'Cores padrão do template: {primary, secondary, accent, button, buttonText, font}',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`min_plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais dos templates com cores padrão
INSERT INTO `templates` (`name`, `slug`, `description`, `min_plan_id`, `has_grid_view`, `has_list_view`, `supports_video`, `supports_promo_price`, `default_colors`) VALUES
('Appetite', 'appetite', 'Moderno estilo iFood - laranja vibrante com fundo claro', 1, 1, 1, 0, 1, '{"primary": "#f97316", "secondary": "#1f2937", "accent": "#f59e0b", "button": "#f97316", "buttonText": "#ffffff", "font": "#1f2937"}'),
('Clássico', 'classic', 'Elegante e equilibrado - tons neutros com dourado', 1, 1, 1, 0, 0, '{"primary": "#1f2937", "secondary": "#f59e0b", "accent": "#d97706", "button": "#f59e0b", "buttonText": "#1f2937", "font": "#1f2937"}'),
('Visual', 'visual', 'Focado em imagens grandes - verde fresco', 2, 1, 0, 0, 1, '{"primary": "#059669", "secondary": "#10b981", "accent": "#34d399", "button": "#059669", "buttonText": "#ffffff", "font": "#1f2937"}'),
('Moderno', 'modern', 'Clean e moderno - azul tecnológico', 2, 1, 1, 1, 1, '{"primary": "#0ea5e9", "secondary": "#0284c7", "accent": "#38bdf8", "button": "#0ea5e9", "buttonText": "#ffffff", "font": "#0f172a"}'),
('Bold', 'bold', 'Alto contraste vermelho e amarelo - impactante', 2, 1, 1, 1, 1, '{"primary": "#dc2626", "secondary": "#fbbf24", "accent": "#f59e0b", "button": "#dc2626", "buttonText": "#ffffff", "font": "#ffffff"}'),
('Hero', 'hero', 'Design impactante com hero banner - ideal para hamburgerias', 2, 1, 1, 1, 1, '{"primary": "#f59e0b", "secondary": "#fbbf24", "accent": "#f97316", "button": "#f59e0b", "buttonText": "#000000", "font": "#ffffff"}'),
('Elegante', 'elegant', 'Sofisticado - tons amadeirados e dourados', 3, 1, 1, 1, 1, '{"primary": "#b45309", "secondary": "#78350f", "accent": "#d97706", "button": "#b45309", "buttonText": "#ffffff", "font": "#292524"}'),
('Minimalista', 'minimal', 'Ultra clean - preto e branco sofisticado', 3, 0, 1, 1, 1, '{"primary": "#18181b", "secondary": "#71717a", "accent": "#3f3f46", "button": "#18181b", "buttonText": "#ffffff", "font": "#18181b"}'),
('Dark Mode', 'dark', 'Tema escuro moderno - roxo sofisticado', 3, 1, 1, 1, 1, '{"primary": "#7c3aed", "secondary": "#a78bfa", "accent": "#8b5cf6", "button": "#7c3aed", "buttonText": "#ffffff", "font": "#f4f4f5"}'),
('Pizzaria', 'pizzaria', 'Tema quente para pizzarias - vermelho com dourado, Pizza Builder integrado', 1, 0, 1, 1, 1, '{"primary": "#c0392b", "secondary": "#d4a574", "accent": "#e74c3c", "button": "#c0392b", "buttonText": "#ffffff", "font": "#faf5f0"}');

-- =====================================================
-- TABELA: restaurants (Restaurantes/Clientes)
-- =====================================================
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30),
  `address` TEXT,
  `internal_notes` TEXT COMMENT 'Notas internas visíveis apenas para admin master',
  
  -- Mídia
  `logo` VARCHAR(500),
  `banner` VARCHAR(500),
  `background_image` VARCHAR(500),
  `background_video` VARCHAR(500),
  
  -- Redes Sociais
  `instagram` VARCHAR(100),
  `facebook` VARCHAR(200),
  `whatsapp` VARCHAR(30),
  `google_maps_url` VARCHAR(500),
  `google_review_url` VARCHAR(500),
  
  -- Cores do tema
  `primary_color` VARCHAR(7) NOT NULL DEFAULT '#dc2626',
  `secondary_color` VARCHAR(7) NOT NULL DEFAULT '#fbbf24',
  `accent_color` VARCHAR(7) NOT NULL DEFAULT '#ff6b00',
  `button_color` VARCHAR(7) NOT NULL DEFAULT '#dc2626',
  `button_text_color` VARCHAR(7) NOT NULL DEFAULT '#ffffff',
  `font_color` VARCHAR(7) NOT NULL DEFAULT '#ffffff',
  `background_color` VARCHAR(7) NOT NULL DEFAULT '#1a1a1a',
  
  -- Relacionamentos
  `plan_id` INT UNSIGNED NOT NULL,
  `template_id` INT UNSIGNED NOT NULL,
  
  -- Status e validade
  `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
  `expires_at` DATE,
  
  -- Credenciais de acesso (admin do restaurante)
  `admin_username` VARCHAR(100),
  `admin_password_hash` VARCHAR(255) COMMENT 'Hash bcrypt da senha',
  
  -- Timestamps
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE RESTRICT,
  
  INDEX `idx_status` (`status`),
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: categories (Categorias de pratos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(500),
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_multi_flavor` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Habilita montagem multi-sabor (pizzas)',
  `flavor_config` JSON DEFAULT NULL COMMENT 'Limite de sabores por tamanho: {"P": 1, "M": 2, "G": 3}',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  INDEX `idx_restaurant_order` (`restaurant_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: products (Pratos/Produtos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `promo_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'Preço promocional (NULL = sem promoção)',
  `sizes_prices` JSON DEFAULT NULL COMMENT 'Preços por tamanho: [{"label": "P", "price": 29.90}, ...]',
  `image` VARCHAR(500),
  `video` VARCHAR(500) COMMENT 'Vídeo do prato (upload local)',
  `badges` JSON COMMENT '["promo", "vegan", "spicy", "new", "chef"]',
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `hide_when_unavailable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Ocultar quando indisponível',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_restaurant_order` (`restaurant_id`, `sort_order`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_available` (`is_available`, `hide_when_unavailable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: access_stats (Estatísticas de acesso)
-- =====================================================
CREATE TABLE IF NOT EXISTS `access_stats` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = acesso ao cardápio geral',
  `access_type` ENUM('menu_view', 'product_view', 'qr_scan') NOT NULL DEFAULT 'menu_view',
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `referer` VARCHAR(500),
  `accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  INDEX `idx_restaurant_date` (`restaurant_id`, `accessed_at`),
  INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: master_admins (Administradores master)
-- =====================================================
CREATE TABLE IF NOT EXISTS `master_admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt da senha',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin master inicial (senha: admin123 - TROCAR EM PRODUÇÃO!)
-- Hash gerada com: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `master_admins` (`name`, `username`, `email`, `password_hash`) VALUES
('Administrador', 'admin', 'admin@cardapiofloripa.com.br', '$2y$10$dJnGPPHzUZG.MvjD6xKFxOmHQmCYz0zYp6qU3xX7a9dJ5mWqJBhiC');

-- =====================================================
-- TABELA: directory_restaurants (Guia Gastronômico)
-- =====================================================
CREATE TABLE IF NOT EXISTS `directory_restaurants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `address` TEXT,
  `neighborhood` VARCHAR(100),
  `city` VARCHAR(100) DEFAULT 'Florianópolis',
  `cuisine_types` JSON COMMENT 'Array de tipos de comida',
  `logo` VARCHAR(500),
  `phone` VARCHAR(30),
  `whatsapp` VARCHAR(30),
  `instagram` VARCHAR(100),
  `website` VARCHAR(255),
  `opening_hours` JSON COMMENT 'Objeto com horários por dia da semana',
  `price_range` ENUM('$', '$$', '$$$', '$$$$') DEFAULT '$$',
  `is_client` TINYINT(1) DEFAULT 0 COMMENT 'É cliente Cardápio Floripa?',
  `linked_restaurant_id` INT UNSIGNED NULL COMMENT 'ID do restaurante cliente se aplicável',
  `menu_url` VARCHAR(255) COMMENT 'URL do cardápio digital se for cliente',
  `status` ENUM('active', 'pending', 'draft') DEFAULT 'draft',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Destacar no topo do diretório',
  `internal_notes` TEXT COMMENT 'Notas internas para prospecção',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`linked_restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_neighborhood` (`neighborhood`),
  INDEX `idx_is_client` (`is_client`),
  INDEX `idx_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: cart_modes (Tipos de carrinho/pedido)
-- =====================================================
CREATE TABLE IF NOT EXISTS `cart_modes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `min_plan_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `default_settings` JSON COMMENT 'Configurações padrão do modo',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cart_modes` (`name`, `slug`, `description`, `min_plan_id`) VALUES
('Pedido WhatsApp', 'whatsapp', 'Envia pedido formatado para WhatsApp do restaurante', 1),
('Pedido Mesa', 'table', 'Cliente faz pedido vinculado a uma mesa', 2),
('Pedido Entrega', 'delivery', 'Pedido com dados de contato, pagamento na entrega', 2),
('Pedido Completo', 'full', 'Cadastro + pagamento online antes de confirmar', 3);

-- =====================================================
-- TABELA: restaurant_cart_modes (Modos ativos por restaurante)
-- =====================================================
CREATE TABLE IF NOT EXISTS `restaurant_cart_modes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `cart_mode_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `config` JSON COMMENT '{whatsapp_number, msg_header, estimated_times, etc}',
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cart_mode_id`) REFERENCES `cart_modes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_restaurant_mode` (`restaurant_id`, `cart_mode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: product_variations (Variações para pedido)
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_variations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `group_name` VARCHAR(100) NOT NULL COMMENT 'Ex: Borda, Adicional, Ponto da Carne',
  `is_required` TINYINT(1) DEFAULT 0 COMMENT 'Obrigatório selecionar?',
  `max_selections` INT DEFAULT 1 COMMENT '1=seleção única (radio), >1=múltipla (checkbox)',
  `sort_order` INT DEFAULT 0,
  `options` JSON NOT NULL COMMENT '[{"label":"Catupiry","price":5.00},{"label":"Cheddar","price":5.00}]',
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_product` (`product_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: orders (Pedidos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token público para acompanhamento',
  `cart_mode` VARCHAR(50) NOT NULL,
  `table_number` VARCHAR(20) DEFAULT NULL,
  `customer_name` VARCHAR(200),
  `customer_phone` VARCHAR(30),
  `customer_address` TEXT,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_status` ENUM('pending','paid','failed') DEFAULT NULL,
  `status` ENUM('pending','confirmed','preparing','ready','delivering','delivered','cancelled') DEFAULT 'pending',
  `subtotal` DECIMAL(10,2) DEFAULT 0,
  `delivery_fee` DECIMAL(10,2) DEFAULT 0,
  `total` DECIMAL(10,2) DEFAULT 0,
  `notes` TEXT,
  `status_history` JSON COMMENT '[{"status":"pending","at":"2025-01-01 12:00:00"},...]',
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Pedido arquivado pelo admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  INDEX `idx_restaurant_status` (`restaurant_id`, `status`),
  INDEX `idx_token` (`token`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: order_items (Itens do pedido)
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED,
  `product_name` VARCHAR(200) NOT NULL,
  `quantity` INT DEFAULT 1,
  `size_selected` VARCHAR(50) DEFAULT NULL,
  `size_price` DECIMAL(10,2) DEFAULT NULL,
  `variations_selected` JSON COMMENT '[{"group":"Borda","option":"Catupiry","price":5.00}]',
  `unit_price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `notes` VARCHAR(500),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ALTER: restaurants (novos campos para pedidos)
-- =====================================================
ALTER TABLE `restaurants` 
  ADD COLUMN `is_open` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Restaurante aceitando pedidos agora',
  ADD COLUMN `order_time_limits` JSON DEFAULT NULL COMMENT '{"pending":5,"preparing":20,"ready":10} em minutos';

-- =====================================================
-- TABELA: stock_images (Banco de Imagens compartilhado)
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(50) NOT NULL COMMENT 'bebidas, sobremesas, acompanhamentos, diversos',
  `name` VARCHAR(100) NOT NULL,
  `filename` VARCHAR(200) NOT NULL COMMENT 'Caminho relativo: bebidas/coca-cola.webp',
  `video_filename` VARCHAR(200) DEFAULT NULL COMMENT 'Caminho relativo do vídeo (NULL = sem vídeo)',
  `tags` VARCHAR(500) DEFAULT NULL COMMENT 'Palavras-chave para busca',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_category` (`category`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- View: Restaurantes com informações de plano e template
CREATE OR REPLACE VIEW `v_restaurants_full` AS
SELECT 
  r.*,
  p.name AS plan_name,
  p.slug AS plan_slug,
  p.max_products,
  p.max_categories,
  t.name AS template_name,
  t.slug AS template_slug,
  t.supports_video,
  t.supports_promo_price,
  DATEDIFF(r.expires_at, CURDATE()) AS days_until_expiration,
  CASE 
    WHEN r.expires_at < CURDATE() THEN 'expired'
    WHEN DATEDIFF(r.expires_at, CURDATE()) <= 30 THEN 'warning'
    ELSE 'ok'
  END AS expiration_status
FROM restaurants r
JOIN plans p ON r.plan_id = p.id
JOIN templates t ON r.template_id = t.id;

-- View: Estatísticas resumidas por restaurante
CREATE OR REPLACE VIEW `v_restaurant_stats` AS
SELECT 
  restaurant_id,
  COUNT(*) AS total_views,
  COUNT(CASE WHEN access_type = 'menu_view' THEN 1 END) AS menu_views,
  COUNT(CASE WHEN access_type = 'product_view' THEN 1 END) AS product_views,
  COUNT(CASE WHEN access_type = 'qr_scan' THEN 1 END) AS qr_scans,
  COUNT(CASE WHEN DATE(accessed_at) = CURDATE() THEN 1 END) AS views_today,
  COUNT(CASE WHEN accessed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) AS views_week,
  COUNT(CASE WHEN accessed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) AS views_month
FROM access_stats
GROUP BY restaurant_id;

-- View: Templates disponíveis por plano
CREATE OR REPLACE VIEW `v_templates_by_plan` AS
SELECT 
  p.id AS plan_id,
  p.name AS plan_name,
  p.slug AS plan_slug,
  t.id AS template_id,
  t.name AS template_name,
  t.slug AS template_slug,
  t.description AS template_description,
  t.supports_video,
  t.supports_promo_price
FROM plans p
JOIN templates t ON t.min_plan_id <= p.id
WHERE p.is_active = 1 AND t.is_active = 1
ORDER BY p.id, t.name;
