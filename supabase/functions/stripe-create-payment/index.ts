import { serve } from "https://deno.land/std@0.190.0/http/server.ts";
import Stripe from "https://esm.sh/stripe@18.5.0";

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
    const stripeKey = Deno.env.get("STRIPE_SECRET_KEY");
    if (!stripeKey) throw new Error("STRIPE_SECRET_KEY not configured");

    const stripe = new Stripe(stripeKey, { apiVersion: "2025-08-27.basil" });

    const {
      order_id,
      restaurant_id,
      amount, // em reais (ex: 59.90)
      payment_method_type, // 'card' ou 'pix'
      stripe_account_id, // conta conectada do restaurante
      payment_model, // 'commission' ou 'full'
      platform_fee_percent, // ex: 6.00
      customer_name,
      customer_email,
    } = await req.json();

    if (!order_id || !amount || !stripe_account_id) {
      throw new Error("order_id, amount and stripe_account_id are required");
    }

    // Converter para centavos (Stripe usa menor unidade)
    const amountInCents = Math.round(amount * 100);

    // Calcular taxa da plataforma
    let applicationFeeAmount = 0;
    if (payment_model === "commission" && platform_fee_percent > 0) {
      applicationFeeAmount = Math.round(amountInCents * (platform_fee_percent / 100));
    }

    // Definir métodos de pagamento aceitos
    const paymentMethodTypes: string[] = [];
    if (payment_method_type === "pix") {
      paymentMethodTypes.push("pix");
    } else if (payment_method_type === "card") {
      paymentMethodTypes.push("card");
    } else {
      // Ambos
      paymentMethodTypes.push("card", "pix");
    }

    // Criar PaymentIntent com split para conta conectada
    const paymentIntentData: Stripe.PaymentIntentCreateParams = {
      amount: amountInCents,
      currency: "brl",
      payment_method_types: paymentMethodTypes,
      metadata: {
        order_id: String(order_id),
        restaurant_id: String(restaurant_id),
        payment_model: payment_model || "commission",
      },
      // Transferir para conta conectada
      transfer_data: {
        destination: stripe_account_id,
      },
    };

    // Aplicar taxa da plataforma apenas no modelo commission
    if (applicationFeeAmount > 0) {
      paymentIntentData.application_fee_amount = applicationFeeAmount;
    }

    // Adicionar descrição
    paymentIntentData.description = `Pedido #${order_id} - Restaurante ${restaurant_id}`;

    const paymentIntent = await stripe.paymentIntents.create(paymentIntentData);

    return new Response(
      JSON.stringify({
        success: true,
        client_secret: paymentIntent.client_secret,
        payment_intent_id: paymentIntent.id,
        amount: amountInCents,
        application_fee: applicationFeeAmount,
      }),
      { headers: { ...corsHeaders, "Content-Type": "application/json" } }
    );
  } catch (error: unknown) {
    console.error("Stripe create payment error:", error);
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
