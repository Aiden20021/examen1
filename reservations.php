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
    $end_date = $_POST['end_date'];

    $stmt = $pdo->prepare("INSERT INTO Reservations (guest_id, room_id, reservation_date, start_time, end_time, end_date, status)
                           VALUES (:guest_id, :room_id, :reservation_date, :start_time, :end_time, :end_date, 'bevestigd')");
    $stmt->execute([
        'guest_id' => $guest_id,
        'room_id' => $room_id,
        'reservation_date' => $reservation_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'end_date' => $end_date
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
    $end_date = $_POST['end_date'];

    $stmt = $pdo->prepare("UPDATE Reservations SET guest_id = :guest_id, room_id = :room_id, reservation_date = :reservation_date,
                           start_time = :start_time, end_time = :end_time, end_date = :end_date WHERE id = :id");
    $stmt->execute([
        'id' => $reservation_id,
        'guest_id' => $guest_id,
        'room_id' => $room_id,
        'reservation_date' => $reservation_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'end_date' => $end_date
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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 1em 0;
            text-align: center;
        }
        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
        }
        main {
            padding: 20px;
        }
        section {
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #45a049;
        }
        .add-reservation-form {
            display: none;
        }
    </style>
    <script>
        // Toggle bewerk formulier binnen dezelfde rij
        function editReservation(id) {
            var row = document.getElementById('row_' + id);
            var form = document.getElementById('form_' + id);
            row.style.display = 'none';
            form.style.display = 'table-row';
        }

        // Toon het toevoegformulier
        function showAddForm() {
            var form = document.getElementById('addReservationForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
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
        <!-- Knop om het toevoegformulier te tonen -->
        <section>
            <button onclick="showAddForm()">Nieuwe reservering toevoegen</button>
            <div id="addReservationForm" class="add-reservation-form">
                <br>
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

                    <label for="end_date">Einddatum:</label>
                    <input type="date" id="end_date" name="end_date" required><br><br>

                    <label for="start_time">Starttijd:</label>
                    <input type="time" id="start_time" name="start_time" required><br><br>

                    <label for="end_time">Eindtijd:</label>
                    <input type="time" id="end_time" name="end_time" required><br><br>

                    <button type="submit">Toevoegen</button>
                </form>
            </div>
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
                        <th>Einddatum</th>
                        <?php if ($userRole === 'admin'): ?>
                            <th>Actie</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr id="row_<?= $reservation['id'] ?>">
                            <td><?= htmlspecialchars($reservation['company_name']) ?></td>
                            <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                            <td><?= htmlspecialchars($reservation['reservation_date']) ?></td>
                            <td><?= htmlspecialchars($reservation['start_time']) ?> - <?= htmlspecialchars($reservation['end_time']) ?></td>
                            <td><?= htmlspecialchars($reservation['end_date']) ?></td>
                            <?php if ($userRole === 'admin'): ?>
                                <td>
                                    <button onclick="editReservation(<?= $reservation['id'] ?>)">Bewerk</button>

                                    <!-- Verwijder formulier -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze reservering wilt annuleren?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <button type="submit">Annuleren</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Bewerk formulier -->
                        <tr id="form_<?= $reservation['id'] ?>" style="display: none;">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">

                                <td>
                                    <select name="guest_id" required>
                                        <?php
                                        $stmt = $pdo->query("SELECT id, company_name FROM Guests WHERE status = 'actief'");
                                        $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($guests as $guest): ?>
                                            <option value="<?= $guest['id'] ?>" <?= $reservation['guest_id'] == $guest['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($guest['company_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <select name="room_id" required>
                                        <?php
                                        $stmt = $pdo->query("SELECT id, name FROM Rooms WHERE status = 'beschikbaar'");
                                        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($rooms as $room): ?>
                                            <option value="<?= $room['id'] ?>" <?= $reservation['room_id'] == $room['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($room['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td><input type="date" name="reservation_date" value="<?= htmlspecialchars($reservation['reservation_date']) ?>" required></td>
                                <td>
                                    <input type="time" name="start_time" value="<?= htmlspecialchars($reservation['start_time']) ?>" required>
                                    <input type="time" name="end_time" value="<?= htmlspecialchars($reservation['end_time']) ?>" required>
                                </td>
                                <td><input type="date" name="end_date" value="<?= htmlspecialchars($reservation['end_date']) ?>" required></td>

                                <td>
                                    <button type="submit">Opslaan</button>
                                    <button type="button" onclick="document.getElementById('form_<?= $reservation['id'] ?>').style.display = 'none'; document.getElementById('row_<?= $reservation['id'] ?>').style.display = 'table-row';">Annuleren</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
