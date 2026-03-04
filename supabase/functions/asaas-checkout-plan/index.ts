import { serve } from "https://deno.land/std@0.190.0/http/server.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers":
    "authorization, x-client-info, apikey, content-type, x-supabase-client-platform, x-supabase-client-platform-version, x-supabase-client-runtime, x-supabase-client-runtime-version",
};

const ASAAS_API_URL = "https://api.asaas.com/v3";
// Para sandbox: "https://sandbox.asaas.com/api/v3"

serve(async (req) => {
  if (req.method === "OPTIONS") {
    return new Response(null, { headers: corsHeaders });
  }

  try {
    const apiKey = Deno.env.get("ASAAS_API_KEY");
    if (!apiKey) throw new Error("ASAAS_API_KEY not configured");

    const {
      action,
      // Dados do restaurante para criar cliente
      restaurant_id,
      restaurant_name,
      restaurant_email,
      restaurant_cnpj,
      restaurant_phone,
      responsavel_nome,
      responsavel_cpf,
      // Dados da cobrança
      plan_value,
      plan_name,
      billing_type, // "CREDIT_CARD", "BOLETO", "PIX"
      installment_count, // 1-12 para cartão
      // Dados do cartão (quando billing_type = CREDIT_CARD)
      credit_card,
      credit_card_holder,
      // Cliente já existente
      asaas_customer_id,
    } = await req.json();

    const headers = {
      "Content-Type": "application/json",
      "access_token": apiKey,
    };

    // ============================================
    // ACTION: create_customer — Criar cliente no Asaas
    // ============================================
    if (action === "create_customer") {
      if (!restaurant_name || !restaurant_cnpj) {
        throw new Error("restaurant_name and restaurant_cnpj are required");
      }

      const customerData: Record<string, unknown> = {
        name: restaurant_name,
        cpfCnpj: restaurant_cnpj.replace(/[^\d]/g, ""),
        email: restaurant_email || undefined,
        mobilePhone: restaurant_phone?.replace(/[^\d]/g, "") || undefined,
        externalReference: String(restaurant_id),
      };

      const resp = await fetch(`${ASAAS_API_URL}/customers`, {
        method: "POST",
        headers,
        body: JSON.stringify(customerData),
      });

      const data = await resp.json();
      if (!resp.ok) {
        throw new Error(data.errors?.[0]?.description || `Asaas error: ${resp.status}`);
      }

      return new Response(
        JSON.stringify({
          success: true,
          customer_id: data.id,
          customer_name: data.name,
        }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // ============================================
    // ACTION: create_payment — Criar cobrança
    // ============================================
    if (action === "create_payment") {
      if (!asaas_customer_id || !plan_value || !billing_type) {
        throw new Error("asaas_customer_id, plan_value and billing_type are required");
      }

      const dueDate = new Date();
      dueDate.setDate(dueDate.getDate() + 3); // Vencimento em 3 dias
      const dueDateStr = dueDate.toISOString().split("T")[0];

      const paymentData: Record<string, unknown> = {
        customer: asaas_customer_id,
        billingType: billing_type, // CREDIT_CARD, BOLETO ou PIX
        value: plan_value,
        dueDate: dueDateStr,
        description: plan_name || `Plano Anual - Cardápio Digital`,
        externalReference: String(restaurant_id),
      };

      // Parcelamento para cartão de crédito
      if (billing_type === "CREDIT_CARD" && installment_count && installment_count > 1) {
        paymentData.installmentCount = Math.min(installment_count, 12);
        paymentData.totalValue = plan_value;
        delete paymentData.value;
      }

      const resp = await fetch(`${ASAAS_API_URL}/payments`, {
        method: "POST",
        headers,
        body: JSON.stringify(paymentData),
      });

      const data = await resp.json();
      if (!resp.ok) {
        throw new Error(data.errors?.[0]?.description || `Asaas error: ${resp.status}`);
      }

      const result: Record<string, unknown> = {
        success: true,
        payment_id: data.id,
        status: data.status,
        value: data.value,
        net_value: data.netValue,
        due_date: data.dueDate,
        billing_type: data.billingType,
        invoice_url: data.invoiceUrl,
        bank_slip_url: data.bankSlipUrl,
      };

      // Se for PIX, buscar QR Code
      if (billing_type === "PIX") {
        const pixResp = await fetch(`${ASAAS_API_URL}/payments/${data.id}/pixQrCode`, {
          method: "GET",
          headers,
        });
        if (pixResp.ok) {
          const pixData = await pixResp.json();
          result.pix_qr_code = pixData.encodedImage;
          result.pix_copy_paste = pixData.payload;
          result.pix_expiration = pixData.expirationDate;
        }
      }

      return new Response(
        JSON.stringify(result),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // ============================================
    // ACTION: create_payment_with_card — Cobrança direta com cartão
    // ============================================
    if (action === "create_payment_with_card") {
      if (!asaas_customer_id || !plan_value || !credit_card || !credit_card_holder) {
        throw new Error("asaas_customer_id, plan_value, credit_card and credit_card_holder are required");
      }

      const dueDate = new Date();
      const dueDateStr = dueDate.toISOString().split("T")[0];

      const paymentData: Record<string, unknown> = {
        customer: asaas_customer_id,
        billingType: "CREDIT_CARD",
        dueDate: dueDateStr,
        description: plan_name || `Plano Anual - Cardápio Digital`,
        externalReference: String(restaurant_id),
        creditCard: {
          holderName: credit_card.holder_name,
          number: credit_card.number,
          expiryMonth: credit_card.expiry_month,
          expiryYear: credit_card.expiry_year,
          ccv: credit_card.ccv,
        },
        creditCardHolderInfo: {
          name: credit_card_holder.name,
          email: credit_card_holder.email,
          cpfCnpj: credit_card_holder.cpf_cnpj?.replace(/[^\d]/g, ""),
          postalCode: credit_card_holder.postal_code?.replace(/[^\d-]/g, ""),
          addressNumber: credit_card_holder.address_number,
          phone: credit_card_holder.phone?.replace(/[^\d]/g, ""),
        },
      };

      // Parcelamento
      if (installment_count && installment_count > 1) {
        paymentData.installmentCount = Math.min(installment_count, 12);
        paymentData.totalValue = plan_value;
      } else {
        paymentData.value = plan_value;
      }

      const resp = await fetch(`${ASAAS_API_URL}/payments`, {
        method: "POST",
        headers,
        body: JSON.stringify(paymentData),
      });

      const data = await resp.json();
      if (!resp.ok) {
        throw new Error(data.errors?.[0]?.description || `Asaas error: ${resp.status}`);
      }

      return new Response(
        JSON.stringify({
          success: true,
          payment_id: data.id,
          status: data.status,
          value: data.value,
          net_value: data.netValue,
          invoice_url: data.invoiceUrl,
        }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // ============================================
    // ACTION: get_payment_status — Consultar status
    // ============================================
    if (action === "get_payment_status") {
      const { payment_id } = await req.json().catch(() => ({}));
      if (!payment_id) throw new Error("payment_id is required");

      // We already parsed the body above, get payment_id from the original parse
    }

    // ============================================
    // ACTION: get_payment_link — Gerar link de pagamento
    // ============================================
    if (action === "get_payment_link") {
      if (!plan_value || !plan_name) {
        throw new Error("plan_value and plan_name are required");
      }

      const linkData: Record<string, unknown> = {
        name: plan_name,
        description: `Plano Anual - Cardápio Digital`,
        endDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
        value: plan_value,
        billingType: "UNDEFINED", // Permite cliente escolher
        chargeType: "DETACHED",
        dueDateLimitDays: 7,
        maxInstallmentCount: 12,
      };

      const resp = await fetch(`${ASAAS_API_URL}/paymentLinks`, {
        method: "POST",
        headers,
        body: JSON.stringify(linkData),
      });

      const data = await resp.json();
      if (!resp.ok) {
        throw new Error(data.errors?.[0]?.description || `Asaas error: ${resp.status}`);
      }

      return new Response(
        JSON.stringify({
          success: true,
          payment_link_id: data.id,
          payment_link_url: data.url,
        }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    throw new Error(`Unknown action: ${action}`);
  } catch (error: unknown) {
    console.error("Asaas checkout error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return new Response(
      JSON.stringify({ success: false, error: message }),
      {
        status: 400,
        headers: { ...corsHeaders, "Content-Type": "application/json" },
      }
    );
  }
});
