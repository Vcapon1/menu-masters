
# Plano: Link de Pedidos + QR Code + Mesa informada pelo cliente

## Mudança de Lógica da Mesa

O número da mesa **não será mais embutido no QR Code**. Em vez disso:
- O QR Code do modo Mesa gera apenas `/{slug}?cart=table` (sem `&mesa=`)
- O cliente informa o número da mesa no **drawer do carrinho** ou na **página de checkout**, antes de enviar o pedido
- Isso significa **um único QR Code** para todas as mesas do restaurante

## Alterações

### 1. `docs/php/admin/index.php` - Adicionar botão "Pedidos"

Na seção Quick Actions, adicionar um botão vermelho para `orders.php`:
- Texto: "Pedidos"
- Cor: `bg-red-600`
- Link: `orders.php`

### 2. `docs/php/admin/qrcode.php` - Reescrever com seletor de modo

A página será reescrita para gerar QR Codes com o parâmetro `?cart=` correto:

**Dados PHP:**
- Buscar modos habilitados do restaurante via `restaurant_cart_modes` JOIN `cart_modes`

**Interface:**

```text
TIPO DE LINK
( ) Apenas cardápio (sem pedidos)
( ) WhatsApp
( ) Mesa
( ) Entrega
( ) Completo
(Somente modos habilitados aparecem)

URL GERADA
[ https://site.com/slug?cart=table        ] [Copiar]

        [QR CODE 400x400]

[Baixar PNG]  [Baixar SVG]

Dicas de uso
```

**Sem geração em lote** - como a mesa é informada pelo cliente, basta um QR Code por modo.

**URLs geradas por modo:**
- Sem pedidos: `/{slug}`
- WhatsApp: `/{slug}?cart=whatsapp`
- Mesa: `/{slug}?cart=table` (sem mesa no link)
- Entrega: `/{slug}?cart=delivery`
- Completo: `/{slug}?cart=full`

**JavaScript:**
- `updateQRCode()`: Atualiza preview e URL quando o radio muda
- `copyUrl()`: Copia URL para clipboard

### 3. `docs/php/includes/cart.js` - Campo de mesa no carrinho

No drawer do carrinho, quando o modo for `table`:
- Exibir um campo de input "Qual sua mesa?" no topo do carrinho
- Campo obrigatório antes de prosseguir para o checkout
- Salvar o valor no `localStorage` junto com os dados do checkout

### 4. `docs/php/checkout.php` - Mesa informada pelo cliente

No modo `table`, o campo de mesa deixa de ser `readonly` com valor do URL:
- Remove o `readonly` do input de mesa
- Preenche com o valor salvo no localStorage (se houver)
- Torna obrigatório na validação antes de enviar

### 5. `docs/php/index.php` - Remover dependência do parâmetro mesa

- O roteador deixa de exigir `?mesa=` na URL
- O parâmetro `mesa` no GET continua funcionando como fallback (retrocompatibilidade), mas não é mais obrigatório

## Arquivos Modificados

| Arquivo | Acao | Detalhes |
|---------|------|---------|
| `docs/php/admin/index.php` | Modificar | Adicionar botao "Pedidos" |
| `docs/php/admin/qrcode.php` | Reescrever | Seletor de modo, QR dinamico, sem lote |
| `docs/php/includes/cart.js` | Modificar | Campo "Qual sua mesa?" no drawer |
| `docs/php/checkout.php` | Modificar | Campo mesa editavel pelo cliente |
| `docs/php/index.php` | Modificar | Remover obrigatoriedade do parametro mesa |

Nenhum arquivo React sera alterado. Todas as mudancas sao exclusivamente PHP.
