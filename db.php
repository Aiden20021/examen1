<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=kamerverhuur;charset=utf8', 'root', '');
    $pdo->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Databaseverbinding mislukt: " . $e->getMessage());
}
// Haal de rol van de huidige gebruiker op
function getCurrentUserRole($pdo): mixed {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT role FROM Users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user['role'] ?? null;
    }
    return null;
}
?>