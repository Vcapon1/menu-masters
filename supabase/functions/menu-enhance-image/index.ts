import { serve } from "https://deno.land/std@0.168.0/http/server.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers":
    "authorization, x-client-info, apikey, content-type, x-supabase-client-platform, x-supabase-client-platform-version, x-supabase-client-runtime, x-supabase-client-runtime-version",
};

const STYLE_PROMPTS: Record<string, string> = {
  minimalist:
    "Professional commercial food photography, extreme close-up, luxury minimalist fine dining style. The food item fills 80% of the frame, centered and dominant on a clean white porcelain plate or gray slate over white marble. Strong directional side lighting creating defined shadows and depth, emphasizing real textures, crispy edges, moisture highlights and natural imperfections. Background ultra blurred light grey, no visual distractions. High contrast between subject and background. Ultra sharp focus on food surface details, shallow depth of field, realistic texture, appetizing, 8k resolution.",
  industrial:
    "Melhore esta foto transformando-a numa foto profissional, com luzes e angulo melhor, se necessário melhor um pouco a montagem do produto, o ambiente deixe algo industrial e urbano, altere apenas o ambiente.",
  solar:
    "Professional commercial food photography, tight dominant close-up, bright organic lifestyle style. The food fills 75–80% of the frame on a light oak table. Strong natural side sunlight creating depth, soft shadow gradients and glowing highlights on moisture and fresh textures. Background extremely blurred green foliage bokeh, strong subject separation. Enhanced color vibrancy only on the food, realistic texture, airy but high contrast between subject and background. Ultra detailed surface, shallow depth of field, 8k.",
  traditional:
    "Professional commercial food photography, tight dominant close-up, rustic traditional style. The food fills at least 80% of the frame on dark reclaimed wood. Warm directional key light emphasizing texture depth, crispy edges and golden tones. Subtle steam rising if applicable. Background very dark and heavily blurred with warm amber glow, strong contrast between subject and surroundings. Rich shadows, authentic imperfections, tactile realism, ultra sharp surface detail, shallow depth of field, 8k.",
  pop: "Professional commercial food photography, bold extreme close-up, vibrant pop commercial style. The food occupies at least 90% of the frame, centered and dominant. Solid highly saturated pastel background, evenly lit but clearly separated from subject. Bright directional lighting creating shine on sauces, crisp texture on edges, visible depth and volume. High color contrast focused on food, background slightly less saturated to avoid overpowering subject. Ultra sharp edges, advertising quality realism, shallow depth of field, 8k.",
  teste_vitor: `
Enhance this photo into a professional restaurant food image.

Keep the exact same pizza and ingredients. Do not redesign or replace the food.

The pizza must be the only subject on the table.
Remove all extra plates, glasses and cutlery.

Frame the pizza as the hero shot.
It must occupy most of the foreground.

Background should be distant, soft and heavily blurred.
Darken and soften the environment to avoid distraction.

Improve lighting and color balance.
Create realistic shadow under the pizza.
Maintain original food structure.

Use tight foreground framing.
Crop close to the pizza.
Background must be secondary and out of focus.
`,
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

    // Build prompt
    let prompt = STYLE_PROMPTS[style];

    // For pop style, allow custom background color
    if (style === "pop" && bg_color) {
      prompt = prompt.replace("highly saturated pastel background", `highly saturated ${bg_color} background`);
    }

    // Add food name context if provided
    const foodContext = food_name ? `This is a photo of "${food_name}". ` : "";
    const fullPrompt = `${foodContext}Transform this food photo into the following style, keeping the same food item but changing the presentation, plating, and environment: ${prompt}`;

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
