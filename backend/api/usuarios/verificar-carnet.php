<?php
declare(strict_types=1);
header('Content-Type: application/json');

require __DIR__ . '/../../config/config.php';

// Verifica si el usuario tiene carnet validado
try {
  $usuario_id = $authUser['id'];

  $stmt = $pdo->prepare("SELECT carnet_validado, carnet_vencimiento FROM usuarios WHERE id = ?");
  $stmt->execute([$usuario_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
  }

  $validado = (bool)$user['carnet_validado'];
  $vencimiento = $user['carnet_vencimiento'];

  if ($validado && $vencimiento && strtotime($vencimiento) > time()) {
    echo json_encode(['validado' => true]);
  } else {
    echo json_encode(['validado' => false]);
  }

} catch (Exception $e) {
  echo json_encode(['error' => 'Error al verificar carnet: ' . $e->getMessage()]);
}
