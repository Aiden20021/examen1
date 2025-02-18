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

    // Controleer of de kamer al bestaat met hetzelfde nummer
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Rooms WHERE room_number = :room_number");
    $stmt->execute(['room_number' => $room_number]);
    if ($stmt->fetchColumn() > 0) {
        $error_message = "Een kamer met dit kamer nummer bestaat al.";
    } else {
        // Controleer of een kamer met dezelfde gegevens maar een ander nummer al bestaat
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Rooms WHERE name = :name AND type = :type AND max_capacity = :max_capacity AND table_layout = :table_layout AND screen_type = :screen_type AND status = :status");
        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'max_capacity' => $max_capacity,
            'table_layout' => $table_layout,
            'screen_type' => $screen_type,
            'status' => $status
        ]);
        if ($stmt->fetchColumn() > 0) {
            $error_message = "Een kamer met dezelfde gegevens bestaat al.";
        } else {
            // Voeg de nieuwe kamer toe
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
    }
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
        $error_message = "Fout bij het verwijderen van de kamer: " . $e->getMessage();
    }
}

// Bewerk een kamer (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update' && $userRole === 'admin') {
    $room_id = $_POST['room_id'];
    $room_number = $_POST['room_number'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $max_capacity = $_POST['max_capacity'];
    $table_layout = $_POST['table_layout'];
    $screen_type = $_POST['screen_type'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE Rooms SET room_number = :room_number, name = :name, type = :type, max_capacity = :max_capacity,
                           table_layout = :table_layout, screen_type = :screen_type, status = :status WHERE id = :id");
    $stmt->execute([
        'id' => $room_id,
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
        .add-room-form {
            display: none;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 8px;
            text-align: center;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <script>
        // Toggle bewerk formulier binnen dezelfde rij
        function editRoom(id) {
            var row = document.getElementById('row_' + id);
            var form = document.getElementById('form_' + id);
            row.style.display = 'none';
            form.style.display = 'table-row';
        }

        // Toon het toevoegformulier
        function showAddForm() {
            var form = document.getElementById('addRoomForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Toon de modal
        window.onload = function() {
            var modal = document.getElementById('errorModal');
            if (modal) {
                modal.style.display = 'block';
            }
        }

        // Verberg de modal
        function hideModal() {
            var modal = document.getElementById('errorModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    </script>
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
        <!-- Modal voor foutmelding -->
        <?php if (isset($error_message)): ?>
            <div id="errorModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="hideModal()">&times;</span>
                    <p><?= $error_message ?></p>
                    <button onclick="hideModal()">OK</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Knop om het toevoegformulier te tonen -->
        <section>
            <button onclick="showAddForm()">Nieuwe kamer toevoegen</button>
            <div id="addRoomForm" class="add-room-form">
            <br>
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
            </div>
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
                        <tr id="row_<?= $room['id'] ?>">
                            <td><?= htmlspecialchars($room['room_number']) ?></td>
                            <td><?= htmlspecialchars($room['name']) ?></td>
                            <td><?= htmlspecialchars($room['type']) ?></td>
                            <td><?= htmlspecialchars($room['max_capacity']) ?></td>
                            <td><?= htmlspecialchars($room['status']) ?></td>
                            <?php if ($userRole === 'admin'): ?>
                                <td>
                                    <button onclick="editRoom(<?= $room['id'] ?>)">Bewerk</button>

                                    <!-- Verwijder formulier -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze kamer wilt verwijderen?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                        <button type="submit">Verwijder</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Bewerk formulier -->
                        <tr id="form_<?= $room['id'] ?>" style="display: none;">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

                                <td><input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required></td>
                                <td><input type="text" name="name" value="<?= htmlspecialchars($room['name']) ?>" required></td>
                                <td>
                                    <select name="type" required>
                                        <option value="kantoorruimte" <?= $room['type'] == 'kantoorruimte' ? 'selected' : '' ?>>Kantoorruimte</option>
                                        <option value="vergaderkamer" <?= $room['type'] == 'vergaderkamer' ? 'selected' : '' ?>>Vergaderruimte</option>
                                    </select>
                                </td>
                                <td><input type="number" name="max_capacity" value="<?= htmlspecialchars($room['max_capacity']) ?>" required></td>
                                <td><input type="text" name="table_layout" value="<?= htmlspecialchars($room['table_layout']) ?>"></td>
                                <td><input type="text" name="screen_type" value="<?= htmlspecialchars($room['screen_type']) ?>"></td>
                                <td>
                                    <select name="status" required>
                                        <option value="beschikbaar" <?= $room['status'] == 'beschikbaar' ? 'selected' : '' ?>>Beschikbaar</option>
                                        <option value="niet beschikbaar" <?= $room['status'] == 'niet beschikbaar' ? 'selected' : '' ?>>Niet beschikbaar</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit">Opslaan</button>
                                    <button type="button" onclick="document.getElementById('form_<?= $room['id'] ?>').style.display = 'none'; document.getElementById('row_<?= $room['id'] ?>').style.display = 'table-row';">Annuleren</button>
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
