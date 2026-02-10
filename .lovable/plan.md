

# Plano: Sistema Completo de Pedidos Modular

## Resumo

Implementar um sistema de pedidos modular com 4 modos independentes, painel de controle em tempo real para o restaurante, acompanhamento de status para o cliente, e controle de abertura/fechamento do estabelecimento.

## Os 4 Modos de Pedido

```text
┌───────────────────────────────────────────────────────────────────────┐
│                      MODOS DE PEDIDO                                 │
├───────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  MODO 1: WHATSAPP (?cart=whatsapp)                                    │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ Cliente monta carrinho --> Finaliza --> Monta mensagem          │  │
│  │ --> Abre wa.me com pedido formatado                             │  │
│  │ SEM banco de dados de pedidos. SEM painel.                     │  │
│  │ Restaurante recebe direto no WhatsApp.                         │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  MODO 2: MESA (?cart=table&mesa=5)                                    │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ Cliente informa mesa --> Monta carrinho --> Envia pedido        │  │
│  │ --> Salva no banco --> Aparece no Painel do Restaurante         │  │
│  │ --> Cliente recebe link de acompanhamento com status            │  │
│  │ SEM cadastro. SEM pagamento.                                   │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  MODO 3: ENTREGA (?cart=delivery)                                     │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ Cliente monta carrinho --> Informa nome/telefone/endereco      │  │
│  │ --> Envia pedido --> Pagamento na entrega                      │  │
│  │ --> Painel mostra pedido --> Status atualizado em tempo real    │  │
│  │ SEM pagamento online. SEM cadastro completo.                   │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  MODO 4: COMPLETO (?cart=full)                                        │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ Cliente se cadastra (nome, telefone, endereco)                 │  │
│  │ --> Monta carrinho --> Faz pagamento online                    │  │
│  │ --> Pedido só entra no painel APÓS pagamento confirmado        │  │
│  │ --> Status atualizado em tempo real                            │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                       │
└───────────────────────────────────────────────────────────────────────┘
```

## Fluxo Completo

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        FLUXO DO CLIENTE                               │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  1. Acessa cardapio: /pizzaria-bella?cart=table&mesa=5                 │
│                                                                        │
│  2. index.php verifica:                                                │
│     - Restaurante existe e esta ativo? (ja existe)                     │
│     - Restaurante esta ABERTO? (novo campo is_open)                   │
│       Se fechado: exibe aviso "Estamos fechados" mas permite ver       │
│       o cardapio sem botao de pedir                                    │
│     - Modo de carrinho e valido para este restaurante?                 │
│                                                                        │
│  3. Cardapio carrega com layout NORMAL do template                     │
│     - Cada card de produto ganha botao "Pedir" (se modo ativo)        │
│     - Icone flutuante do carrinho com contador de itens               │
│                                                                        │
│  4. Ao clicar "Pedir":                                                │
│     a) Produto SEM variacoes -> adiciona direto ao carrinho            │
│     b) Produto COM variacoes -> abre modal de opcoes:                  │
│        - Seleciona tamanho (se sizes_prices existe)                    │
│        - Seleciona variacoes (borda, adicional, etc)                   │
│        - Define quantidade                                             │
│        - Adiciona ao carrinho                                          │
│                                                                        │
│  5. Ao abrir carrinho flutuante:                                       │
│     - Lista itens com opcoes selecionadas                              │
│     - Permite editar quantidade ou remover                             │
│     - Mostra subtotal de cada item e total geral                      │
│     - Campo de observacoes gerais                                      │
│     - Botao "Finalizar Pedido"                                         │
│                                                                        │
│  6. Ao finalizar -> TELA DE CHECKOUT (layout do sistema, nao template)│
│     - Header com logo e nome do restaurante                           │
│     - Layout depende do modo:                                          │
│                                                                        │
│     WHATSAPP: Resumo -> Botao "Enviar pelo WhatsApp"                  │
│     MESA: Resumo + Numero da mesa -> Botao "Enviar Pedido"            │
│     ENTREGA: Resumo + Nome/Tel/Endereco -> Botao "Enviar Pedido"      │
│     COMPLETO: Resumo + Cadastro + Pagamento -> Botao "Pagar e Pedir" │
│                                                                        │
│  7. Apos envio (modos 2,3,4):                                         │
│     - Tela de confirmacao com numero do pedido                         │
│     - Link/pagina de acompanhamento: /pedido/{token}                  │
│     - Status em tempo real (polling a cada 15s)                        │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

