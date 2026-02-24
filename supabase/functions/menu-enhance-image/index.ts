import { serve } from "https://deno.land/std@0.168.0/http/server.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers":
    "authorization, x-client-info, apikey, content-type, x-supabase-client-platform, x-supabase-client-platform-version, x-supabase-client-runtime, x-supabase-client-runtime-version",
};

// Guardrail global: trava o modelo no modo "edição", não "criação"
const BASE_GUARDRAIL =
  `Edit the provided photo only. Keep the exact same dish and composition. ` +
  `Do not change ingredients, toppings, sauces, garnish, shape, size, proportions, texture, doneness, or arrangement. ` +
  `Do not slice, add/remove pieces, replate, or restyle the food. ` +
  `Do not add props or any new objects. ` +
  `No visible light fixtures (no lamps, no spotlights, no hanging lights in frame). ` +
  `Only improve lighting, exposure, white balance, sharpness and background style while preserving the food exactly. ` +
  `Make the food the hero: tight crop and shallow depth of field. `;

const STYLE_PROMPTS: Record<string, string> = {
  minimalist:
    `Minimal clean studio look. Food occupies 90–95% of the frame (tight close-up). ` +
    `Neutral surface (white marble or light grey). Soft diffused side light (no hard spots). ` +
    `Background plain and heavily blurred. No extra objects. No visible light fixtures.`,

  industrial:
    `Industrial urban mood. Food occupies 90–95% of the frame (tight close-up). ` +
    `Dark matte surface (stone/metal/wood). Controlled soft side light with gentle shadow. ` +
    `Background dark, heavily blurred, subtle texture only. No props. No visible light fixtures.`,

  solar:
    `Bright natural daylight look. Food occupies 90–95% of the frame (tight close-up). ` +
    `Light wooden or neutral surface. Soft daylight from the side (not overhead), natural shadows. ` +
    `Background bright but heavily blurred. No props. No visible light fixtures.`,

  traditional:
    `Warm cozy look. Food occupies 90–95% of the frame (tight close-up). ` +
    `Wooden surface. Warm soft side light, richer shadows, realistic highlights. ` +
    `Background darker and heavily blurred. No props. No visible light fixtures.`,

  pop:
    `Commercial pop look (still photographic). Food occupies 90–95% of the frame (tight close-up). ` +
    `Solid clean background color, smooth and slightly blurred. Even soft light, true-to-life textures. ` +
    `No illustration/cartoon. No props. No visible light fixtures.`,

  teste_vitor:
    `Use the second image only as a blurred background context. ` +
    `Food occupies 85–90% of the frame in the foreground (tight crop). ` +
    `Remove all table items/props; keep a clean surface only. ` +
    `Match perspective and lighting; add realistic contact shadow under the dish. ` +
    `Background distant, heavily blurred and slightly darkened. No visible light fixtures.`,
};

const STYLE_NAMES: Record<string, string> = {
  minimalist: "Minimalista & Moderno",
  industrial: "Industrial & Urbano",
  solar: "Solar & Orgânico",
  traditional: "Tradicional & Aconchegante",
  pop: "Pop & Colorido",
  teste_vitor: "Teste Vitor (Prato + Ambiente)",
};

