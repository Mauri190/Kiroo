<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$user_id = getCurrentUserId();
$user_type = getCurrentUserType();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch($action) {
        // ===== VEHÍCULOS =====
        case 'get_vehicles':
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'vehicles' => $stmt->fetchAll()]);
            break;
            
        case 'save_vehicle':
            $brand = $_POST['brand'] ?? '';
            $model = $_POST['model'] ?? '';
            $plateNumber = $_POST['plate_number'] ?? '';
            $year = $_POST['year'] ?? null;
            $mileage = $_POST['mileage'] ?? 0;
            $color = $_POST['color'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $vehicle_id = $_POST['vehicle_id'] ?? null;
            
            if (empty($brand) || empty($model) || empty($plateNumber)) {
                echo json_encode(['success' => false, 'message' => 'Marca, modelo y placa son obligatorios']);
                exit;
            }
            
            if ($vehicle_id) {
                $stmt = $pdo->prepare("UPDATE vehicles SET brand=?, model=?, plate_number=?, year=?, mileage=?, color=?, notes=?, updated_at=NOW() WHERE id=? AND user_id=?");
                $stmt->execute([$brand, $model, $plateNumber, $year, $mileage, $color, $notes, $vehicle_id, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO vehicles (user_id, brand, model, plate_number, year, mileage, color, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $brand, $model, $plateNumber, $year, $mileage, $color, $notes]);
            }
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_vehicle':
            $vehicle_id = $_POST['vehicle_id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id=? AND user_id=?");
            $stmt->execute([$vehicle_id, $user_id]);
            echo json_encode(['success' => true]);
            break;
            
        // ===== CITAS =====
        case 'get_appointments':
            if ($user_type === 'cliente') {
                $stmt = $pdo->prepare("SELECT a.*, u.full_name as mechanic_name FROM appointments a JOIN users u ON u.id = a.mechanic_id WHERE a.client_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare("SELECT a.*, u.full_name as client_name FROM appointments a JOIN users u ON u.id = a.client_id WHERE a.mechanic_id = ? ORDER BY a.appointment_date ASC, a.appointment_time ASC");
                $stmt->execute([$user_id]);
            }
            echo json_encode(['success' => true, 'appointments' => $stmt->fetchAll()]);
            break;
            
        case 'save_appointment':
            $client_id = $user_type === 'cliente' ? $user_id : ($_POST['client_id'] ?? 0);
            $mechanic_id = $user_type === 'mecanico' ? $user_id : ($_POST['mechanic_id'] ?? 0);
            $appointment_date = $_POST['appointment_date'] ?? '';
            $appointment_time = $_POST['appointment_time'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $vehicle_id = $_POST['vehicle_id'] ?? null;
            $appointment_id = $_POST['appointment_id'] ?? null;
            
            if (empty($appointment_date) || empty($appointment_time)) {
                echo json_encode(['success' => false, 'message' => 'Fecha y hora son obligatorias']);
                exit;
            }
            
            if ($appointment_id) {
                $stmt = $pdo->prepare("UPDATE appointments SET appointment_date=?, appointment_time=?, notes=?, vehicle_id=?, updated_at=NOW() WHERE id=? AND client_id=?");
                $stmt->execute([$appointment_date, $appointment_time, $notes, $vehicle_id, $appointment_id, $client_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO appointments (client_id, mechanic_id, appointment_date, appointment_time, notes, vehicle_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$client_id, $mechanic_id, $appointment_date, $appointment_time, $notes, $vehicle_id]);
            }
            echo json_encode(['success' => true]);
            break;
            
        case 'update_appointment_status':
            $appointment_id = $_POST['appointment_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            $stmt = $pdo->prepare("UPDATE appointments SET status=? WHERE id=? AND mechanic_id=?");
            $stmt->execute([$status, $appointment_id, $user_id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_appointment':
            $appointment_id = $_POST['appointment_id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id=? AND (client_id=? OR mechanic_id=?)");
            $stmt->execute([$appointment_id, $user_id, $user_id]);
            echo json_encode(['success' => true]);
            break;
            
        // ===== DIAGNÓSTICOS =====
        case 'get_diagnostics':
            if ($user_type === 'cliente') {
                $stmt = $pdo->prepare("SELECT d.*, u.full_name as mechanic_name FROM diagnostics d JOIN users u ON u.id = d.mechanic_id WHERE d.client_id = ? ORDER BY d.created_at DESC");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare("SELECT d.*, u.full_name as client_name FROM diagnostics d JOIN users u ON u.id = d.client_id WHERE d.mechanic_id = ? ORDER BY d.created_at DESC");
                $stmt->execute([$user_id]);
            }
            echo json_encode(['success' => true, 'diagnostics' => $stmt->fetchAll()]);
            break;
            
        case 'save_diagnostic':
            $client_id = $_POST['client_id'] ?? 0;
            $appointment_id = $_POST['appointment_id'] ?? null;
            $vehicle_name = $_POST['vehicle_name'] ?? '';
            $mileage = $_POST['mileage'] ?? null;
            $symptoms = $_POST['symptoms'] ?? '';
            $diagnosis = $_POST['diagnosis'] ?? '';
            $recommendation = $_POST['recommendation'] ?? '';
            $vehicle_condition = $_POST['vehicle_condition'] ?? 'regular';
            $systems_status = $_POST['systems_status'] ?? null;
            $parts_needed = $_POST['parts_needed'] ?? '';
            $estimated_cost = $_POST['estimated_cost'] ?? null;
            $additional_notes = $_POST['additional_notes'] ?? '';
            
            if (empty($client_id) || empty($diagnosis) || empty($recommendation)) {
                echo json_encode(['success' => false, 'message' => 'Cliente, diagnóstico y recomendaciones son obligatorios']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO diagnostics (client_id, mechanic_id, appointment_id, vehicle_name, mileage, symptoms, diagnosis, recommendation, vehicle_condition, systems_status, parts_needed, estimated_cost, additional_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $user_id, $appointment_id, $vehicle_name, $mileage, $symptoms, $diagnosis, $recommendation, $vehicle_condition, $systems_status, $parts_needed, $estimated_cost, $additional_notes]);
            
            $diagnostic_id = $pdo->lastInsertId();
            
            // Actualizar cita si existe
            if ($appointment_id) {
                $stmt2 = $pdo->prepare("UPDATE appointments SET status='completado', diagnostic_id=? WHERE id=?");
                $stmt2->execute([$diagnostic_id, $appointment_id]);
            }
            
            echo json_encode(['success' => true, 'diagnostic_id' => $diagnostic_id]);
            break;
            
        case 'rate_diagnostic':
            $diagnostic_id = $_POST['diagnostic_id'] ?? 0;
            $rating = intval($_POST['rating'] ?? 0);
            $comment = $_POST['comment'] ?? '';
            
            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Calificación inválida']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE diagnostics SET rating=?, rating_comment=?, rated=TRUE WHERE id=? AND client_id=?");
            $stmt->execute([$rating, $comment, $diagnostic_id, $user_id]);
            echo json_encode(['success' => true]);
            break;
            
        // ===== EVENTOS/AGENDA =====
        case 'get_events':
            $stmt = $pdo->prepare("SELECT e.*, v.brand, v.model, v.plate_number FROM events e LEFT JOIN vehicles v ON v.id = e.vehicle_id WHERE e.user_id = ? ORDER BY e.event_date ASC, e.event_time ASC");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'events' => $stmt->fetchAll()]);
            break;
            
        case 'save_event':
            $title = $_POST['title'] ?? '';
            $event_date = $_POST['event_date'] ?? '';
            $event_time = $_POST['event_time'] ?? '';
            $event_type = $_POST['event_type'] ?? 'otro';
            $description = $_POST['description'] ?? '';
            $vehicle_id = $_POST['vehicle_id'] ?? null;
            $event_id = $_POST['event_id'] ?? null;
            
            if (empty($title) || empty($event_date) || empty($event_time)) {
                echo json_encode(['success' => false, 'message' => 'Título, fecha y hora son obligatorios']);
                exit;
            }
            
            if ($event_id) {
                $stmt = $pdo->prepare("UPDATE events SET title=?, event_date=?, event_time=?, event_type=?, description=?, vehicle_id=?, updated_at=NOW() WHERE id=? AND user_id=?");
                $stmt->execute([$title, $event_date, $event_time, $event_type, $description, $vehicle_id, $event_id, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO events (user_id, title, event_date, event_time, event_type, description, vehicle_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $event_date, $event_time, $event_type, $description, $vehicle_id]);
            }
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_event':
            $event_id = $_POST['event_id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM events WHERE id=? AND user_id=?");
            $stmt->execute([$event_id, $user_id]);
            echo json_encode(['success' => true]);
            break;
            
        // ===== CHAT =====
        case 'get_chat_messages':
            $other_user_id = $_POST['other_user_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE (client_id=? AND mechanic_id=?) OR (client_id=? AND mechanic_id=?) ORDER BY created_at ASC");
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            
            // Marcar mensajes como leídos
            $updateStmt = $pdo->prepare("UPDATE chat_messages SET is_read=TRUE WHERE client_id=? AND mechanic_id=? AND sender_id!=?");
            $updateStmt->execute([$user_id, $other_user_id, $user_id]);
            
            echo json_encode(['success' => true, 'messages' => $stmt->fetchAll()]);
            break;
            
        case 'send_message':
            $receiver_id = $_POST['receiver_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Mensaje vacío']);
                exit;
            }
            
            $client_id = $user_type === 'cliente' ? $user_id : $receiver_id;
            $mechanic_id = $user_type === 'mecanico' ? $user_id : $receiver_id;
            
            $stmt = $pdo->prepare("INSERT INTO chat_messages (client_id, mechanic_id, sender_id, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$client_id, $mechanic_id, $user_id, $message]);
            echo json_encode(['success' => true]);
            break;
            
        // ===== CLIENTES PARA MECÁNICOS =====
        case 'get_my_clients':
            if ($user_type !== 'mecanico') {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.full_name, u.email, u.phone FROM users u JOIN appointments a ON a.client_id = u.id WHERE a.mechanic_id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'clients' => $stmt->fetchAll()]);
            break;
            
        // ===== PERFIL =====
        case 'get_profile':
            $stmt = $pdo->prepare("SELECT id, username, email, full_name, phone, user_type, specialty, workshop_name, experience_years, created_at FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'profile' => $stmt->fetch()]);
            break;
            
        case 'update_profile':
            $full_name = $_POST['full_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            if (empty($full_name)) {
                echo json_encode(['success' => false, 'message' => 'Nombre completo es obligatorio']);
                exit;
            }
            
            if ($user_type === 'mecanico') {
                $specialty = $_POST['specialty'] ?? '';
                $workshop_name = $_POST['workshop_name'] ?? '';
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, specialty=?, workshop_name=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$full_name, $phone, $specialty, $workshop_name, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$full_name, $phone, $user_id]);
            }
            
            $_SESSION['full_name'] = $full_name;
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch(PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>