<?php
declare(strict_types=1);

function db(): PDO {
  $dsn  = 'mysql:host=127.0.0.1;dbname=subete;charset=utf8mb4';
  $user = 'root';        // XAMPP default
  $pass = '';            // XAMPP default (si tenés contraseña, ponela acá)
  $opt  = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];
  return new PDO($dsn, $user, $pass, $opt);
}
