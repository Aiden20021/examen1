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

// Haal alle reserveringen op
$stmt = $pdo->query("SELECT r.*, g.company_name, ro.name AS room_name FROM Reservations r 
                     JOIN Guests g ON r.guest_id = g.id 
                     JOIN Rooms ro ON r.room_id = ro.id 
                     WHERE r.status = 'bevestigd'");
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Voeg een nieuwe reservering toe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $guest_id = $_POST['guest_id'];
    $room_id = $_POST['room_id'];
    $reservation_date = $_POST['reservation_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $pdo->prepare("INSERT INTO Reservations (guest_id, room_id, reservation_date, start_time, end_time, status) 
                           VALUES (:guest_id, :room_id, :reservation_date, :start_time, :end_time, 'bevestigd')");
    $stmt->execute([
        'guest_id' => $guest_id,
        'room_id' => $room_id,
        'reservation_date' => $reservation_date,
        'start_time' => $start_time,
        'end_time' => $end_time
    ]);

    header("Location: reservations.php");
    exit;
}

// Verwijder een reservering (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete' && $userRole === 'admin') {
    $reservation_id = $_POST['reservation_id'];
    $stmt = $pdo->prepare("UPDATE Reservations SET status = 'geannuleerd' WHERE id = :id");
    $stmt->execute(['id' => $reservation_id]);

    header("Location: reservations.php");
    exit;
}

// Bewerk een reservering (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update' && $userRole === 'admin') {
    $reservation_id = $_POST['reservation_id'];
    $guest_id = $_POST['guest_id'];
    $room_id = $_POST['room_id'];
    $reservation_date = $_POST['reservation_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $pdo->prepare("UPDATE Reservations SET guest_id = :guest_id, room_id = :room_id, reservation_date = :reservation_date, 
                           start_time = :start_time, end_time = :end_time WHERE id = :id");
    $stmt->execute([
        'id' => $reservation_id,
        'guest_id' => $guest_id,
        'room_id' => $room_id,
        'reservation_date' => $reservation_date,
        'start_time' => $start_time,
        'end_time' => $end_time
    ]);

    header("Location: reservations.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De Samenkomst - Reserveringen</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <h1>De Samenkomst - Reserveringen</h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="rooms.php">Kamers</a>
            <a href="guests.php">Gasten</a>
            <a href="reservations.php">Reserveringen</a>
            <a href="logout.php">Uitloggen</a>
        </nav>
    </header>

    <main>
        <!-- Nieuwe reservering toevoegen -->
        <section>
            <h2>Nieuwe reservering toevoegen</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <label for="guest_id">Gast:</label>
                <select id="guest_id" name="guest_id" required>
                    <?php
                    $stmt = $pdo->query("SELECT id, company_name FROM Guests WHERE status = 'actief'");
                    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($guests as $guest): ?>
                        <option value="<?= $guest['id'] ?>"><?= htmlspecialchars($guest['company_name']) ?></option>
                    <?php endforeach; ?>
                </select><br><br>

                <label for="room_id">Kamer:</label>
                <select id="room_id" name="room_id" required>
                    <?php
                    $stmt = $pdo->query("SELECT id, name FROM Rooms WHERE status = 'beschikbaar'");
                    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></option>
                    <?php endforeach; ?>
                </select><br><br>

                <label for="reservation_date">Datum:</label>
                <input type="date" id="reservation_date" name="reservation_date" required><br><br>

                <label for="start_time">Starttijd:</label>
                <input type="time" id="start_time" name="start_time" required><br><br>

                <label for="end_time">Eindtijd:</label>
                <input type="time" id="end_time" name="end_time" required><br><br>

                <button type="submit">Toevoegen</button>
            </form>
        </section>

        <!-- Alle reserveringen tonen -->
        <section>
            <h2>Alle reserveringen</h2>
            <table>
                <thead>
                    <tr>
                        <th>Gastennaam</th>
                        <th>Kamernaam</th>
                        <th>Datum</th>
                        <th>Tijdstippen</th>
                        <?php if ($userRole === 'admin'): ?>
                            <th>Actie</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['company_name']) ?></td>
                            <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                            <td><?= htmlspecialchars($reservation['reservation_date']) ?></td>
                            <td><?= htmlspecialchars($reservation['start_time']) ?> - <?= htmlspecialchars($reservation['end_time']) ?></td>
                            <?php if ($userRole === 'admin'): ?>
                                <td>
                                    <!-- Bewerk formulier -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <input type="hidden" name="guest_id" value="<?= $reservation['guest_id'] ?>">
                                        <input type="hidden" name="room_id" value="<?= $reservation['room_id'] ?>">
                                        <input type="hidden" name="reservation_date" value="<?= $reservation['reservation_date'] ?>">
                                        <input type="hidden" name="start_time" value="<?= $reservation['start_time'] ?>">
                                        <input type="hidden" name="end_time" value="<?= $reservation['end_time'] ?>">
                                        <button type="submit">Bewerk</button>
                                    </form>

                                    <!-- Verwijder formulier -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze reservering wilt annuleren?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <button type="submit">Annuleren</button>
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