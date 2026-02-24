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

    const { action, restaurant_id, restaurant_name, restaurant_email, return_url, account_id } = await req.json();

    // Action: create - Cria conta conectada e gera link de onboarding
    if (action === "create") {
      if (!restaurant_id || !return_url) {
        throw new Error("restaurant_id and return_url are required");
      }

      // Criar conta conectada (Express) no Brasil
      const account = await stripe.accounts.create({
        type: "express",
        country: "BR",
        email: restaurant_email || undefined,
        business_type: "company",
        capabilities: {
          card_payments: { requested: true },
          transfers: { requested: true },
        },
        metadata: {
          restaurant_id: String(restaurant_id),
          restaurant_name: restaurant_name || "",
        },
      });

      // Gerar link de onboarding
      const accountLink = await stripe.accountLinks.create({
        account: account.id,
        refresh_url: `${return_url}?stripe_refresh=1&restaurant_id=${restaurant_id}`,
        return_url: `${return_url}?stripe_return=1&restaurant_id=${restaurant_id}&account_id=${account.id}`,
        type: "account_onboarding",
      });

      return new Response(
        JSON.stringify({
          success: true,
          account_id: account.id,
          onboarding_url: accountLink.url,
        }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // Action: status - Verificar status da conta conectada
    if (action === "status") {
      if (!account_id) throw new Error("account_id is required");

      const account = await stripe.accounts.retrieve(account_id);

      let status: string = "pending";
      if (account.charges_enabled && account.payouts_enabled) {
        status = "active";
      } else if (account.requirements?.disabled_reason) {
        status = "restricted";
      }

      return new Response(
        JSON.stringify({
          success: true,
          account_id: account.id,
          status,
          charges_enabled: account.charges_enabled,
          payouts_enabled: account.payouts_enabled,
          details_submitted: account.details_submitted,
          requirements: account.requirements,
        }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // Action: login_link - Gerar link do dashboard Stripe Express
    if (action === "login_link") {
      if (!account_id) throw new Error("account_id is required");

      const loginLink = await stripe.accounts.createLoginLink(account_id);

      return new Response(
        JSON.stringify({ success: true, url: loginLink.url }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // Action: refresh_link - Gerar novo link de onboarding se expirou
    if (action === "refresh_link") {
      if (!account_id || !return_url) {
        throw new Error("account_id and return_url are required");
      }

      const accountLink = await stripe.accountLinks.create({
        account: account_id,
        refresh_url: `${return_url}?stripe_refresh=1&restaurant_id=${restaurant_id}`,
        return_url: `${return_url}?stripe_return=1&restaurant_id=${restaurant_id}&account_id=${account_id}`,
        type: "account_onboarding",
      });

      return new Response(
        JSON.stringify({ success: true, onboarding_url: accountLink.url }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    throw new Error(`Unknown action: ${action}`);
  } catch (error: unknown) {
    console.error("Stripe onboarding error:", error);
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
