
# Plano: Barra de Status do Pedido no Topo do Cardapio

## Conceito

Apos o cliente finalizar um pedido, o token do pedido e salvo no `localStorage`. Ao navegar no cardapio, o sistema detecta esse token e exibe uma barra fixa e compacta no topo da pagina com o status atual do pedido, atualizada automaticamente via polling. O cliente acompanha o pedido de forma passiva sem sair do cardapio.

## Fluxo

```text
Cliente faz pedido
    |
    v
checkout.php salva token no localStorage
    |
    v
Cliente volta ao cardapio (/{slug}?cart=...)
    |
    v
cart.js detecta token salvo no localStorage
    |
    v
Exibe barra fixa no topo com status do pedido
    |
    v
Polling a cada 15s atualiza o status via API
    |
    v
Quando status = "delivered" ou "cancelled"
    -> Barra some apos 30 segundos
    -> Remove token do localStorage
```

## Alteracoes

### 1. `docs/php/checkout.php` - Salvar token do pedido

Apos o pedido ser criado com sucesso (linha 338-348), salvar o token retornado no localStorage:

```javascript
// Apos data.success
localStorage.setItem('active_order_' + RESTAURANT_ID, JSON.stringify({
    token: data.order.token,
    orderId: data.order.id,
    createdAt: new Date().toISOString()
}));
```

### 2. `docs/php/includes/cart.js` - Barra de status no cardapio

Adicionar ao objeto `Cart` um novo modulo `OrderTracker` com as seguintes funcoes:

**`OrderTracker.init()`** - Chamado no `Cart.init()`:
- Verifica se existe `active_order_{restaurantId}` no localStorage
- Se existir, chama `OrderTracker.show()`

**`OrderTracker.show()`**:
- Cria um elemento fixo no topo da pagina (acima do header sticky)
- Layout compacto (altura ~48px):

```text
┌──────────────────────────────────────────────────┐
│  Pedido #42  ●  Preparando       [Acompanhar ->] │
└──────────────────────────────────────────────────┘
```

- Fundo escuro com borda sutil (`background: rgba(30,30,30,0.95)`)
- Dot colorido animado conforme status:
  - pending/confirmed: amarelo (pulsando)
  - preparing: laranja (pulsando)
  - ready: verde
  - delivering: azul
  - delivered: verde (fixo)
  - cancelled: vermelho
- Texto do status traduzido (Recebido, Preparando, Pronto, etc.)
- Link "Acompanhar" que abre a pagina `/pedido/{token}` em nova aba
- Botao "X" discreto para fechar/minimizar a barra

**`OrderTracker.poll()`**:
- Faz requisicao GET para `/api/orders.php?action=status&token={token}` a cada 15 segundos
- Atualiza o texto do status e a cor do dot
- Se status for `delivered` ou `cancelled`:
  - Mostra mensagem final ("Pedido entregue!" ou "Pedido cancelado")
  - Apos 30 segundos, remove a barra e limpa o localStorage

**`OrderTracker.dismiss()`**:
- Remove a barra do DOM
- Nao remove do localStorage (o pedido continua ativo, a barra reaparece ao recarregar)
- Se o usuario clicar segurando Shift ou clicar num botao "Encerrar", ai sim limpa o localStorage

### 3. `docs/php/includes/cart-styles.css` - Estilos da barra

Adicionar estilos para:
- `.order-tracker-bar`: barra fixa no topo (`position: fixed; top: 0; z-index: 9999`)
- `.order-tracker-dot`: bolinha de status com animacao pulse
- `.order-tracker-dismiss`: botao de fechar discreto
- Transicao de entrada (slide-down) e saida (slide-up)
- Ajustar `body` com `padding-top` quando a barra esta visivel para nao sobrepor o header

### 4. `docs/php/index.php` - Nenhuma alteracao necessaria

O `cart.js` ja e carregado quando `$cartMode` esta ativo. O tracker roda automaticamente dentro do `Cart.init()`.

## Detalhes Tecnicos

**Chave do localStorage:**
- `active_order_{restaurantId}` - garante que cada restaurante tem sua propria sessao de pedido
- Contem: `{ token, orderId, createdAt }`
- Expira automaticamente apos 4 horas (fallback de seguranca caso o polling falhe)

**Polling:**
- Intervalo: 15 segundos (mesmo da pagina de rastreamento)
- Usa a mesma API existente: `/api/orders.php?action=status&token=xxx`
- Para automaticamente quando status e final (delivered/cancelled) ou apos 4 horas

**Z-index:**
- Barra: `z-index: 9999` (acima de tudo)
- Header do template: geralmente `z-index: 40-50`
- O body recebe `padding-top: 48px` dinamicamente quando a barra aparece

## Arquivos Modificados

| Arquivo | Acao | Detalhes |
|---------|------|---------|
| `docs/php/checkout.php` | Modificar | Salvar token no localStorage apos sucesso |
| `docs/php/includes/cart.js` | Modificar | Adicionar modulo OrderTracker com barra e polling |
| `docs/php/includes/cart-styles.css` | Modificar | Estilos da barra de status |

Somente arquivos PHP/JS/CSS. Nenhum arquivo React sera alterado.
