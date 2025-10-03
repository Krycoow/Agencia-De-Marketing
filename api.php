<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'zion_marketing';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'save_appointment':
        saveAppointment($pdo);
        break;
    case 'get_appointments':
        getAppointments($pdo);
        break;
    case 'save_message':
        saveMessage($pdo);
        break;
    case 'get_messages':
        getMessages($pdo);
        break;
    case 'admin_login':
        adminLogin($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}

function saveAppointment($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("INSERT INTO appointments (name, email, phone, service, message) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $data['name'],
        $data['email'],
        $data['phone'],
        $data['service'],
        $data['message']
    ]);
    
    if ($result) {
        $appointmentId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'appointment_id' => $appointmentId]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar la cita']);
    }
}

function getAppointments($pdo) {
    $stmt = $pdo->query("SELECT * FROM appointments ORDER BY created_at DESC");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($appointments);
}

function saveMessage($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("INSERT INTO chat_messages (appointment_id, sender_type, message) VALUES (?, ?, ?)");
    $result = $stmt->execute([
        $data['appointment_id'],
        $data['sender_type'],
        $data['message']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el mensaje']);
    }
}

function getMessages($pdo) {
    $appointmentId = $_GET['appointment_id'] ?? null;
    
    if ($appointmentId) {
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE appointment_id = ? ORDER BY created_at ASC");
        $stmt->execute([$appointmentId]);
    } else {
        $stmt = $pdo->query("SELECT cm.*, a.name as client_name, a.email FROM chat_messages cm 
                           LEFT JOIN appointments a ON cm.appointment_id = a.id 
                           ORDER BY cm.created_at DESC");
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
}

function adminLogin($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND password = ?");
    $stmt->execute([$data['email'], $data['password']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo json_encode(['success' => true, 'admin' => $admin]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales incorrectas']);
    }
}
?>
