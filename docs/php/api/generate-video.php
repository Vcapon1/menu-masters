<?php
/**
 * PREMIUM MENU - API de Geração de Vídeo por IA
 * 
 * Proxy para a Edge Function menu-generate-video (Vertex AI Veo).
 * Suporta dois fluxos:
 *   POST action=generate  - Inicia geração de vídeo a partir de imagem + estilo
 *   POST action=poll      - Verifica status de operação em andamento
 *   POST action=download  - Baixa vídeo do GCS e salva localmente
 * 
 * Requer autenticação de admin (session).
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticação
if (!isset($_SESSION['restaurant_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// URL da Edge Function
define('VIDEO_EDGE_URL', 'https://qmpikyymjcnmocjfmvxs.supabase.co/functions/v1/menu-generate-video');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        // =====================================================
        // INICIAR GERAÇÃO DE VÍDEO
        // =====================================================
        case 'generate':
            $image = $input['image'] ?? '';
            $style = $input['style'] ?? '';
            $foodName = $input['food_name'] ?? '';

            if (empty($image)) {
                throw new Exception('Imagem é obrigatória');
            }
            if (empty($style)) {
                throw new Exception('Estilo é obrigatório');
            }

            // Verificar se o plano suporta vídeo
            $restaurant = getRestaurantById($_SESSION['restaurant_id']);
            if (!$restaurant || !$restaurant['supports_video']) {
                throw new Exception('Seu plano não suporta geração de vídeos.');
            }

            // Chamar Edge Function
            $payload = json_encode([
                'action' => 'generate',
                'image' => $image,
                'style' => $style,
                'food_name' => $foodName,
            ]);

            $ch = curl_init(VIDEO_EDGE_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception('Erro de conexão: ' . $curlError);
            }

            $data = json_decode($response, true);

            if ($httpCode !== 200) {
                throw new Exception($data['error'] ?? 'Erro ao iniciar geração de vídeo');
            }

            echo json_encode([
                'success' => true,
                'operation_name' => $data['operation_name'],
                'style_name' => $data['style_name'] ?? '',
                'message' => $data['message'] ?? 'Geração iniciada',
            ]);
            break;

        // =====================================================
        // VERIFICAR STATUS DA GERAÇÃO
        // =====================================================
        case 'poll':
            $operationName = $input['operation_name'] ?? '';
            if (empty($operationName)) {
                throw new Exception('operation_name é obrigatório');
            }

            $payload = json_encode([
                'action' => 'poll',
                'operation_name' => $operationName,
            ]);

            $ch = curl_init(VIDEO_EDGE_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception('Erro de conexão: ' . $curlError);
            }

            $data = json_decode($response, true);

            if ($httpCode !== 200) {
                throw new Exception($data['error'] ?? 'Erro ao verificar status');
            }

            if (!empty($data['done'])) {
                if (!empty($data['error'])) {
                    echo json_encode([
                        'success' => false,
                        'done' => true,
                        'error' => $data['error'],
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'done' => true,
                        'video_uri' => $data['video_uri'],
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'done' => false,
                    'progress' => $data['progress'] ?? 0,
                    'state' => $data['state'] ?? 'RUNNING',
                ]);
            }
            break;

        // =====================================================
        // BAIXAR VÍDEO DO GCS E SALVAR LOCALMENTE
        // =====================================================
        case 'download':
            $videoUri = $input['video_uri'] ?? '';
            $productId = (int)($input['product_id'] ?? 0);

            if (empty($videoUri)) {
                throw new Exception('video_uri é obrigatório');
            }

            $restaurantId = $_SESSION['restaurant_id'];

            // Criar diretório de vídeos se não existir
            $videoDir = UPLOAD_DIR . "restaurants/{$restaurantId}/videos/";
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }

            $filename = 'ai_' . uniqid() . '.mp4';
            $localPath = $videoDir . $filename;

            // Se vier base64 (data URI), decodificar e salvar diretamente
            if (strpos($videoUri, 'data:video/') === 0) {
                $parts = explode(',', $videoUri, 2);
                if (count($parts) !== 2 || strpos($parts[0], ';base64') === false) {
                    throw new Exception('Formato de vídeo base64 inválido');
                }

                $binaryVideo = base64_decode($parts[1], true);
                if ($binaryVideo === false) {
                    throw new Exception('Falha ao decodificar vídeo em base64');
                }

                if (file_put_contents($localPath, $binaryVideo) === false) {
                    throw new Exception('Erro ao salvar vídeo localmente');
                }
            } elseif (preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $videoUri) && strlen($videoUri) > 1000) {
                // Fallback: payload base64 sem prefixo data:
                $binaryVideo = base64_decode($videoUri, true);
                if ($binaryVideo === false) {
                    throw new Exception('Falha ao decodificar payload de vídeo');
                }

                if (file_put_contents($localPath, $binaryVideo) === false) {
                    throw new Exception('Erro ao salvar vídeo localmente');
                }
            } else {
                // Se for URI do GCS (gs://), converter para URL pública
                if (strpos($videoUri, 'gs://') === 0) {
                    $gcsPath = substr($videoUri, 5); // Remove "gs://"
                    $downloadUrl = 'https://storage.googleapis.com/' . $gcsPath;
                } else {
                    $downloadUrl = $videoUri;
                }

                // Baixar o vídeo via HTTP
                $ch = curl_init($downloadUrl);
                $fp = fopen($localPath, 'wb');
                curl_setopt_array($ch, [
                    CURLOPT_FILE => $fp,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 120,
                ]);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                fclose($fp);

                if ($curlError || $httpCode !== 200) {
                    // Limpar arquivo parcial
                    if (file_exists($localPath)) {
                        unlink($localPath);
                    }
                    throw new Exception('Erro ao baixar vídeo: ' . ($curlError ?: "HTTP {$httpCode}"));
                }
            }

            $videoUrl = UPLOAD_URL . "restaurants/{$restaurantId}/videos/{$filename}";

            // Se product_id foi informado, atualizar o produto
            if ($productId > 0) {
                $sql = "UPDATE products SET video = :video WHERE id = :id AND restaurant_id = :rid";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    'video' => $videoUrl,
                    'id' => $productId,
                    'rid' => $restaurantId,
                ]);
            }

            echo json_encode([
                'success' => true,
                'video_url' => $videoUrl,
                'message' => 'Vídeo salvo com sucesso',
            ]);
            break;

        default:
            throw new Exception('Ação não reconhecida. Use: generate, poll, download');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
