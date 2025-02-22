<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=kamerverhuur;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // error behandling
} catch (PDOException $e) {
    die("Databaseverbinding mislukt: " . $e->getMessage());
}
// Haal de rol van de huidige gebruiker op
function getCurrentUserRole($pdo): mixed {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT role FROM Users WHERE id = :id"); // tijdelijk waarde
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user['role'] ?? null;
    }
    return null;
}
?>