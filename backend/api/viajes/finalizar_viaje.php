<?php
declare(strict_types=1);
require_once('../../config/db.php');
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

try {
    // --- Auth por token ---
    $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!$auth || stripos($auth, 'bearer ') !== 0) { throw new Exception('No hay token de sesión', 401); }
    $token = substr($auth, 7);

    // Traer sesión + rol
    $stmt = $pdo->prepare("SELECT s.id_usuario, u.rol FROM sesiones s JOIN usuarios u ON u.ID_Usuario = s.id_usuario WHERE s.token = ? AND s.expira_en > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$session) { throw new Exception('Token inválido o expirado', 401); }

    $id_usuario = (int)$session['id_usuario']; // ID del conductor/admin que finaliza
    $rol        = $session['rol'] ?? 'usuario';

    // --- Body ---
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $id_viaje = isset($data['ID_Viaje']) ? (int)$data['ID_Viaje'] : (int)($data['id_viaje'] ?? 0);
    if ($id_viaje <= 0) { throw new Exception('Falta ID del viaje', 400); }

    $pdo->beginTransaction();

    // Lock del viaje para evitar condiciones de carrera
    $q = $pdo->prepare("SELECT * FROM viajes WHERE ID_Viaje = ? FOR UPDATE");
    $q->execute([$id_viaje]);
    $viaje = $q->fetch(PDO::FETCH_ASSOC);
    if (!$viaje) { $pdo->rollBack(); throw new Exception('Viaje no encontrado', 404); }

    // Permiso: conductor del viaje o admin
    if ((int)$viaje['ID_Usuario'] !== $id_usuario && $rol !== 'admin') { $pdo->rollBack(); throw new Exception('No autorizado', 403); }

    // Debe estar "En proceso" para finalizarse
    if (($viaje['Estado'] ?? '') !== 'En proceso') { $pdo->rollBack(); throw new Exception('Solo se puede finalizar un viaje "En proceso"', 409); } // 409 Conflict

    // Marcar viaje como "Completado"
    $up = $pdo->prepare("UPDATE viajes SET Estado = 'Completado' WHERE ID_Viaje = ?");
    $up->execute([$id_viaje]);

    // Contar pasajeros pagados y guardar histórico (opcional pero buena práctica)
    $sum = $pdo->prepare("SELECT COALESCE(SUM(cantidad),0) AS cant FROM reservas WHERE ID_Viaje = ? AND Estado = 'pagado'");
    $sum->execute([$id_viaje]);
    $pasajerosPagados = (int)$sum->fetchColumn();

    $ins = $pdo->prepare("INSERT INTO viajes_finalizados (ID_Usuario, origen, destino, fecha_salida, fecha_llegada, cantidad_pasajeros) VALUES (:u, :o, :d, :fs, NOW(), :cp)");
    $ins->execute([ ':u' => (int)$viaje['ID_Usuario'], ':o' => $viaje['Origen'], ':d' => $viaje['Destino'], ':fs' => $viaje['Fecha_Hora_Salida'], ':cp' => $pasajerosPagados ]);

    $pdo->commit(); // Confirmar cambios en la BD

    // --- NUEVO: Notificar a los PASAJEROS PAGADOS ---
    try {
        $q_pasajeros = $pdo->prepare("SELECT ID_Usuario FROM reservas WHERE ID_Viaje = ? AND Estado = 'pagado'");
        $q_pasajeros->execute([$id_viaje]);
        $pasajeros = $q_pasajeros->fetchAll(PDO::FETCH_COLUMN); // Obtener solo los IDs

        if (!empty($pasajeros)) {
            $mensajeNotif = "El viaje {$viaje['Origen']} → {$viaje['Destino']} ha finalizado. ¡No olvides calificar al conductor!";
            $q_notif = $pdo->prepare("INSERT INTO notificaciones (ID_Usuario_Destino, Tipo, Mensaje, ID_Viaje_Relacionado) VALUES (?, 'nueva_calificacion', ?, ?)"); // Usamos 'nueva_calificacion' para que el link lleve al detalle y puedan calificar

            foreach ($pasajeros as $id_pasajero) {
                // Evitar notificar al conductor si por error estuviera en la lista
                if ((int)$id_pasajero !== (int)$viaje['ID_Usuario']) {
                    $q_notif->execute([(int)$id_pasajero, $mensajeNotif, $id_viaje]);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Error al crear notificación de viaje finalizado: ' . $e->getMessage()); // Loguear pero no fallar la respuesta principal
    }
    // --- FIN DEL CÓDIGO NUEVO ---

    echo json_encode([
        'ok' => true,
        'msg' => 'Viaje finalizado correctamente',
        'nuevo_estado' => 'Completado',
        'ID_Viaje' => $id_viaje,
        'pasajeros_pagados' => $pasajerosPagados
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $httpCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($httpCode);
    error_log('ERROR finalizar_viaje: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
    echo json_encode(['ok'=>false,'error'=> $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log('ERROR INESPERADO finalizar_viaje: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'Error interno del servidor']);
}