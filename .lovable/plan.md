

# Arquitetura de Pagamentos: Asaas para Planos + Stripe para Pedidos

## Status: ✅ Implementado

## Componentes Criados

### Edge Functions
1. **`asaas-checkout-plan`** — Criar cliente Asaas, gerar cobranças (Pix/Boleto/Cartão até 12x)
2. **`asaas-webhook`** — Receber confirmação de pagamento e atualizar status do restaurante

### Páginas PHP
1. **`docs/php/cadastro.php`** — Formulário de cadastro do restaurante via token exclusivo
2. **`docs/php/onboarding.php`** — Formulário pós-pagamento (upload cardápio, logo, fotos, horários)
3. **`docs/php/parceiro.php`** — Formulário público "Quero ser Parceiro" (cria lead)
4. **`docs/php/pagamento-plano.php`** — Checkout com Pix/Boleto/Cartão via Asaas

### Schema MySQL Atualizado
- `restaurants`: novos campos para onboarding (token, CNPJ, CPF, status expandido, datas assinatura, Asaas IDs)
- `commissions`: tabela de comissões por pedido
- `plan_payments`: tabela de pagamentos de planos via Asaas

### Master Admin Atualizado
- Botão "Criar Novo Restaurante (Onboarding)" com geração de link exclusivo
- Botão "Aprovar" para leads vindos do formulário parceiro
- Tabela expandida com coluna de status onboarding
- Status expandidos: lead, aguardando_cadastro, aguardando_pagamento, active, vencido, suspenso

## Fluxo Completo
```
Master Admin → Cria restaurante → Gera link (7 dias)
       ↓
Restaurante acessa link → Preenche dados + aceita termos
       ↓
Redireciona para pagamento via ASAAS (Pix/Boleto/Cartão 12x)
       ↓
Webhook Asaas confirma → status = ativo (+12 meses)
       ↓
Formulário onboarding (cardápio, logo, fotos, horários)
       ↓
Pedidos dos clientes → STRIPE Connect (split automático)
```

## Secrets Configurados
- `ASAAS_API_KEY` ✅
- `STRIPE_SECRET_KEY` ✅ (pedidos)
- `STRIPE_WEBHOOK_SECRET` ✅ (pedidos)

## Pendências
- Configurar webhook no painel Asaas apontando para: `https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1/asaas-webhook`
- Adicionar rotas no `index.php`: `/cadastro/{token}`, `/pagamento-plano/{id}`, `/onboarding`, `/parceiro`
- Implementar regra automática de vencimento (cron/scheduled)
