import { serve } from "https://deno.land/std@0.168.0/http/server.ts";

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
    const LOVABLE_API_KEY = Deno.env.get("LOVABLE_API_KEY");
    if (!LOVABLE_API_KEY) {
      throw new Error("LOVABLE_API_KEY is not configured");
    }

    const { images } = await req.json();

    if (!images || !Array.isArray(images) || images.length === 0) {
      return new Response(
        JSON.stringify({ error: "Envie pelo menos uma imagem em base64" }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    if (images.length > 5) {
      return new Response(
        JSON.stringify({ error: "Máximo de 5 imagens por importação" }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // Build message content with images
    const contentParts: any[] = [
      {
        type: "text",
        text: `Analise estas fotos de um cardápio de restaurante brasileiro.
Extraia TODAS as categorias e produtos visíveis.

Regras:
- Preços em formato numérico (sem R$)
- Se houver tamanhos (P, M, G), inclua no campo sizes_prices como objeto {"P": 10.00, "M": 15.00, "G": 20.00}
- Se não conseguir ler um preço, coloque 0
- Mantenha acentuação correta em português
- Descrição vazia se não visível
- Não invente dados que não estão na imagem`,
      },
    ];

    for (const img of images) {
      // Support both raw base64 and data URI
      const base64Data = img.startsWith("data:") ? img : `data:image/jpeg;base64,${img}`;
      contentParts.push({
        type: "image_url",
        image_url: { url: base64Data },
      });
    }

    const response = await fetch(
      "https://ai.gateway.lovable.dev/v1/chat/completions",
      {
        method: "POST",
        headers: {
          Authorization: `Bearer ${LOVABLE_API_KEY}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          model: "google/gemini-2.5-flash",
          messages: [
            {
              role: "user",
              content: contentParts,
            },
          ],
          tools: [
            {
              type: "function",
              function: {
                name: "extract_menu",
                description:
                  "Extrai categorias e produtos de uma foto de cardápio de restaurante",
                parameters: {
                  type: "object",
                  properties: {
                    categories: {
                      type: "array",
                      items: {
                        type: "object",
                        properties: {
                          name: { type: "string", description: "Nome da categoria" },
                          products: {
                            type: "array",
                            items: {
                              type: "object",
                              properties: {
                                name: { type: "string", description: "Nome do produto" },
                                description: {
                                  type: "string",
                                  description: "Descrição do produto, vazio se não visível",
                                },
                                price: {
                                  type: "number",
                                  description: "Preço do produto (0 se não legível)",
                                },
                                sizes_prices: {
                                  type: "object",
                                  description:
                                    "Preços por tamanho se aplicável, ex: {P: 10, M: 15, G: 20}",
                                  additionalProperties: { type: "number" },
                                },
                              },
                              required: ["name", "description", "price"],
                              additionalProperties: false,
                            },
                          },
                        },
                        required: ["name", "products"],
                        additionalProperties: false,
                      },
                    },
                  },
                  required: ["categories"],
                  additionalProperties: false,
                },
              },
            },
          ],
          tool_choice: { type: "function", function: { name: "extract_menu" } },
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
        JSON.stringify({ error: "Erro ao processar imagens com IA" }),
        { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    const data = await response.json();

    // Extract tool call result
    const toolCall = data.choices?.[0]?.message?.tool_calls?.[0];
    if (!toolCall) {
      // Fallback: try to parse from content
      const content = data.choices?.[0]?.message?.content;
      if (content) {
        try {
          const parsed = JSON.parse(content);
          return new Response(JSON.stringify(parsed), {
            headers: { ...corsHeaders, "Content-Type": "application/json" },
          });
        } catch {
          // ignore
        }
      }
      return new Response(
        JSON.stringify({ error: "IA não retornou dados estruturados" }),
        { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    const menuData = JSON.parse(toolCall.function.arguments);

    return new Response(JSON.stringify(menuData), {
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  } catch (e) {
    console.error("menu-import-ai error:", e);
    return new Response(
      JSON.stringify({ error: e instanceof Error ? e.message : "Erro desconhecido" }),
      { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
    );
  }
});
