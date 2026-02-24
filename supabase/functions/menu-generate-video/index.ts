import { serve } from "https://deno.land/std@0.168.0/http/server.ts";
import { encode as base64url } from "https://deno.land/std@0.168.0/encoding/base64url.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers":
    "authorization, x-client-info, apikey, content-type, x-supabase-client-platform, x-supabase-client-platform-version, x-supabase-client-runtime, x-supabase-client-runtime-version",
};

const PROJECT_ID = "videoscardapio";
const LOCATION = "us-central1";
const MODEL_ID = "veo-3.0-generate-preview";
const SCOPE = "https://www.googleapis.com/auth/cloud-platform";

const AI_PLATFORM_REGIONAL_BASE = `https://${LOCATION}-aiplatform.googleapis.com/v1`;
const FETCH_PREDICT_OPERATION_URL = `${AI_PLATFORM_REGIONAL_BASE}/projects/${PROJECT_ID}/locations/${LOCATION}/publishers/google/models/${MODEL_ID}:fetchPredictOperation`;

const VIDEO_STYLES: Record<string, { name: string; prompt: string }> = {
  cheese_pull: {
    name: "Cheese Pull",
    prompt:
      "Slow motion extreme close-up cinematic shot. The food is being pulled apart revealing stretchy melted cheese. Warm golden lighting, shallow depth of field, steam rising. Professional food commercial quality, 9:16 vertical format, smooth camera movement.",
  },
  spinning_plate: {
    name: "Prato Girando",
    prompt:
      "Cinematic rotating plate shot. The dish spins slowly on a turntable, camera at slight angle. Dramatic studio lighting with rim light, dark moody background. Professional food photography style, 9:16 vertical format, smooth 360 rotation.",
  },
  macro_detail: {
    name: "Macro Detail",
    prompt:
      "Ultra macro close-up cinematic shot. Extreme detail of food textures, grains, droplets, and surfaces. Rack focus effect revealing intricate details. Warm natural lighting, shallow depth of field. Professional food documentary style, 9:16 vertical format.",
  },
  steam_heat: {
    name: "Vapor & Calor",
    prompt:
      "Cinematic shot of hot food with visible steam and heat haze rising. Dramatic backlit steam effect, warm amber lighting. The steam dances and swirls in slow motion. Dark background to emphasize steam. Professional food commercial, 9:16 vertical format.",
  },
};

// --- JWT / OAuth helpers ---

async function importPrivateKey(pem: string): Promise<CryptoKey> {
  const pemContents = pem
    .replace(/-----BEGIN PRIVATE KEY-----/g, "")
    .replace(/-----END PRIVATE KEY-----/g, "")
    .replace(/\s/g, "");

  const binaryDer = Uint8Array.from(atob(pemContents), (c) => c.charCodeAt(0));

  return crypto.subtle.importKey(
    "pkcs8",
    binaryDer,
    { name: "RSASSA-PKCS1-v1_5", hash: "SHA-256" },
    false,
    ["sign"]
  );
}

function textEncode(str: string): Uint8Array {
  return new TextEncoder().encode(str);
}

async function createSignedJwt(
  clientEmail: string,
  privateKey: string
): Promise<string> {
  const now = Math.floor(Date.now() / 1000);

  const header = { alg: "RS256", typ: "JWT" };
  const payload = {
    iss: clientEmail,
    sub: clientEmail,
    aud: "https://oauth2.googleapis.com/token",
    iat: now,
    exp: now + 3600,
    scope: SCOPE,
  };

  const headerB64 = base64url(textEncode(JSON.stringify(header)));
  const payloadB64 = base64url(textEncode(JSON.stringify(payload)));
  const signingInput = `${headerB64}.${payloadB64}`;

  const key = await importPrivateKey(privateKey);
  const signature = await crypto.subtle.sign(
    "RSASSA-PKCS1-v1_5",
    key,
    textEncode(signingInput)
  );

  const signatureB64 = base64url(new Uint8Array(signature));
  return `${signingInput}.${signatureB64}`;
}

async function getAccessToken(
  clientEmail: string,
  privateKey: string
): Promise<string> {
  const jwt = await createSignedJwt(clientEmail, privateKey);

  const resp = await fetch("https://oauth2.googleapis.com/token", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      grant_type: "urn:ietf:params:oauth:grant-type:jwt-bearer",
      assertion: jwt,
    }),
  });

  if (!resp.ok) {
    const err = await resp.text();
    console.error("OAuth token error:", err);
    throw new Error("Falha ao obter token de acesso do Google Cloud");
  }

  const data = await resp.json();
  return data.access_token;
}

// --- Main handler ---

