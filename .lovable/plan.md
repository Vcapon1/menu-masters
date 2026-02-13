

# Plano: Auto-atualizar status do restaurante (aberto/fechado) sem recarregar

## Problema
Quando o admin fecha ou abre o restaurante pelo painel, o cliente que ja esta navegando no cardapio so ve a mudanca se recarregar a pagina manualmente.

## Solucao
Adicionar um polling leve no `cart.js` que verifica o status do restaurante a cada 60 segundos. Quando detecta mudanca (abriu ou fechou), atualiza a interface automaticamente sem reload.

## Alteracoes

### 1. Nova API: `docs/php/api/orders.php` - Endpoint publico de status do restaurante

Adicionar um novo case `restaurant_status` no switch que retorna apenas o `is_open` do restaurante:

```
GET /api/orders.php?action=restaurant_status&restaurant_id=123
Resposta: { "success": true, "is_open": true }
```

Endpoint leve, sem autenticacao, retorna apenas um boolean.

### 2. `docs/php/includes/cart.js` - Polling de status

Adicionar ao `Cart.init()` a chamada de um novo metodo `Cart.startStatusPolling()`:

- A cada 60 segundos, faz GET no endpoint acima
- Se `is_open` mudou em relacao ao estado atual (`IS_OPEN`):
  - **Fechou**: injeta o banner "Estamos fechados" e desabilita o botao de pedir
  - **Abriu**: remove o banner de fechado e reabilita os botoes
- Atualiza a variavel global `IS_OPEN` para manter consistencia

O polling so roda se `CART_MODE` estiver ativo (ou seja, o restaurante tem modo de pedido configurado).

## Detalhes Tecnicos

- Intervalo: 60 segundos (leve para o servidor)
- Nenhuma recarga de pagina necessaria
- O banner de "fechado" usa o mesmo `showClosedBanner()` ja existente
- Ao reabrir, o banner e removido e `IS_OPEN` volta a `true`

## Arquivos Modificados

| Arquivo | Acao | Detalhes |
|---------|------|---------|
| `docs/php/api/orders.php` | Modificar | Novo case `restaurant_status` (publico, retorna is_open) |
| `docs/php/includes/cart.js` | Modificar | Adicionar `startStatusPolling()` com polling 60s |

