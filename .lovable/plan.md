

# Melhorias de UX/UI no Cardapio Mobile

## Problemas Identificados

1. **Barra de status do pedido nao atualiza automaticamente** - O OrderTracker ja faz polling a cada 15s, mas a barra pode nao estar refletindo mudancas visuais corretamente
2. **Icone do carrinho aparece sempre** - O botao flutuante fica visivel mesmo com carrinho vazio
3. **Cardapio visivel por baixo dos modais no mobile** - O modal de variacoes e o drawer do carrinho deixam o conteudo da pagina visivel por baixo no Safari/mobile
4. **Botao "Pedir" nao aparece no modal de variacoes** - O modal so tem "Adicionar", mas a barra do carrinho com "Pedir" aparece por baixo

## Solucao

### 1. Icone do carrinho: mostrar apenas quando tem itens

No `cart.js`, modificar `renderFloatingButton()` e `updateBadge()` para esconder o botao inteiro (nao so o badge) quando o carrinho esta vazio.

### 2. Corrigir modais no Safari mobile (cardapio aparecendo por baixo)

No `cart-styles.css`, adicionar `overscroll-behavior: contain` e garantir que o overlay dos modais cubra 100% da viewport. Tambem usar `-webkit-fill-available` para altura em iOS.

### 3. Garantir que a barra de status atualiza corretamente

A barra ja faz polling, mas vamos garantir que a atualizacao visual funcione de forma consistente, reconstruindo o HTML da barra corretamente apos cada poll.

### 4. Remover observacoes do modal de variacoes (ja feito anteriormente - confirmar)

Verificar que o campo "Observacoes" no modal de variacoes foi de fato removido na versao atual. Pela imagem do usuario, ainda aparece - remover se presente.

## Arquivos Modificados

| Arquivo | Mudanca |
|---------|---------|
| `docs/php/includes/cart.js` | Esconder botao flutuante quando carrinho vazio; remover campo observacoes do modal de variacoes |
| `docs/php/includes/cart-styles.css` | Fixes para Safari mobile: overscroll-behavior, altura 100dvh, isolamento dos modais |

## Detalhes Tecnicos

### cart.js - renderFloatingButton / updateBadge
- `updateBadge()`: alem de esconder o badge, esconder o `#cart-float` inteiro quando `count === 0`
- Garantir que ao adicionar item o botao aparece

### cart.js - modal de variacoes
- Remover o campo `Observacoes` que ainda aparece na imagem do usuario (linha com `var-notes`)

### cart-styles.css - fixes mobile
- `.variations-modal`, `.cart-drawer`: adicionar `overscroll-behavior: contain` no conteudo para impedir scroll do body por baixo
- `.variations-overlay`, `.cart-drawer-overlay`: garantir `z-index` adequado e cobertura total
- Usar `100dvh` (dynamic viewport height) para melhor suporte iOS