serve(async (req) => {
  if (req.method === "OPTIONS") {
    return new Response(null, { headers: corsHeaders });
  }

  try {
    const saJson = Deno.env.get("GOOGLE_VERTEX_API_KEY");
    if (!saJson) {
      throw new Error("GOOGLE_VERTEX_API_KEY não configurada");
    }

    const sa = JSON.parse(saJson);
    const clientEmail = sa.client_email;
    const privateKey = sa.private_key;

    if (!clientEmail || !privateKey) {
      throw new Error("Service Account JSON inválido: faltam client_email ou private_key");
    }

    const { action, image, style, food_name, operation_name } = await req.json();

    // --- POLL action: check status of long-running operation ---
    if (action === "poll") {
      if (!operation_name) {
        return new Response(
          JSON.stringify({ error: "operation_name é obrigatório para poll" }),
          { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
        );
      }

      const accessToken = await getAccessToken(clientEmail, privateKey);

      // If operation_name is just a UUID, construct the full path
      const fullOperationName = operation_name.includes("/")
        ? operation_name
        : `projects/${PROJECT_ID}/locations/${LOCATION}/publishers/google/models/${MODEL_ID}/operations/${operation_name}`;

      console.log("Polling operation:", fullOperationName);

      const pollResp = await fetch(FETCH_PREDICT_OPERATION_URL, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${accessToken}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ operationName: fullOperationName }),
      });

      if (!pollResp.ok) {
        const errText = await pollResp.text();
        console.error("Poll error:", pollResp.status, errText);
        console.error("Poll URL used:", FETCH_PREDICT_OPERATION_URL);
        console.error("Operation name sent:", operation_name);
        return new Response(
          JSON.stringify({ error: "Erro ao verificar status do vídeo", status: pollResp.status, details: errText }),
          { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
        );
      }

      const opData = await pollResp.json();

      if (opData.done) {
        if (opData.error) {
          return new Response(
            JSON.stringify({ done: true, error: opData.error.message || "Erro na geração do vídeo" }),
            { headers: { ...corsHeaders, "Content-Type": "application/json" } }
          );
        }

        // Extract video URI from response
        const videoUri =
          opData.response?.generateVideoResponse?.generatedSamples?.[0]?.video?.uri ||
          opData.response?.generatedSamples?.[0]?.video?.uri;

        if (!videoUri) {
          console.error("No video URI in response:", JSON.stringify(opData).substring(0, 1000));
          return new Response(
            JSON.stringify({ done: true, error: "Vídeo gerado mas URI não encontrada" }),
            { headers: { ...corsHeaders, "Content-Type": "application/json" } }
          );
        }

        // If it's a GCS URI, generate a signed URL or return as-is
        return new Response(
          JSON.stringify({ done: true, video_uri: videoUri }),
          { headers: { ...corsHeaders, "Content-Type": "application/json" } }
        );
      }

      // Still processing
      const metadata = opData.metadata || {};
      return new Response(
        JSON.stringify({
          done: false,
          progress: metadata.progressPercent || 0,
          state: metadata.state || "RUNNING",
        }),
        { headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    // --- GENERATE action: start video generation ---
    if (!image) {
      return new Response(
        JSON.stringify({ error: "Envie uma imagem em base64" }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    if (!style || !VIDEO_STYLES[style]) {
      return new Response(
        JSON.stringify({
          error: `Estilo inválido. Use: ${Object.keys(VIDEO_STYLES).join(", ")}`,
          available_styles: Object.entries(VIDEO_STYLES).map(([k, v]) => ({
            id: k,
            name: v.name,
          })),
        }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    const accessToken = await getAccessToken(clientEmail, privateKey);

    const foodContext = food_name ? `This is "${food_name}". ` : "";
    const fullPrompt = `${foodContext}${VIDEO_STYLES[style].prompt}`;

    // Prepare image data
    const imageBase64 = image.startsWith("data:")
      ? image.split(",")[1]
      : image;

    const endpoint = `https://${LOCATION}-aiplatform.googleapis.com/v1/projects/${PROJECT_ID}/locations/${LOCATION}/publishers/google/models/${MODEL_ID}:predictLongRunning`;

    const requestBody = {
      instances: [
        {
          prompt: fullPrompt,
          image: {
            bytesBase64Encoded: imageBase64,
            mimeType: "image/jpeg",
          },
        },
      ],
      parameters: {
        aspectRatio: "9:16",
        sampleCount: 1,
        durationSeconds: 6,
      },
    };

    console.log("Calling Vertex AI Veo:", endpoint);

    const genResp = await fetch(endpoint, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(requestBody),
    });

    if (!genResp.ok) {
      const errText = await genResp.text();
      console.error("Veo API error:", genResp.status, errText);

      if (genResp.status === 429) {
        return new Response(
          JSON.stringify({ error: "Limite de requisições excedido. Tente novamente em alguns minutos." }),
          { status: 429, headers: { ...corsHeaders, "Content-Type": "application/json" } }
        );
      }

      return new Response(
        JSON.stringify({ error: "Erro ao iniciar geração de vídeo", details: errText }),
        { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    const opResult = await genResp.json();
    const operationName = opResult.name;

    if (!operationName) {
      console.error("No operation name:", JSON.stringify(opResult).substring(0, 500));
      return new Response(
        JSON.stringify({ error: "API não retornou ID da operação" }),
        { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      );
    }

    return new Response(
      JSON.stringify({
        operation_name: operationName,
        style_name: VIDEO_STYLES[style].name,
        message: "Geração de vídeo iniciada. Use o operation_name para verificar o progresso.",
      }),
      { headers: { ...corsHeaders, "Content-Type": "application/json" } }
    );
  } catch (e) {
    console.error("menu-generate-video error:", e);
    return new Response(
      JSON.stringify({ error: e instanceof Error ? e.message : "Erro desconhecido" }),
      { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
    );
  }
});