## Painel de Controle do Restaurante

```text
┌────────────────────────────────────────────────────────────────────────┐
│                    PAINEL DE PEDIDOS (admin/orders.php)                │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ┌─── Header ───────────────────────────────────────────────────────┐  │
│  │  [Logo] Pizzaria Bella    [● ABERTO / ○ FECHADO]    [Sair]      │  │
│  │                                                                  │  │
│  │  Botao toggle ABERTO/FECHADO em destaque                        │  │
│  │  Ao fechar: cardapio continua visivel mas sem botoes de pedir   │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  ┌─── Filtros ──────────────────────────────────────────────────────┐  │
│  │  [Todos] [Pendentes (3)] [Preparando (2)] [Pronto (1)]          │  │
│  │  [Entregue] [Cancelado]                                         │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  ┌─── Lista de Pedidos ─────────────────────────────────────────────┐  │
│  │                                                                  │  │
│  │  ┌── Pedido #42 ── MESA 5 ─── 14:32 ─── ha 8 min ────────────┐ │  │
│  │  │  Status: [PENDENTE ▼]  (dropdown para alterar)              │ │  │
│  │  │                                                              │ │  │
│  │  │  1x Pizza Margherita (Grande) - Borda Catupiry     R$54,90  │ │  │
│  │  │  2x Coca-Cola 600ml                                R$16,00  │ │  │
│  │  │  Obs: Sem cebola na pizza                                   │ │  │
│  │  │                                                    ─────── │ │  │
│  │  │                                          Total: R$70,90     │ │  │
│  │  │                                                              │ │  │
│  │  │  ┌─ Alerta de tempo ─────────────────────────────────────┐  │ │  │
│  │  │  │  ⚠ Pendente ha 8 min (limite: 5 min)  [VERMELHO]     │  │ │  │
│  │  │  └──────────────────────────────────────────────────────┘  │ │  │
│  │  └──────────────────────────────────────────────────────────┘  │  │
│  │                                                                  │  │
│  │  ┌── Pedido #41 ── DELIVERY ─── 14:25 ─── ha 15 min ─────────┐ │  │
│  │  │  Status: [PREPARANDO ▼]   Cliente: João - (48)99999-9999   │ │  │
│  │  │  Endereco: Rua das Flores, 123 - Centro                    │ │  │
│  │  │  ...                                                        │ │  │
│  │  │  ┌─ Alerta de tempo ─────────────────────────────────────┐  │ │  │
│  │  │  │  ✓ Preparando ha 10 min (limite: 20 min)  [VERDE]    │  │ │  │
│  │  │  └──────────────────────────────────────────────────────┘  │ │  │
│  │  └──────────────────────────────────────────────────────────┘  │  │
│  │                                                                  │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  Atualiza automaticamente a cada 10 segundos (polling AJAX)           │
│  Som de alerta quando novo pedido chega                                │
│  Pedidos com tempo estourado ficam com borda vermelha                 │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

## Pagina de Acompanhamento do Cliente

```text
┌────────────────────────────────────────────────────────────────────────┐
│               ACOMPANHAMENTO: /pedido/{token}                         │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ┌──────────────────────────────────────────────────┐                  │
│  │        [Logo]                                     │                  │
│  │     Pizzaria Bella                                │                  │
│  │                                                   │                  │
│  │     Pedido #42 - Mesa 5                           │                  │
│  │                                                   │                  │
│  │     ● Recebido ──── ● Preparando ──── ○ Pronto   │                  │
│  │     14:32           14:35              --:--      │                  │
│  │                                                   │                  │
│  │     Status atual: PREPARANDO                      │                  │
│  │     Tempo estimado: ~15 minutos                   │                  │
│  │                                                   │                  │
│  │     ─────────────────────────────                 │                  │
│  │     1x Pizza Margherita (G) Borda Cat.  R$54,90   │                  │
│  │     2x Coca-Cola 600ml                  R$16,00   │                  │
│  │     ─────────────────────────────                 │                  │
│  │     Total: R$70,90                                │                  │
│  └──────────────────────────────────────────────────┘                  │
│                                                                        │
│  Atualiza automaticamente a cada 15 segundos                          │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

## Detalhes Tecnicos

### 1. Novas Tabelas SQL

