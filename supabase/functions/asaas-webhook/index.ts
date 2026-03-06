import { serve } from "https://deno.land/std@0.190.0/http/server.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers":
    "authorization, x-client-info, apikey, content-type, x-supabase-client-platform, x-supabase-client-platform-version, x-supabase-client-runtime, x-supabase-client-runtime-version",
};

serve(async (req) => {
  if (req.method === "OPTIONS") {
    return new Response(null, { headers: corsHeaders });
  }

  try {
    // Validar token de autenticação do Asaas
    const webhookToken = Deno.env.get("ASAAS_WEBHOOK_TOKEN");
    const authToken = req.headers.get("asaas-access-token");

    if (!webhookToken || authToken !== webhookToken) {
      console.error("Webhook token inválido ou ausente");
      return new Response(
        JSON.stringify({ success: false, error: "Unauthorized" }),
        { status: 401, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    const body = await req.text();
    const event = JSON.parse(body);

    console.log(`Asaas webhook received: ${event.event}`);

    const responseData: Record<string, unknown> = {
      success: true,
      event_type: event.event,
    };

    const payment = event.payment || {};

    switch (event.event) {
      // ============================================
      // Pagamento confirmado (Pix, Boleto, Cartão)
      // ============================================
      case "PAYMENT_CONFIRMED":
      case "PAYMENT_RECEIVED": {
        responseData.payment_id = payment.id;
        responseData.status = "confirmed";
        responseData.value = payment.value;
        responseData.net_value = payment.netValue;
        responseData.billing_type = payment.billingType;
        responseData.confirmed_date = payment.confirmedDate || payment.paymentDate;
        responseData.external_reference = payment.externalReference;
        responseData.customer_id = payment.customer;
        responseData.invoice_url = payment.invoiceUrl;

        // Calcular datas de assinatura
        const startDate = new Date();
        const endDate = new Date();
        endDate.setFullYear(endDate.getFullYear() + 1); // +12 meses

        responseData.subscription_start = startDate.toISOString().split("T")[0];
        responseData.subscription_end = endDate.toISOString().split("T")[0];

        // O external_reference contém o restaurant_id
        responseData.restaurant_id = payment.externalReference;

        console.log(
          `Payment confirmed: ${payment.id} - Restaurant: ${payment.externalReference} - Value: R$${payment.value}`
        );
        break;
      }

      // ============================================
      // Pagamento vencido / não pago
      // ============================================
      case "PAYMENT_OVERDUE": {
        responseData.payment_id = payment.id;
        responseData.status = "overdue";
        responseData.external_reference = payment.externalReference;
        responseData.restaurant_id = payment.externalReference;

        console.log(`Payment overdue: ${payment.id}`);
        break;
      }

      // ============================================
      // Pagamento estornado / cancelado
      // ============================================
      case "PAYMENT_REFUNDED":
      case "PAYMENT_DELETED": {
        responseData.payment_id = payment.id;
        responseData.status = payment.status === "REFUNDED" ? "refunded" : "cancelled";
        responseData.external_reference = payment.externalReference;
        responseData.restaurant_id = payment.externalReference;

        console.log(`Payment ${event.event}: ${payment.id}`);
        break;
      }

      // ============================================
      // Pagamento criado / pendente
      // ============================================
      case "PAYMENT_CREATED":
      case "PAYMENT_UPDATED": {
        responseData.payment_id = payment.id;
        responseData.status = "pending";
        responseData.value = payment.value;
        responseData.billing_type = payment.billingType;
        responseData.external_reference = payment.externalReference;
        responseData.invoice_url = payment.invoiceUrl;
        responseData.bank_slip_url = payment.bankSlipUrl;

        console.log(`Payment ${event.event}: ${payment.id}`);
        break;
      }

      default:
        console.log(`Unhandled Asaas event: ${event.event}`);
        responseData.unhandled = true;
    }

    return new Response(JSON.stringify(responseData), {
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  } catch (error: unknown) {
    console.error("Asaas webhook error:", error);
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
