

# Plano: Suporte a Múltiplos Tamanhos/Preços para Pizzarias

## Resumo

Adicionar suporte a preços variáveis por tamanho (P/M/G) para produtos, ideal para pizzarias. A solução utiliza um campo JSON no banco de dados para armazenar os tamanhos e seus preços, mantendo compatibilidade total com produtos que usam preço único.

## Abordagem

A estratégia de **mínimo impacto** utiliza:

1. **Um novo campo JSON opcional** na tabela de produtos para armazenar tamanhos
2. **Lógica condicional** nos templates: se existirem tamanhos, exibe-os; senão, mantém o comportamento atual
3. **Formulário adaptável** no admin: toggle para ativar/desativar tamanhos por produto

```text
┌─────────────────────────────────────────────────────────────┐
│                    FLUXO DA SOLUÇÃO                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  BANCO DE DADOS                                             │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ products.sizes_prices (JSON, nullable)              │    │
│  │                                                     │    │
│  │ NULL = preço único (campo price)                    │    │
│  │ JSON = múltiplos tamanhos:                          │    │
│  │   [                                                 │    │
│  │     {"label": "Pequena", "price": 29.90},           │    │
│  │     {"label": "Média", "price": 39.90},             │    │
│  │     {"label": "Grande", "price": 49.90}             │    │
│  │   ]                                                 │    │
│  └─────────────────────────────────────────────────────┘    │
│                           │                                 │
│                           ▼                                 │
│  ADMIN DO RESTAURANTE                                       │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ [ ] Produto com tamanhos variáveis                  │    │
│  │                                                     │    │
│  │ Se marcado:                                         │    │
│  │ ┌─────────────┬─────────────┬─────────────┐         │    │
│  │ │ Pequena     │ Média       │ Grande      │         │    │
│  │ │ R$ [29.90]  │ R$ [39.90]  │ R$ [49.90]  │         │    │
│  │ └─────────────┴─────────────┴─────────────┘         │    │
│  │                                                     │    │
│  │ Se desmarcado:                                      │    │
│  │ Preço: R$ [_____] (comportamento atual)             │    │
│  └─────────────────────────────────────────────────────┘    │
│                           │                                 │
│                           ▼                                 │
│  TEMPLATES (Cardápio)                                       │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Se sizes_prices existe:                             │    │
│  │   "Pizza Margherita"                                │    │
│  │   P: R$29,90 | M: R$39,90 | G: R$49,90              │    │
│  │                                                     │    │
│  │ Se sizes_prices é NULL:                             │    │
│  │   "Hambúrguer Clássico"                             │    │
│  │   R$38,90 (comportamento atual)                     │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Detalhes Técnicos

### 1. Alteração no Banco de Dados

```sql
ALTER TABLE `products` 
  ADD COLUMN `sizes_prices` JSON DEFAULT NULL 
  COMMENT 'Preços por tamanho: [{"label": "P", "price": 29.90}, ...]';
```

### 2. Modificações no Admin de Produtos (`products.php`)

- Adicionar checkbox "Produto com tamanhos variáveis"
- Campos dinâmicos para P/M/G que aparecem quando checkbox está marcado
- Esconder campo de preço único quando tamanhos estão ativos
- Processar e salvar como JSON no backend

### 3. Modificações nos Templates

Adicionar lógica condicional para exibir preços:

```php
<?php 
$sizes = json_decode($product['sizes_prices'] ?? 'null', true);
if ($sizes && is_array($sizes) && count($sizes) > 0): 
?>
    <div class="product-sizes">
        <?php foreach ($sizes as $size): ?>
            <span class="size-price">
                <?= htmlspecialchars($size['label']) ?>: 
                R$ <?= number_format($size['price'], 2, ',', '.') ?>
            </span>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Preço único (código atual) -->
    <span class="product-price">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
<?php endif; ?>
```

### 4. Arquivos a Modificar

| Arquivo | Mudança |
|---------|---------|
| `docs/database/schema.sql` | Adicionar coluna `sizes_prices` |
| `docs/php/admin/products.php` | Form e lógica para tamanhos |
| `docs/php/templates/hero/template.php` | Exibição de preços por tamanho |
| `docs/php/templates/appetite/template.php` | Exibição de preços por tamanho |
| `docs/php/templates/classic/template.php` | Exibição de preços por tamanho |
| `docs/php/templates/bold/template.php` | Exibição de preços por tamanho |

### 5. Design Visual nos Cards

```text
┌─────────────────────────────────────────┐
│  [Imagem da Pizza]                      │
│                                         │
│  Pizza 4 Queijos                        │
│  Blend de mussarela, parmesão...        │
│                                         │
│  ┌─────────────────────────────────┐    │
│  │  P R$29,90 │ M R$39,90 │ G R$49,90│  │
│  └─────────────────────────────────┘    │
└─────────────────────────────────────────┘
```

### 6. Design Visual no Modal

No modal de detalhes, os tamanhos podem ser exibidos como botões/chips selecionáveis para melhor UX, destacando visualmente as opções disponíveis.

## Vantagens desta Abordagem

1. **Zero impacto** em produtos existentes (campo é opcional/nullable)
2. **Flexível**: suporta qualquer quantidade de tamanhos (P/M/G, Individual/Família, etc.)
3. **Labels customizáveis**: o restaurante define os nomes (Broto, Média, Grande, Gigante...)
4. **Reutilizável**: não apenas pizzarias - serve para qualquer produto com variações

## Etapas de Implementação

1. Criar migration SQL para adicionar coluna
2. Atualizar formulário de produtos no admin
3. Atualizar template Hero com exibição de tamanhos
4. Replicar para demais templates (Appetite, Classic, Bold)
5. Testar com dados de pizzaria real

