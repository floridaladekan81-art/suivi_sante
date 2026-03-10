<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'suivi_sante');
define('DB_USER', 'root'); // Remplacez par votre utilisateur XAMPP si nécessaire
define('DB_PASS', ''); // Remplacez par votre mot de passe XAMPP si nécessaire

function getDBConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Génère des exceptions en cas d'erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourne les résultats sous forme de tableau associatif
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Utilise les requêtes préparées du C (plus sûr)
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // En production, il est préférable de journaliser l'erreur plutôt que de l'afficher
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>
