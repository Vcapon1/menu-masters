<?php
/**
 * PREMIUM MENU - API de Pedidos
 * 
 * Endpoints JSON para criar, buscar e atualizar pedidos.
 * 
 * POST /api/orders.php?action=create   - Criar pedido
 * GET  /api/orders.php?action=status&token=xxx - Status do pedido (público)
 * GET  /api/orders.php?action=list&restaurant_id=1&status=pending - Listar pedidos (admin)
 * POST /api/orders.php?action=update_status - Atualizar status (admin)
 * POST /api/orders.php?action=toggle_open - Abrir/Fechar restaurante (admin)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Dados inválidos');
            }
            
            $restaurantId = (int)($input['restaurant_id'] ?? 0);
            $cartMode = sanitize($input['cart_mode'] ?? '');
            $items = $input['items'] ?? [];
            
            if ($restaurantId === 0 || empty($cartMode) || empty($items)) {
                throw new Exception('Dados obrigatórios ausentes');
            }
            
            // Verificar se restaurante está aberto
            $restaurant = getRestaurantById($restaurantId);
            if (!$restaurant || !$restaurant['is_open']) {
                throw new Exception('Restaurante fechado no momento');
            }
            
            $order = createOrder([
                'restaurant_id' => $restaurantId,
                'cart_mode' => $cartMode,
                'table_number' => sanitize($input['table_number'] ?? ''),
                'customer_name' => sanitize($input['customer_name'] ?? ''),
                'customer_phone' => sanitize($input['customer_phone'] ?? ''),
                'customer_address' => sanitize($input['customer_address'] ?? ''),
                'notes' => sanitize($input['notes'] ?? ''),
                'items' => $items
            ]);
            
            echo json_encode(['success' => true, 'order' => $order]);
            break;
            
        case 'status':
            $token = sanitize($_GET['token'] ?? '');
            if (empty($token)) {
                throw new Exception('Token não informado');
            }
            
            $order = getOrderByToken($token);
            if (!$order) {
                throw new Exception('Pedido não encontrado');
            }
            
            // Buscar itens do pedido
            $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
            $stmt = db()->prepare($sql);
            $stmt->execute(['order_id' => $order['id']]);
            $order['items'] = $stmt->fetchAll();
            
            // Buscar dados do restaurante
            $restaurant = getRestaurantById($order['restaurant_id']);
            $order['restaurant_name'] = $restaurant['name'] ?? '';
            $order['restaurant_logo'] = $restaurant['logo'] ?? '';
            
            echo json_encode(['success' => true, 'order' => $order]);
            break;
            
        case 'list':
            session_start();
            if (!isset($_SESSION['restaurant_id'])) {
                throw new Exception('Não autorizado');
            }
            
            $restaurantId = $_SESSION['restaurant_id'];
            $status = sanitize($_GET['status'] ?? '');
            $archived = isset($_GET['archived']) && $_GET['archived'] === '1';
            
            $orders = getRestaurantOrders($restaurantId, $status ?: null, $archived);
            
            // Buscar itens de cada pedido
            foreach ($orders as &$order) {
                $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
                $stmt = db()->prepare($sql);
                $stmt->execute(['order_id' => $order['id']]);
                $order['items'] = $stmt->fetchAll();
            }
            
            echo json_encode(['success' => true, 'orders' => $orders]);
            break;
            
        case 'update_status':
            session_start();
            if (!isset($_SESSION['restaurant_id'])) {
                throw new Exception('Não autorizado');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $orderId = (int)($input['order_id'] ?? 0);
            $newStatus = sanitize($input['status'] ?? '');
            
            if ($orderId === 0 || empty($newStatus)) {
                throw new Exception('Dados obrigatórios ausentes');
            }
            
            // Verificar que o pedido pertence ao restaurante
            $sql = "SELECT id FROM orders WHERE id = :id AND restaurant_id = :rid";
            $stmt = db()->prepare($sql);
            $stmt->execute(['id' => $orderId, 'rid' => $_SESSION['restaurant_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Pedido não encontrado');
            }
            
            updateOrderStatus($orderId, $newStatus);
            echo json_encode(['success' => true]);
            break;

        case 'archive':
            session_start();
            if (!isset($_SESSION['restaurant_id'])) {
                throw new Exception('Não autorizado');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $orderId = (int)($input['order_id'] ?? 0);
            $archive = (bool)($input['archive'] ?? true);
            
            if ($orderId === 0) {
                throw new Exception('order_id obrigatório');
            }
            
            // Verificar que o pedido pertence ao restaurante
            $sql = "SELECT id FROM orders WHERE id = :id AND restaurant_id = :rid";
            $stmt = db()->prepare($sql);
            $stmt->execute(['id' => $orderId, 'rid' => $_SESSION['restaurant_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Pedido não encontrado');
            }
            
            $sql = "UPDATE orders SET is_archived = :archived WHERE id = :id";
            $stmt = db()->prepare($sql);
            $stmt->execute(['archived' => $archive ? 1 : 0, 'id' => $orderId]);
            echo json_encode(['success' => true]);
            break;

        case 'archive_delivered':
            session_start();
            if (!isset($_SESSION['restaurant_id'])) {
                throw new Exception('Não autorizado');
            }
            
            $sql = "UPDATE orders SET is_archived = 1 WHERE restaurant_id = :rid AND status IN ('delivered', 'cancelled') AND is_archived = 0";
            $stmt = db()->prepare($sql);
            $stmt->execute(['rid' => $_SESSION['restaurant_id']]);
            echo json_encode(['success' => true, 'archived_count' => $stmt->rowCount()]);
            break;
            
        case 'restaurant_status':
            $restaurantId = (int)($_GET['restaurant_id'] ?? 0);
            if ($restaurantId === 0) {
                throw new Exception('restaurant_id obrigatório');
            }
            $restaurant = getRestaurantById($restaurantId);
            if (!$restaurant) {
                throw new Exception('Restaurante não encontrado');
            }
            echo json_encode(['success' => true, 'is_open' => (bool)($restaurant['is_open'] ?? false)]);
            break;

        case 'toggle_open':
            session_start();
            if (!isset($_SESSION['restaurant_id'])) {
                throw new Exception('Não autorizado');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $isOpen = (bool)($input['is_open'] ?? false);
            
            toggleRestaurantOpen($_SESSION['restaurant_id'], $isOpen);
            echo json_encode(['success' => true, 'is_open' => $isOpen]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
