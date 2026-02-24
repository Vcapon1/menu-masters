

# Sistema de Pagamento com Stripe Connect

## Resumo

Implementar pagamento online no checkout do cardápio digital usando **Stripe Connect** com suporte a **cartão de crédito e Pix**. O sistema terá dois modelos de repasse configurados pelo Master Admin por restaurante.

---

## Modelos de Pagamento

### Modelo 1 — Comissionado (6%)
- 6% do valor do pedido fica com a plataforma (liquido)
- Taxa do Stripe e descontada do restaurante
- Exemplo: pedido R$100 -> Plataforma recebe R$6,00 -> Restaurante recebe R$100 - R$6 - taxa Stripe

### Modelo 2 — Full (100% repasse)
- 100% do valor vai para o restaurante
- Taxa do Stripe e descontada do restaurante
- Plataforma nao cobra nada (receita vem do plano mensal)

---

## Arquitetura Tecnica

### 1. Banco de Dados (MySQL - PHP)

Novas colunas na tabela `restaurants`:

```text
restaurants
  +-- stripe_account_id       VARCHAR(255)   -- ID da conta conectada (acct_xxx)
  +-- stripe_account_status   ENUM('pending','active','restricted')
  +-- payment_model           ENUM('commission','full') DEFAULT 'commission'
  +-- platform_fee_percent    DECIMAL(5,2) DEFAULT 6.00
```

Nova tabela para registro de pagamentos:

```text
payments
  +-- id                  INT AUTO_INCREMENT PK
  +-- order_id            INT FK -> orders
  +-- restaurant_id       INT FK -> restaurants
  +-- stripe_payment_id   VARCHAR(255)
  +-- amount              DECIMAL(10,2)
  +-- platform_fee        DECIMAL(10,2)
  +-- gateway_fee         DECIMAL(10,2)
  +-- net_restaurant      DECIMAL(10,2)
  +-- payment_method      ENUM('card','pix')
  +-- status              ENUM('pending','processing','succeeded','failed','refunded')
  +-- paid_at             DATETIME
  +-- created_at          DATETIME DEFAULT CURRENT_TIMESTAMP
```

### 2. Stripe Connect - Fluxo de Onboarding

Cada restaurante precisa ter uma **conta conectada** no Stripe. O fluxo:

1. Master Admin cadastra restaurante e define o modelo (commission/full)
2. No painel Admin do restaurante, aparece botao "Configurar Recebimentos"
3. Clica e e redirecionado ao Stripe Connect Onboarding (formulario do proprio Stripe)
4. Stripe valida dados bancarios e ativa a conta
5. Status atualiza para `active` e pagamentos ficam habilitados

### 3. Edge Functions (Lovable Cloud)

**a) `stripe-onboarding`** — Cria conta conectada e gera link de onboarding

```text
POST /stripe-onboarding
Body: { restaurant_id, return_url }
Response: { account_id, onboarding_url }
```

**b) `stripe-create-payment`** — Cria PaymentIntent com split

```text
POST /stripe-create-payment
Body: { order_id, restaurant_id, amount, payment_method_type }
Response: { client_secret, payment_intent_id }
```

Logica do split:
- Busca `payment_model` e `platform_fee_percent` do restaurante
- Se `commission`: cria PaymentIntent com `application_fee_amount`
- Se `full`: cria PaymentIntent sem fee, `transfer_data` direto para a conta

**c) `stripe-webhook`** — Recebe eventos do Stripe

```text
POST /stripe-webhook
Eventos: payment_intent.succeeded, payment_intent.payment_failed
Atualiza status do pagamento e do pedido
```

### 4. Alteracoes no Checkout (PHP)

O arquivo `checkout.php` sera atualizado:

- Detectar se o modo e `full` (pagamento online) ou se o restaurante tem pagamento habilitado
- Carregar Stripe.js no frontend
- Mostrar opcoes: Cartao de Credito ou Pix
- Para **Cartao**: exibir Stripe Elements (formulario do Stripe)
- Para **Pix**: exibir QR Code gerado pelo Stripe
- Apos pagamento confirmado, redirecionar para pagina de sucesso com rastreamento

### 5. Painel Master Admin

No formulario de restaurante (`master/restaurants.php`):

- Novo campo: **Modelo de Pagamento** (select: Comissionado 6% / Full 100%)
- Campo editavel para percentual de comissao (padrao 6%)
- Exibir status da conta Stripe do restaurante (pendente/ativo/restrito)
- Botao para resetar conta Stripe se necessario

### 6. Painel Admin do Restaurante

No dashboard (`admin/index.php`):

- Card de "Recebimentos" mostrando status da conta Stripe
- Se nao configurado: botao "Configurar Recebimentos" que inicia o onboarding
- Se ativo: resumo de recebimentos (total recebido, pendente, ultimo repasse)
- Na pagina de pedidos: exibir status do pagamento em cada pedido

---

## Fluxo Completo do Cliente

```text
1. Cliente monta carrinho no cardapio
2. Vai para checkout (modo 'full' ou com pagamento habilitado)
3. Preenche dados pessoais e endereco
4. Escolhe forma de pagamento: Cartao ou Pix
5. Cartao: preenche dados no Stripe Elements -> confirma
   Pix: recebe QR Code -> escaneia e paga -> confirmacao automatica
6. Pedido criado com status 'paid'
7. Restaurante recebe notificacao de novo pedido pago
8. Cliente ve pagina de acompanhamento
```

---

## Ordem de Implementacao

1. Habilitar integracao Stripe no Lovable (coletar chave)
2. Criar schema do banco (colunas + tabela payments)
3. Criar edge function `stripe-onboarding`
4. Adicionar configuracao no Master Admin
5. Adicionar onboarding no Admin do restaurante
6. Criar edge function `stripe-create-payment`
7. Atualizar checkout.php com Stripe Elements + Pix
8. Criar edge function `stripe-webhook`
9. Exibir status de pagamento nos paineis

---

## Consideracoes

- **Seguranca**: Todas as chaves do Stripe ficam nas secrets do Lovable Cloud, nunca expostas no frontend
- **Pix via Stripe**: O Stripe suporta Pix nativamente no Brasil, gerando QR Code automaticamente
- **Taxas reais do Stripe BR**: Cartao ~3,99% + R$0,39 | Pix ~1% (max R$5)
- **O checkout.php ja tem a estrutura visual** preparada para o modo `full` com placeholder para pagamento
- **A taxa do gateway e descontada do restaurante** em ambos os modelos, conforme definido
