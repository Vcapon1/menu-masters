

# Melhorias de UX/UI no Cardapio Mobile ✅ CONCLUÍDO

## Alterações Realizadas

### 1. ✅ Ícone do carrinho: só aparece quando tem itens
- `updateBadge()` agora esconde `#cart-float` inteiro quando `count === 0`

### 2. ✅ Modais no Safari mobile: cardápio não aparece mais por baixo
- Overlay escurecido de 0.7 → 0.85 com blur de 8px
- `overscroll-behavior: contain` nos modais e conteúdos
- `max-height` com fallback `dvh` para iOS

### 3. ✅ Campo Observações removido do modal de variações
- Referência ao `#var-notes` removida de `confirmVariations()`

### 4. ✅ Barra de status do pedido (OrderTracker)
- Já funciona com polling 15s e reconstrói HTML a cada poll (já estava correto)