```sql
-- Tipos de carrinho disponiveis no sistema
CREATE TABLE `cart_modes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `min_plan_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `default_settings` JSON COMMENT 'Config padrao do modo',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `cart_modes` (`name`, `slug`, `description`, `min_plan_id`) VALUES
('Pedido WhatsApp', 'whatsapp', 'Envia pedido formatado para WhatsApp do restaurante', 1),
('Pedido Mesa', 'table', 'Cliente faz pedido vinculado a uma mesa', 2),
('Pedido Entrega', 'delivery', 'Pedido com dados de contato, pagamento na entrega', 2),
('Pedido Completo', 'full', 'Cadastro + pagamento online antes de confirmar', 3);

-- Modos ativos por restaurante (um restaurante pode ter varios)
CREATE TABLE `restaurant_cart_modes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `cart_mode_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `config` JSON COMMENT '{whatsapp_number, msg_header, estimated_times, etc}',
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cart_mode_id`) REFERENCES `cart_modes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_restaurant_mode` (`restaurant_id`, `cart_mode_id`)
);

-- Variacoes de produto para pedido (borda, adicional, ponto da carne)
CREATE TABLE `product_variations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `group_name` VARCHAR(100) NOT NULL COMMENT 'Ex: Borda, Adicional, Ponto',
  `is_required` TINYINT(1) DEFAULT 0 COMMENT 'Obrigatorio selecionar?',
  `max_selections` INT DEFAULT 1 COMMENT '1=selecao unica, >1=multipla',
  `sort_order` INT DEFAULT 0,
  `options` JSON NOT NULL COMMENT '[{"label":"Catupiry","price":5.00},{"label":"Cheddar","price":5.00}]',
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_product` (`product_id`, `sort_order`)
);

-- Pedidos (modos table, delivery, full)
CREATE TABLE `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token para acompanhamento publico',
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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  INDEX `idx_restaurant_status` (`restaurant_id`, `status`),
  INDEX `idx_token` (`token`),
  INDEX `idx_created` (`created_at`)
);

CREATE TABLE `order_items` (
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
);

-- Adicionar campo is_open na tabela restaurants
ALTER TABLE `restaurants` ADD COLUMN `is_open` TINYINT(1) NOT NULL DEFAULT 1 
  COMMENT 'Restaurante aceitando pedidos agora';

-- Adicionar configuracoes de tempo por etapa
ALTER TABLE `restaurants` ADD COLUMN `order_time_limits` JSON DEFAULT NULL
  COMMENT '{"pending":5,"preparing":20,"ready":10} em minutos';
```

### 2. Alteracoes no Roteador (index.php)

```php
// Apos carregar restaurante, verificar modo de carrinho
$cartMode = null;
$cartSlug = isset($_GET['cart']) ? sanitize($_GET['cart']) : '';
if (!empty($cartSlug)) {
    $cartMode = getRestaurantCartMode($restaurant['id'], $cartSlug);
}
$tableNumber = isset($_GET['mesa']) ? sanitize($_GET['mesa']) : null;
$isOpen = (bool)$restaurant['is_open'];

// Passar para o template: $cartMode, $tableNumber, $isOpen
```

### 3. Novas Funcoes em functions.php

- `getRestaurantCartMode($restaurantId, $modeSlug)` - busca modo ativo
- `getProductVariations($productId)` - busca variacoes do produto
- `createOrder($data)` - cria pedido no banco
- `getOrderByToken($token)` - busca pedido por token publico
- `updateOrderStatus($orderId, $status)` - atualiza status com historico
- `getRestaurantOrders($restaurantId, $status)` - lista pedidos para painel
- `toggleRestaurantOpen($restaurantId, $isOpen)` - abre/fecha restaurante
- `generateOrderToken()` - gera token unico para acompanhamento

### 4. JavaScript Compartilhado (includes/cart.js)

Arquivo unico carregado por todos os templates quando `$cartMode` esta ativo:
- Gerenciamento de sessao do carrinho (localStorage)
- Adicionar/remover/editar itens
- Modal de variacoes (selecionar tamanho, borda, extras)
- Drawer do carrinho flutuante
- Calculo de totais
- Finalizacao por modo (wa.me para whatsapp, POST para os demais)

### 5. Checkout Padronizado (includes/checkout.php)

Tela de finalizacao com layout do sistema (nao do template):
- Header com logo + nome do restaurante
- Resumo do pedido
- Campos especificos por modo (mesa, endereco, cadastro, pagamento)
- Botao de finalizar
- Redirecionamento para pagina de acompanhamento

### 6. Pagina de Acompanhamento (order-track.php)

