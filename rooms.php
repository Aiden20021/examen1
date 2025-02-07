<?php
session_start();
include 'db.php';
// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Haal de rol van de gebruiker op
$userRole = getCurrentUserRole($pdo);
// Controleer of de gebruiker toegang heeft
if ($userRole !== 'admin' && $userRole !== 'baliemedewerker') {
    die("U heeft geen toegang tot deze pagina.");
}
// Haal alle kamers op
$stmt = $pdo->query("SELECT * FROM Rooms");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Voeg een nieuwe kamer toe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $room_number = $_POST['room_number'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $max_capacity = $_POST['max_capacity'];
    $table_layout = $_POST['table_layout'];
    $screen_type = $_POST['screen_type'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("INSERT INTO Rooms (room_number, name, type, max_capacity, table_layout, screen_type, status) 
                           VALUES (:room_number, :name, :type, :max_capacity, :table_layout, :screen_type, :status)");
    $stmt->execute([
        'room_number' => $room_number,
        'name' => $name,
        'type' => $type,
        'max_capacity' => $max_capacity,
        'table_layout' => $table_layout,
        'screen_type' => $screen_type,
        'status' => $status
    ]);
    header("Location: rooms.php");
    exit;
}
// Soft delete een kamer (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete' && $userRole === 'admin') {
    $room_id = $_POST['room_id'] ?? null;
    if (!$room_id) {
        die("Fout: Geen geldige kamer-ID opgegeven.");
    }
    try {
        // Update de status van de kamer naar "niet beschikbaar" en markeer als "verwijderd"
        $stmt = $pdo->prepare("UPDATE Rooms SET status = 'niet beschikbaar', deleted = 'ja' WHERE id = :id");
        $stmt->execute(['id' => $room_id]);
        header("Location: rooms.php");
        exit;
    } catch (PDOException $e) {
        echo "Fout bij het verwijderen van de kamer: " . $e->getMessage();
    }
}
// Haal alleen actieve kamers op
$stmt = $pdo->query("SELECT * FROM Rooms WHERE deleted = 'nee'");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De Samenkomst - Kamers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <h1>De Samenkomst - Kamers</h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="rooms.php">Kamers</a>
            <a href="guests.php">Gasten</a>
            <a href="reservations.php">Reserveringen</a>
            <a href="logout.php">Uitloggen</a>
        </nav>
    </header>
    <main>
        <!-- Nieuwe kamer toevoegen -->
        <section>
            <h2>Nieuwe kamer toevoegen</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <label for="room_number">Kamernummer:</label>
                <input type="text" id="room_number" name="room_number" required><br><br>
                <label for="name">Naam:</label>
                <input type="text" id="name" name="name" required><br><br>
                <label for="type">Type:</label>
                <select id="type" name="type" required>
                    <option value="kantoorruimte">Kantoorruimte</option>
                    <option value="vergaderkamer">Vergaderruimte</option>
                </select><br><br>
                <label for="max_capacity">Max capaciteit:</label>
                <input type="number" id="max_capacity" name="max_capacity" required><br><br>
                <label for="table_layout">Tafelopstelling:</label>
                <input type="text" id="table_layout" name="table_layout"><br><br>
                <label for="screen_type">Beeldschermtype:</label>
                <input type="text" id="screen_type" name="screen_type"><br><br>
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="beschikbaar">Beschikbaar</option>
                    <option value="niet beschikbaar">Niet beschikbaar</option>
                </select><br><br>
                <button type="submit">Toevoegen</button>
            </form>
        </section>
        <!-- Alle kamers tonen -->
        <section>
            <h2>Alle kamers</h2>
            <table>
                <thead>
                    <tr>
                        <th>Kamernummer</th>
                        <th>Naam</th>
                        <th>Type</th>
                        <th>Capaciteit</th>
                        <th>Status</th>
                        <?php if ($userRole === 'admin'): ?>
                            <th>Actie</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars($room['room_number']) ?></td>
                            <td><?= htmlspecialchars($room['name']) ?></td>
                            <td><?= htmlspecialchars($room['type']) ?></td>
                            <td><?= htmlspecialchars($room['max_capacity']) ?></td>
                            <td><?= htmlspecialchars($room['status']) ?></td>
                            <?php if ($userRole === 'admin'): ?>
                                <td>
                                   
                                    <!-- Verwijder formulier -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze kamer wilt verwijderen?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <button type="submit">Verwijder</button>
                                </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>