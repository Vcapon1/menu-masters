

# Template Pizzaria com Pizza Builder Integrado

## Visao Geral

Criar um template dedicado para pizzarias chamado **"Pizzaria"** (slug: `pizzaria`), com visual tematico e o sistema de montagem multi-sabores embutido. Nenhum template existente sera modificado.

## Visual do Template

- **Tema**: Fundo escuro quente (#1a1410), acentos em vermelho pizzaria (#c0392b) e dourado (#d4a574)
- **Tipografia**: Playfair Display (serif) para titulos, Inter para corpo
- **Hero**: Banner compacto (25vh) com overlay escuro, logo centralizado
- **Cards**: Estilo horizontal (imagem a esquerda, info a direita) - ideal para pizzas que sao visualmente similares
- **Destaque**: Botao "Montar Pizza" proeminente nas categorias habilitadas, com icone de pizza

## Funcionalidade Multi-Sabores

O Pizza Builder sera um metodo novo no `cart.js`, disponivel para qualquer template mas acionado apenas pelo template Pizzaria inicialmente.

### Fluxo do Cliente

```text
1. Clica "Montar Pizza" na categoria
         |
2. Seleciona TAMANHO (ex: Broto, Media, Grande)
   -> Sistema verifica quantos sabores o tamanho permite
   -> Se permite apenas 1: mostra lista de sabores como pedido normal
         |
3. Seleciona SABORES (checkboxes com imagem + nome)
   -> Limite visual: "2/3 sabores selecionados"
   -> Preco exibido = maior preco entre os selecionados
         |
4. Confirma e adiciona ao carrinho como item unico
```

### No Carrinho e WhatsApp

```text
1x Pizza Grande (3 sabores) - R$65,90
   - Calabresa
   - Portuguesa  
   - Quatro Queijos
```

## Arquivos a Criar/Modificar

| Arquivo | Acao | Descricao |
|---------|------|-----------|
| `docs/database/schema.sql` | Modificar | Adicionar colunas `allow_multi_flavor` e `flavor_config` na tabela `categories` + INSERT do template pizzaria |
| `docs/php/admin/categories.php` | Modificar | Adicionar campos "Multi-sabor" e configuracao de sabores por tamanho no formulario |
| `docs/php/includes/cart.js` | Modificar | Adicionar metodo `Cart.openPizzaBuilder()` com modal em etapas |
| `docs/php/includes/cart-styles.css` | Modificar | Adicionar estilos do modal Pizza Builder |
| `docs/php/index.php` | Modificar | Exportar dados multi-flavor para variavel JS global |
| `docs/php/templates/pizzaria/template.php` | Criar | Template completo com visual de pizzaria e botao "Montar Pizza" |
| `src/lib/templatePresets.ts` | Modificar | Adicionar preset de cores do template pizzaria |
| `docs/php/master/templates.php` | Modificar | Registrar icone do template |

## Detalhes Tecnicos

### Banco de Dados

Duas colunas novas na tabela `categories`:

```sql
ALTER TABLE categories
  ADD COLUMN allow_multi_flavor TINYINT(1) DEFAULT 0,
  ADD COLUMN flavor_config JSON DEFAULT NULL;
```

O `flavor_config` armazena o limite de sabores por tamanho:

```json
{"Broto": 1, "Media": 2, "Grande": 3}
```

As chaves correspondem aos labels de `sizes_prices` dos produtos. Se um tamanho nao esta no config, assume 1 sabor.

INSERT do template:

```sql
INSERT INTO templates (slug, name, min_plan_id, is_active, supports_video, supports_promo_price, has_grid_view, has_list_view)
VALUES ('pizzaria', 'Pizzaria', 1, 1, 1, 1, 0, 1);
```

### Admin de Categorias

Ao ativar o checkbox "Permitir multi-sabor", aparece uma area onde o admin define quantos sabores cada tamanho permite. Os tamanhos sao extraidos automaticamente dos `sizes_prices` dos produtos daquela categoria.

### Cart.js - openPizzaBuilder(categoryId)

Novo metodo que:

1. Le os dados de `MULTI_FLAVOR_CATEGORIES[categoryId]` (variavel global injetada pelo PHP)
2. Renderiza modal com etapa de tamanho (radios)
3. Ao selecionar tamanho, verifica `flavorConfig[tamanho]`:
   - Se == 1: fecha o builder e mostra os produtos normalmente
   - Se > 1: avanca para etapa de selecao de sabores com checkboxes visuais (imagem + nome + preco)
4. Calcula preco pelo sabor mais caro no tamanho selecionado
5. Adiciona ao carrinho via `Cart.addItem()` existente, com sabores listados como variations

### index.php - Variavel Global

```javascript
var MULTI_FLAVOR_CATEGORIES = {
  "5": {
    flavorConfig: {"Broto": 1, "Media": 2, "Grande": 3},
    products: [
      {id: 10, name: "Calabresa", image: "...", sizesPrices: [{"label":"Broto","price":29.90}, ...]}
    ]
  }
};
```

### Template Pizzaria - Estrutura

- Hero compacto com parallax sutil
- Navegacao por categorias em chips horizontais (mesmo padrao dos outros templates)
- Cards em formato lista horizontal (imagem 1:1 a esquerda, info a direita)
- Nas categorias com `allow_multi_flavor`, exibe botao "Montar Pizza" logo abaixo do titulo da categoria
- Modal de detalhes do produto similar ao Zen (limpo, com botao Pedir integrado ao cart.js)
- Integracao total com cart.js (variações, tamanhos, carrinho, WhatsApp)

### Preset de Cores

```javascript
pizzaria: {
  primaryColor: "#c0392b",
  secondaryColor: "#d4a574",
  accentColor: "#e74c3c",
  buttonColor: "#c0392b",
  buttonTextColor: "#ffffff",
  fontColor: "#faf5f0",
  description: "Tema quente para pizzarias - vermelho com dourado"
}
```