- Rota: `/pedido/{token}`
- Mostra status atual com timeline visual
- Polling AJAX a cada 15 segundos
- Exibe itens do pedido e total

### 7. Painel de Pedidos (admin/orders.php)

- Rota dedicada acessada pelo admin do restaurante
- Botao ABERTO/FECHADO no header (toggle via AJAX)
- Lista de pedidos com filtros por status
- Cada pedido mostra: numero, mesa/cliente, itens, total, tempo
- Dropdown para alterar status (dispara update no banco)
- Alerta de tempo: calcula diferenca entre `created_at` do status atual e os limites configurados em `order_time_limits`
- Borda vermelha quando tempo estourado, amarela quando proximo
- Polling AJAX a cada 10 segundos para atualizar lista
- Som de notificacao quando novo pedido chega

### 8. Alteracoes no Admin de Produtos (products.php)

Adicionar secao "Variacoes para Pedido" no formulario:
- Botao "+ Grupo de Variacao" (ex: Borda, Adicional, Ponto da Carne)
- Para cada grupo: nome, obrigatorio (sim/nao), max selecoes
- Lista de opcoes com label e preco adicional
- Salvar como registros na tabela `product_variations`

### 9. Alteracoes no Master Admin (restaurants.php)

Adicionar secao "Modulos de Pedido" no formulario do restaurante:
- Checkboxes com modos disponiveis (filtrados pelo plano)
- Config especifica por modo (ex: numero WhatsApp, limites de tempo)
- Campo de tempo estimado por etapa (pendente, preparando, pronto)

## Arquivos a Criar/Modificar

| Arquivo | Acao | Descricao |
|---------|------|-----------|
| `docs/database/schema.sql` | Modificar | 4 novas tabelas + ALTER restaurants |
| `docs/php/index.php` | Modificar | Ler GET cart/mesa, carregar modo, is_open |
| `docs/php/includes/functions.php` | Modificar | ~8 novas funcoes |
| `docs/php/includes/cart.js` | Criar | JS do carrinho (localStorage, modal, drawer) |
| `docs/php/includes/checkout.php` | Criar | Tela de checkout padronizada |
| `docs/php/order-track.php` | Criar | Pagina de acompanhamento do pedido |
| `docs/php/api/orders.php` | Criar | API JSON para criar/buscar/atualizar pedidos |
| `docs/php/admin/orders.php` | Criar | Painel de pedidos do restaurante |
| `docs/php/admin/products.php` | Modificar | Adicionar secao de variacoes |
| `docs/php/master/restaurants.php` | Modificar | Config de modos + limites de tempo |
| `docs/php/templates/hero/template.php` | Modificar | Botao pedir + include cart.js |
| `docs/php/templates/appetite/template.php` | Modificar | Botao pedir + include cart.js |
| `docs/php/templates/classic/template.php` | Modificar | Botao pedir + include cart.js |
| `docs/php/templates/bold/template.php` | Modificar | Botao pedir + include cart.js |

## Fases de Implementacao

Devido a complexidade, implementar em fases progressivas:

**Fase 1 - Infraestrutura + WhatsApp (mais simples, sem banco de pedidos)**
1. Criar tabelas: cart_modes, restaurant_cart_modes, product_variations
2. ALTER restaurants: is_open, order_time_limits
3. Novas funcoes em functions.php
4. Alterar index.php para ler parametros cart/mesa
5. Criar cart.js (carrinho em localStorage)
6. Adicionar variacoes no admin de produtos (products.php)
7. Integrar botao "Pedir" e carrinho flutuante no template Hero
8. Implementar finalizacao WhatsApp (monta mensagem, abre wa.me)

**Fase 2 - Pedido Mesa + Painel**
9. Criar tabelas: orders, order_items
10. Criar api/orders.php (endpoints JSON)
11. Criar admin/orders.php (painel com polling)
12. Implementar toggle ABERTO/FECHADO
13. Criar order-track.php (acompanhamento do cliente)
14. Implementar alertas de tempo no painel
15. Implementar modo table no checkout

**Fase 3 - Delivery + Dados do Cliente**
16. Implementar checkout de entrega (nome, telefone, endereco)
17. Adicionar status "delivering" ao fluxo
18. Config de modos no master admin

**Fase 4 - Completo + Pagamento**
19. Implementar cadastro do cliente
20. Integrar gateway de pagamento
21. Fluxo de confirmacao pos-pagamento

**Fase 5 - Replicar Templates**
22. Adaptar Appetite, Classic e Bold com mesma logica do Hero

