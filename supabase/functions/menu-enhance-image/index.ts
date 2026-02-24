import { serve } from "https://deno.land/std@0.168.0/http/server.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers":
    "authorization, x-client-info, apikey, content-type, x-supabase-client-platform, x-supabase-client-platform-version, x-supabase-client-runtime, x-supabase-client-runtime-version",
};

// Guardrail global: trava o modelo no modo "edição", não "criação"
const BASE_GUARDRAIL =
  `This is a real photo of real food. ` +
  `Keep the dish exactly the same: do not change ingredients, shape, size, proportions, or texture. ` +
  `Do not slice, replate, rearrange, stylize, or generate a different dish. ` +
  `Do not add any new food, sauces, toppings, garnish, or props. ` +
  `Only improve lighting, sharpness, color balance, and background style. ` +
  `If the image quality is low, still keep the original food unchanged. `;

const STYLE_PROMPTS: Record<string, string> = {
  minimalist:
    `Minimal clean studio look. Neutral surface (white marble or light grey). ` +
    `Soft natural-looking light. Background softly blurred and uncluttered. ` +
    `Food is the clear focus.`,

  industrial:
    `Industrial urban mood. Darker background with subtle texture (metal/brick feel). ` +
    `Focused light on the food. Background blurred and less bright than the food. ` +
    `Food stands out clearly.`,

  solar:
    `Bright natural daylight. Light wooden surface. ` +
    `Warm soft daylight with gentle shadows. ` +
    `Background softly blurred with natural tones. ` +
    `Food is the clear focus.`,

  traditional:
    `Warm traditional mood. Wooden surface. ` +
    `Warm light, slightly darker background. ` +
    `Background softly blurred, cozy tones. ` +
    `Food stands out clearly.`,

  pop:
    `Clean commercial pop look. Solid simple colorful background. ` +
    `Bright even light on the food. Background smooth and slightly blurred. ` +
    `No illustration style; keep it photographic and realistic. ` +
    `Food is the clear focus.`,

  teste_vitor:
    `Use the restaurant environment from the second image as background context only. ` +
    `Make the food the hero: tight foreground framing and close crop. ` +
    `Background must be distant, soft and heavily blurred. ` +
    `Darken and soften the environment to avoid distraction. ` +
    `Remove extra plates, glasses and cutlery; keep the table clean. ` +
    `Match lighting direction and color temperature so the composite looks real. ` +
    `Create a realistic contact shadow under the dish.`,
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
    const fullPrompt = `${foodContext}${BASE_GUARDRAIL}${stylePrompt}`;

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
