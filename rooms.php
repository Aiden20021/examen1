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
$stmt = $pdo->query("SELECT * FROM Rooms WHERE deleted = 'nee'");
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

// Soft delete geselecteerde kamers (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete-selected' && $userRole === 'admin') {
    $selected_rooms = $_POST['selected_rooms'] ?? [];

    if (!empty($selected_rooms)) {
        try {
            $placeholders = implode(',', array_fill(0, count($selected_rooms), '?'));
            $query = "UPDATE Rooms SET status = 'niet beschikbaar', deleted = 'ja' WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array_map('intval', $selected_rooms));
        } catch (PDOException $e) {
            echo "Fout bij het verwijderen van de kamers: " . $e->getMessage();
        }
    }

    header("Location: rooms.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De Samenkomst - Kamers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .hidden {
            display: none;
        }
    </style>
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
            <form method="POST" action="" id="delete-form">
                <input type="hidden" name="action" value="delete-selected">
                <?php if ($userRole === 'admin'): ?>
                    <button type="button" id="toggle-checkboxes">Verwijder</button>
                    <button type="submit" id="confirm-delete" class="hidden" onclick="return confirm('Weet je zeker dat je de geselecteerde kamers wilt verwijderen?')">Bevestig verwijderen</button>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <?php if ($userRole === 'admin'): ?>
                                <th class="hidden">Selecteer</th>
                            <?php endif; ?>
                            <th>Kamernummer</th>
                            <th>Naam</th>
                            <th>Type</th>
                            <th>Capaciteit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <?php if ($userRole === 'admin'): ?>
                                    <td class="hidden"><input type="checkbox" name="selected_rooms[]" value="<?= $room['id'] ?>"></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($room['room_number']) ?></td>
                                <td><?= htmlspecialchars($room['name']) ?></td>
                                <td><?= htmlspecialchars($room['type']) ?></td>
                                <td><?= htmlspecialchars($room['max_capacity']) ?></td>
                                <td><?= htmlspecialchars($room['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </section>

        <script>
            // Toon/verberg checkboxes en bevestigingsknop
            document.getElementById('toggle-checkboxes').addEventListener('click', function () {
                const checkboxes = document.querySelectorAll('#delete-form td.hidden');
                const confirmButton = document.getElementById('confirm-delete');

                checkboxes.forEach(function (checkbox) {
                    checkbox.classList.toggle('hidden');
                });

                confirmButton.classList.toggle('hidden');
                this.style.display = 'none'; // Verberg de "Verwijder"-knop
            });

            // Controleer of er kamers zijn geselecteerd voordat het formulier wordt verzonden
            document.getElementById('delete-form').addEventListener('submit', function (event) {
                const selectedCheckboxes = document.querySelectorAll('#delete-form input[type="checkbox"]:checked');

                if (selectedCheckboxes.length === 0) {
                    event.preventDefault(); // Voorkom het verzenden van het formulier
                    alert('Selecteer ten minste één kamer om te verwijderen.');
                }
            });
        </script>
    </main>
</body>
</html>