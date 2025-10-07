<?php
require_once('../../conexion.php');
date_default_timezone_set('America/Argentina/Buenos_Aires');

$ahora = date('Y-m-d H:i:s');

// Si la hora actual >= salida y no está completado → poner en proceso
$sql = "UPDATE viajes 
        SET Estado = 'En proceso' 
        WHERE Estado = 'Disponible' 
        AND Fecha_Hora_Salida <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ahora);
$stmt->execute();

echo json_encode(["status" => "success"]);
?>
