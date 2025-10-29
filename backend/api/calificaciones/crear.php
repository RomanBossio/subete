<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../config/db.php'; // desde api/calificaciones hasta config/db.php
$pdo = db();

/* ---------- AUTH ---------- */
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? ''; // Simplificado
if (!$auth || stripos($auth, 'bearer ') !== 0) {
  http_response_code(401); // Devolver código de error apropiado
  echo json_encode(['ok'=>false,'error'=>'No hay token o tiene formato incorrecto']); exit;
}
$token = trim(substr($auth, 7)); // Añadir trim

// Verificar token y obtener ID usuario (pasajero)
try {
    $q = $pdo->prepare("SELECT s.id_usuario FROM sesiones s WHERE s.token = ? AND s.expira_en > NOW() LIMIT 1"); // Añadir chequeo de expiración
    $q->execute([$token]);
    $me = $q->fetch(PDO::FETCH_ASSOC);
    if (!$me) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit;
    }
    $id_user = (int)$me['id_usuario']; // ID del pasajero que está calificando
} catch (Throwable $e) {
    error_log("Error DB auth calificaciones: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno del servidor']); exit;
}


/* ---------- BODY ---------- */
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$ID_Viaje   = (int)($in['ID_Viaje'] ?? $in['id_viaje'] ?? 0);
$Puntuacion = (int)($in['Puntuacion'] ?? $in['puntuacion'] ?? 0);
// Permitir comentario vacío, no solo null
$Comentario = isset($in['Comentario']) ? trim((string)$in['Comentario']) : '';

if ($ID_Viaje <= 0 || $Puntuacion < 1 || $Puntuacion > 5) {
  http_response_code(400); // Bad Request
  echo json_encode(['ok'=>false,'error'=>'Datos inválidos (ID Viaje o Puntuación fuera de rango 1-5)']); exit;
}

/* ---------- VALIDACIONES ---------- */
try {
    // 1) El usuario debe tener reserva en ese viaje y obtener datos necesarios
    $qr = $pdo->prepare("
      SELECT r.ID_Reserva, r.estado AS estado_reserva,
             v.ID_Usuario AS ID_Conductor, v.Estado AS estado_viaje,
             v.Origen, v.Destino /* Añadido para posible uso futuro o logs */
      FROM reservas r
      JOIN viajes v ON v.ID_Viaje = r.ID_Viaje
      WHERE r.ID_Viaje = ? AND r.ID_Usuario = ?
      ORDER BY r.Fecha_Reserva DESC
      LIMIT 1
    ");
    $qr->execute([$ID_Viaje, $id_user]);
    $row = $qr->fetch(PDO::FETCH_ASSOC);

    // Si no encontró reserva, devolver error claro
    if (!$row) {
        http_response_code(404); // Not Found (o 403 Forbidden si prefieres)
        echo json_encode(['ok'=>false,'error'=>'No se encontró una reserva tuya para este viaje.']); exit;
    }

    $ID_Conductor   = (int)$row['ID_Conductor'];
    $estado_viaje   = $row['estado_viaje'] ?? '';
    $estado_reserva = $row['estado_reserva'] ?? '';

    // 2) Solo calificar viajes completados
    if ($estado_viaje !== 'Completado') {
      http_response_code(409); // Conflict - El estado no permite la acción
      echo json_encode(['ok'=>false,'error'=>'Solo podés calificar viajes que ya han sido marcados como completados.']); exit;
    }
    // Opcional: Podrías quitar la validación de reserva cancelada si quieres permitir calificar igualmente
    // if ($estado_reserva === 'cancelado') {
    //   http_response_code(409);
    //   echo json_encode(['ok'=>false,'error'=>'Tu reserva para este viaje fue cancelada, no puedes calificarlo.']); exit;
    // }

    // 3) Evitar auto-calificación
    if ($ID_Conductor === $id_user) {
      http_response_code(403); // Forbidden
      echo json_encode(['ok'=>false,'error'=>'No puedes calificar un viaje que tú mismo condujiste.']); exit;
    }

    /* ---------- INSERT/UPDATE ---------- */
    // Usar transacción para asegurar atomicidad si hubiera más pasos
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO calificaciones (ID_Viaje, ID_Conductor, ID_Pasajero, Puntuacion, Comentario, Fecha)
        VALUES (:v,:c,:p,:punt,:com, NOW())
        ON DUPLICATE KEY UPDATE
          Puntuacion = VALUES(Puntuacion),
          Comentario = VALUES(Comentario),
          Fecha      = NOW()
      ");
    $stmt->execute([
        ':v'    => $ID_Viaje,
        ':c'    => $ID_Conductor,
        ':p'    => $id_user, // El pasajero que califica
        ':punt' => $Puntuacion,
        ':com'  => $Comentario,
    ]);

    // --- Crear notificación para el CONDUCTOR (MODIFICADO) ---
try {
    // Obtener nombre del pasajero que calificó
    $q_pasajero = $pdo->prepare("SELECT Nombre FROM usuarios WHERE ID_Usuario = ?");
    $q_pasajero->execute([$id_user]); // $id_user es el pasajero
    $pasajero = $q_pasajero->fetch();
    $nombre_pasajero = $pasajero ? $pasajero['Nombre'] : 'Un pasajero';

    // Construir el mensaje base
    $mensaje = $nombre_pasajero . " te ha calificado con " . $Puntuacion . " estrellas.";

    // Añadir el comentario SI NO ESTÁ VACÍO
    if (!empty($Comentario)) {
        // Usar mb_strimwidth para acortar comentarios largos y evitar que rompan el diseño
        $comentarioCorto = mb_strimwidth($Comentario, 0, 100, "..."); // Acorta a 100 caracteres máx.
        $mensaje .= " Comentó: \"" . htmlspecialchars($comentarioCorto) . "\""; // Usar htmlspecialchars por seguridad
    }

    $q_notif = $pdo->prepare(
        "INSERT INTO notificaciones (ID_Usuario_Destino, Tipo, Mensaje, ID_Viaje_Relacionado) VALUES (?, 'nueva_calificacion', ?, ?)"
    );
    // La notificación es para el CONDUCTOR
    $q_notif->execute([$ID_Conductor, $mensaje, $ID_Viaje]);

} catch (Throwable $e) {
    // Importante: Si falla la notificación, no deshacer la calificación. Solo loguear.
    error_log("Error al crear notificación de calificación (ViajeID: $ID_Viaje, ConductorID: $ID_Conductor): " . $e->getMessage());
}
// --- FIN Notificación ---
    $pdo->commit(); // Confirmar la calificación (y la notificación si no falló)

    echo json_encode(['ok'=>true,'msg'=>'Calificación registrada con éxito.']);

} catch (PDOException $e) { // Capturar errores específicos de PDO
    if ($pdo->inTransaction()) $pdo->rollBack(); // Deshacer si hubo error DB
    error_log("Error PDO en calificaciones/crear: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error al interactuar con la base de datos.']); // Mensaje genérico

} catch (Throwable $e) { // Capturar otros errores inesperados
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error inesperado en calificaciones/crear: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno del servidor.']);
}