import { serve } from "https://deno.land/std@0.168.0/http/server.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers":
    "authorization, x-client-info, apikey, content-type, x-supabase-client-platform, x-supabase-client-platform-version, x-supabase-client-runtime, x-supabase-client-runtime-version",
};

const STYLE_PROMPTS: Record<string, string> = {
  minimalist:
    "Professional food photography, extreme close-up, minimalist fine dining style. The food item centered on a clean white porcelain plate or gray slate, placed on a white marble surface. Soft, directional studio lighting emphasizing food textures. Very shallow depth of field with a minimalist, blurred light grey background. High-end aesthetic, sharp focus on food details, 8k resolution, realistic.",
  industrial:
    "Professional food photography, close-up shot, industrial street food style. The food item prominently displayed on a dark metal tray or slate surface. Dramatic, high-contrast spotlighting on the food. Very shallow depth of field with a dark, blurred brick wall background. Vibrant colors, gritty but appetizing textures, 8k, highly detailed, food fills most of the frame.",
  solar:
    "Professional food photography, tight shot, bright organic lifestyle style. The food item on a light oak wood table. Bright natural sunlight directly illuminating the food, with soft, long shadows. Extremely blurred green foliage background (bokeh), creating a serene, natural halo around the product. Fresh, airy, and inviting atmosphere, 8k, realistic.",
  traditional:
    "Professional food photography, dominant close-up, rustic traditional style. The food item on a dark reclaimed wood table. Warm, inviting light focused on the product, highlighting its textures. Very blurred, dark background with subtle, warm amber light (like a distant oven glow). Slight dust of flour on the surface, rich, authentic textures, home-cooked feel, 8k, realistic.",
  pop:
    "Professional food photography, bold close-up, vibrant pop art commercial style. The food item against a solid, evenly lit, highly saturated pastel background that almost fills the frame. Bright, flat, commercial lighting, making the food pop. High color saturation, playful and fun aesthetic. Sharp focus, clean edges, advertising quality, 8k, product occupies at least 70% of the image.",
};

const STYLE_NAMES: Record<string, string> = {
  minimalist: "Minimalista & Moderno",
  industrial: "Industrial & Urbano",
  solar: "Solar & Orgânico",
  traditional: "Tradicional & Aconchegante",
  pop: "Pop & Colorido",
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

    const { image, style, food_name, bg_color } = await req.json();

    if (!image) {
      return new Response(
        JSON.stringify({ error: "Envie uma imagem em base64" }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    if (!style || !STYLE_PROMPTS[style]) {
      return new Response(
        JSON.stringify({ error: `Estilo inválido. Use: ${Object.keys(STYLE_PROMPTS).join(", ")}` }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
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

    const response = await fetch(
      "https://ai.gateway.lovable.dev/v1/chat/completions",
      {
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
              content: [
                { type: "text", text: fullPrompt },
                { type: "image_url", image_url: { url: imageUrl } },
              ],
            },
          ],
          modalities: ["image", "text"],
        }),
      }
    );

    if (!response.ok) {
      if (response.status === 429) {
        return new Response(
          JSON.stringify({ error: "Limite de requisições excedido. Tente novamente em alguns minutos." }),
          { status: 429, headers: { ...corsHeaders, "Content-Type": "application/json" } }
        );
      }
      if (response.status === 402) {
        return new Response(
          JSON.stringify({ error: "Créditos de IA esgotados. Adicione créditos na sua conta." }),
          { status: 402, headers: { ...corsHeaders, "Content-Type": "application/json" } }
        );
      }
      const errText = await response.text();
      console.error("AI gateway error:", response.status, errText);
      return new Response(
        JSON.stringify({ error: "Erro ao processar imagem com IA" }),
        { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    const data = await response.json();
    const resultImage = data.choices?.[0]?.message?.images?.[0]?.image_url?.url;

    if (!resultImage) {
      console.error("No image in response:", JSON.stringify(data).substring(0, 500));
      return new Response(
        JSON.stringify({ error: "IA não retornou imagem. Tente novamente." }),
        { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    return new Response(
      JSON.stringify({
        enhanced_image: resultImage,
        style_name: STYLE_NAMES[style],
      }),
      { headers: { ...corsHeaders, "Content-Type": "application/json" } }
    );
  } catch (e) {
    console.error("menu-enhance-image error:", e);
    return new Response(
      JSON.stringify({ error: e instanceof Error ? e.message : "Erro desconhecido" }),
      { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
    );
  }
});
