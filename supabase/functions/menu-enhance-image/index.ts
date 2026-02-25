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
  `Do NOT add ANY new objects: no plates, no bowls, no cutlery, no napkins, no props whatsoever. If the food has no plate in the original, do not add one. ` +
  `CRITICAL: Keep the EXACT same camera angle, perspective, and viewpoint as the original photo. Do not rotate, tilt, or reframe. ` +
  `No visible light fixtures (no lamps, no spotlights, no hanging lights in frame). ` +
  `Only improve lighting, exposure, white balance, sharpness and background style while preserving the food exactly as it appears. ` +
  `Make the food the hero: tight crop and shallow depth of field. ` +
  `The result MUST look like a professional commercial food photograph that triggers instant craving and desire to eat. ` +
  `Enhance textures to look irresistible: glistening sauces, crispy edges, juicy freshness, steam if appropriate. ` +
  `Use appetizing warm tones and perfect highlights to make the dish look premium and mouth-watering. `;

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

  customizavel: "DYNAMIC", // built dynamically from parameters
};

const STYLE_NAMES: Record<string, string> = {
  minimalist: "Minimalista & Moderno",
  industrial: "Industrial & Urbano",
  solar: "Solar & Orgânico",
  traditional: "Tradicional & Aconchegante",
  pop: "Pop & Colorido",
  customizavel: "Customizável (Prato + Ambiente)",
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

    const { image, image_environment, style, food_name, bg_color, framing, angle, lighting, background_effect } = await req.json();

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

    // Validate customizavel requires second image
    if (style === "customizavel" && !image_environment) {
      return new Response(
        JSON.stringify({ error: "O estilo Customizável requer uma segunda imagem do ambiente (image_environment)" }),
        {
          status: 400,
          headers: { ...corsHeaders, "Content-Type": "application/json" },
        },
      );
    }

    // Build style prompt
    let stylePrompt: string;

    if (style === "customizavel") {
      // Build dynamic prompt from parameters
      const framingMap: Record<string, string> = {
        "70": "Food occupies 70% of the frame, showing some of the environment context around it.",
        "90": "Food occupies 90% of the frame (tight close-up), minimal environment visible.",
        "200": "Extreme macro close-up, food fills the entire frame showing textures and details at 200% zoom.",
      };
      const angleMap: Record<string, string> = {
        "45": "Camera angle at 45 degrees (three-quarter view), showing both the top and side of the dish.",
        "top": "Camera directly overhead (top-down/flatlay view), looking straight down at the dish.",
      };
      const lightingMap: Record<string, string> = {
        "ambient": "Use the natural ambient lighting from the environment photo. Match the existing light direction and color temperature.",
        "professional": "Apply professional studio-quality lighting: soft diffused key light from the side, subtle fill light, and gentle rim light for depth.",
      };
      const bgEffectMap: Record<string, string> = {
        "darkened": "Background slightly darkened to make the food stand out as the hero.",
        "blurred": "Background heavily blurred (strong bokeh effect) to isolate the food.",
        "blurred_darkened": "Background heavily blurred AND darkened for maximum food focus and dramatic mood.",
      };

      const framingPrompt = framingMap[framing || "90"] || framingMap["90"];
      const anglePrompt = angleMap[angle || "45"] || angleMap["45"];
      const lightingPrompt = lightingMap[lighting || "professional"] || lightingMap["professional"];
      const bgPrompt = bgEffectMap[background_effect || "blurred_darkened"] || bgEffectMap["blurred_darkened"];

      stylePrompt =
        `Imagine you are a top commercial food photographer hired to photograph this dish inside the restaurant/space shown in the second image. ` +
        `Study the dish in the first image: memorize every ingredient, topping, sauce, texture, and detail. ` +
        `Study the environment in the second image: memorize the surfaces, materials, colors, ambient light, and atmosphere. ` +
        `Now produce ONE single photograph as if the dish is physically sitting on a table/counter inside that environment. ` +
        `The dish must be rendered with photorealistic detail: natural textures, weight, shadows, reflections on the surface, ambient light bouncing off the food. ` +
        `This is NOT a composite or collage — it must look like ONE real photo taken with a professional camera in that location. ` +
        `${framingPrompt} ` +
        `${anglePrompt} ` +
        `${lightingPrompt} ` +
        `${bgPrompt} ` +
        `No visible light fixtures in frame. ` +
        `The result must trigger instant hunger — make it look irresistible, premium, and worthy of a food magazine cover.`;
    } else {
      stylePrompt = STYLE_PROMPTS[style];
      // For pop style, allow custom background color
      if (style === "pop" && bg_color) {
        stylePrompt = stylePrompt.replace("Solid clean background color", `Solid ${bg_color} background color`);
      }
    }

    // Add food name context if provided (lightweight, doesn't trigger redesign)
    const foodContext = food_name ? `This is "${food_name}". ` : "";

    // ✅ IMPORTANT: remove any instruction that allows "plating/presentation" changes
    // For customizavel, use only the style prompt (it has its own guardrails). For others, prepend BASE_GUARDRAIL.
    const fullPrompt = style === "customizavel"
      ? `${foodContext}${stylePrompt} Camera: 50mm look, f/1.4–f/2 shallow depth of field, subject razor sharp, background creamy blur.`
      : `${foodContext}${BASE_GUARDRAIL}${stylePrompt} Camera: 50mm look, f/1.4–f/2 shallow depth of field, subject razor sharp, background creamy blur.`;

    const imageUrl = image.startsWith("data:") ? image : `data:image/jpeg;base64,${image}`;

    // Build content array with images
    const contentParts: any[] = [
      { type: "text", text: fullPrompt },
      { type: "image_url", image_url: { url: imageUrl } },
    ];

    // Add environment image for customizavel style
    if (style === "customizavel" && image_environment) {
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
