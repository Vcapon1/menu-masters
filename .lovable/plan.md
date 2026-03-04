

# Arquitetura de Pagamentos: Asaas para Planos + Stripe para Pedidos

## Análise da Sugestão

Faz muito sentido separar os gateways por finalidade:

| Finalidade | Gateway | Motivo |
|---|---|---|
| **Cobrança de planos** (assinatura anual dos restaurantes) | **Asaas** | Melhor para boleto, Pix e parcelamento no Brasil. API simples para cobranças recorrentes. Taxas mais competitivas para B2B. |
| **Pagamento de pedidos** (clientes finais comprando comida) | **Stripe Connect** | Já implementado. Split automático entre plataforma e restaurante. Stripe Elements no checkout. |

## O que muda no plano de onboarding

### Edge Function: `asaas-checkout-plan` (nova)

Substitui a `stripe-checkout-plan` que estava planejada. Responsabilidades:

- Criar cliente no Asaas (CNPJ do restaurante)
- Gerar cobrança (boleto, Pix ou cartão parcelado)
- Retornar link de pagamento ou QR Code Pix
- Suportar parcelamento nativo (até 12x)

### Edge Function: `asaas-webhook` (nova)

- Receber confirmação de pagamento (`PAYMENT_CONFIRMED`, `PAYMENT_RECEIVED`)
- Atualizar status do restaurante para `ativo`
- Definir `subscription_start` e `subscription_end`

### O que permanece igual

- Stripe Connect para pedidos dos clientes (já funciona)
- `stripe-onboarding`, `stripe-create-payment`, `stripe-webhook` — intocados
- Toda a lógica de onboarding do restaurante (cadastro, formulário, termos)
- Schema MySQL planejado

## Configuração necessária

1. **Chave API do Asaas** — precisa ser adicionada como secret (`ASAAS_API_KEY`)
2. **Webhook do Asaas** — apontar para a Edge Function após deploy
3. **Ambiente** — Asaas tem sandbox para testes (`sandbox.asaas.com`) e produção (`api.asaas.com`)

## Vantagens do Asaas para cobrança de planos no Brasil

- Boleto bancário nativo (muitos restaurantes preferem)
- Pix com QR Code automático
- Parcelamento no cartão sem complicação
- Cobrança recorrente automática (para renovação)
- Nota fiscal integrada (opcional)
- Dashboard em português para acompanhar cobranças

## Fluxo atualizado

```text
Master cria restaurante
       ↓
Restaurante acessa link de cadastro
       ↓
Preenche dados + aceita termos
       ↓
Redireciona para pagamento via ASAAS
  (Pix / Boleto / Cartão parcelado)
       ↓
Webhook Asaas confirma pagamento
       ↓
status = ativo (início +12 meses)
       ↓
Formulário de onboarding
       ↓
Cliente faz pedido no cardápio
       ↓
Pagamento do pedido via STRIPE Connect
  (Pix / Cartão com split automático)
```

## Ordem de implementação

1. Configurar secret `ASAAS_API_KEY`
2. Criar Edge Function `asaas-checkout-plan`
3. Criar Edge Function `asaas-webhook`
4. Atualizar schema MySQL + arquivos PHP de onboarding
5. Integrar no fluxo de cadastro do restaurante

Deseja aprovar este plano para iniciar a implementação?

