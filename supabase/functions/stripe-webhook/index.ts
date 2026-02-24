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

    const stripeWebhookSecret = Deno.env.get("STRIPE_WEBHOOK_SECRET");

    const stripe = new Stripe(stripeKey, { apiVersion: "2025-08-27.basil" });

    const body = await req.text();
    let event: Stripe.Event;

    // Verificar assinatura do webhook se secret configurado
    if (stripeWebhookSecret) {
      const signature = req.headers.get("stripe-signature");
      if (!signature) throw new Error("Missing stripe-signature header");

      event = stripe.webhooks.constructEvent(body, signature, stripeWebhookSecret);
    } else {
      // Em desenvolvimento, aceitar sem verificação
      event = JSON.parse(body) as Stripe.Event;
    }

    console.log(`Stripe webhook received: ${event.type}`);

    // Resposta padrão com dados do evento para o PHP processar
    const responseData: Record<string, unknown> = {
      success: true,
      event_type: event.type,
      event_id: event.id,
    };

    switch (event.type) {
      case "payment_intent.succeeded": {
        const paymentIntent = event.data.object as Stripe.PaymentIntent;
        responseData.payment_intent_id = paymentIntent.id;
        responseData.amount = paymentIntent.amount;
        responseData.status = "succeeded";
        responseData.metadata = paymentIntent.metadata;
        responseData.payment_method_type = paymentIntent.payment_method_types?.[0] || "card";

        // Calcular taxas (estimativas — Stripe BR)
        const amountInReais = paymentIntent.amount / 100;
        const isCard = paymentIntent.payment_method_types?.includes("card");
        let gatewayFee = 0;
        if (isCard) {
          gatewayFee = Math.round((amountInReais * 0.0399 + 0.39) * 100) / 100;
        } else {
          // Pix: ~1% com máximo de R$5
          gatewayFee = Math.min(Math.round(amountInReais * 0.01 * 100) / 100, 5);
        }

        responseData.gateway_fee = gatewayFee;
        responseData.order_id = paymentIntent.metadata?.order_id;
        responseData.restaurant_id = paymentIntent.metadata?.restaurant_id;

        console.log(`Payment succeeded: ${paymentIntent.id} - Order: ${paymentIntent.metadata?.order_id}`);
        break;
      }

      case "payment_intent.payment_failed": {
        const failedIntent = event.data.object as Stripe.PaymentIntent;
        responseData.payment_intent_id = failedIntent.id;
        responseData.status = "failed";
        responseData.metadata = failedIntent.metadata;
        responseData.order_id = failedIntent.metadata?.order_id;
        responseData.error_message =
          failedIntent.last_payment_error?.message || "Payment failed";

        console.log(`Payment failed: ${failedIntent.id} - ${responseData.error_message}`);
        break;
      }

      case "account.updated": {
        const account = event.data.object as Stripe.Account;
        let accountStatus = "pending";
        if (account.charges_enabled && account.payouts_enabled) {
          accountStatus = "active";
        } else if (account.requirements?.disabled_reason) {
          accountStatus = "restricted";
        }

        responseData.account_id = account.id;
        responseData.account_status = accountStatus;
        responseData.charges_enabled = account.charges_enabled;
        responseData.payouts_enabled = account.payouts_enabled;
        responseData.restaurant_id = account.metadata?.restaurant_id;

        console.log(`Account updated: ${account.id} - Status: ${accountStatus}`);
        break;
      }

      default:
        console.log(`Unhandled event type: ${event.type}`);
    }

    return new Response(JSON.stringify(responseData), {
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  } catch (error: unknown) {
    console.error("Stripe webhook error:", error);
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