serve(async (req) => {
  if (req.method === "OPTIONS") {
    return new Response(null, { headers: corsHeaders });
  }

  try {
    const LOVABLE_API_KEY = Deno.env.get("LOVABLE_API_KEY");
    if (!LOVABLE_API_KEY) {
      throw new Error("LOVABLE_API_KEY is not configured");
    }

    const { image, image_environment, style, food_name, bg_color } = await req.json();

    if (!image) {
      return new Response(JSON.stringify({ error: "Envie uma imagem em base64" }), {
        status: 400,
        headers: { ...corsHeaders, "Content-Type": "application/json" },
      });
    }

    if (!style || !STYLE_PROMPTS[style]) {
      return new Response(JSON.stringify({ error: `Estilo inválido. Use: ${Object.keys(STYLE_PROMPTS).join(", ")}` }), {
        status: 400,
        headers: { ...corsHeaders, "Content-Type": "application/json" },
      });
    }

    // Validate teste_vitor requires second image
    if (style === "teste_vitor" && !image_environment) {
      return new Response(
        JSON.stringify({ error: "O estilo Teste Vitor requer uma segunda imagem do ambiente (image_environment)" }),
        {
          status: 400,
          headers: { ...corsHeaders, "Content-Type": "application/json" },
        },
      );
    }

    // Build style prompt
    let stylePrompt = STYLE_PROMPTS[style];

    // For pop style, allow custom background color (safe substitution)
    if (style === "pop" && bg_color) {
      stylePrompt = stylePrompt.replace("Solid simple colorful background.", `Solid simple ${bg_color} background.`);
    }

    // Add food name context if provided (lightweight, doesn't trigger redesign)
    const foodContext = food_name ? `This is "${food_name}". ` : "";

    // ✅ IMPORTANT: remove any instruction that allows "plating/presentation" changes
    const fullPrompt = `${foodContext}${BASE_GUARDRAIL}${stylePrompt} Camera: 50mm look, f/1.4–f/2 shallow depth of field, subject razor sharp, background creamy blur.`;

    const imageUrl = image.startsWith("data:") ? image : `data:image/jpeg;base64,${image}`;

    // Build content array with images
    const contentParts: any[] = [
      { type: "text", text: fullPrompt },
      { type: "image_url", image_url: { url: imageUrl } },
    ];

    // Add environment image for teste_vitor style
    if (style === "teste_vitor" && image_environment) {
      const envImageUrl = image_environment.startsWith("data:")
        ? image_environment
        : `data:image/jpeg;base64,${image_environment}`;
      contentParts.push({ type: "image_url", image_url: { url: envImageUrl } });
    }

    const response = await fetch("https://ai.gateway.lovable.dev/v1/chat/completions", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${LOVABLE_API_KEY}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        model: "google/gemini-2.5-flash-image",
        messages: [
          {
            role: "user",
            content: contentParts,
          },
        ],
        modalities: ["image", "text"],
      }),
    });

    if (!response.ok) {
      if (response.status === 429) {
        return new Response(
          JSON.stringify({ error: "Limite de requisições excedido. Tente novamente em alguns minutos." }),
          { status: 429, headers: { ...corsHeaders, "Content-Type": "application/json" } },
        );
      }
      if (response.status === 402) {
        return new Response(JSON.stringify({ error: "Créditos de IA esgotados. Adicione créditos na sua conta." }), {
          status: 402,
          headers: { ...corsHeaders, "Content-Type": "application/json" },
        });
      }
      const errText = await response.text();
      console.error("AI gateway error:", response.status, errText);
      return new Response(JSON.stringify({ error: "Erro ao processar imagem com IA" }), {
        status: 500,
        headers: { ...corsHeaders, "Content-Type": "application/json" },
      });
    }

    const data = await response.json();
    const resultImage = data.choices?.[0]?.message?.images?.[0]?.image_url?.url;

    if (!resultImage) {
      console.error("No image in response:", JSON.stringify(data).substring(0, 500));
      return new Response(JSON.stringify({ error: "IA não retornou imagem. Tente novamente." }), {
        status: 500,
        headers: { ...corsHeaders, "Content-Type": "application/json" },
      });
    }

    return new Response(
      JSON.stringify({
        enhanced_image: resultImage,
        style_name: STYLE_NAMES[style],
      }),
      { headers: { ...corsHeaders, "Content-Type": "application/json" } },
    );
  } catch (e) {
    console.error("menu-enhance-image error:", e);
    return new Response(JSON.stringify({ error: e instanceof Error ? e.message : "Erro desconhecido" }), {
      status: 500,
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  }
});
