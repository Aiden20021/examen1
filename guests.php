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

// Haal alle gasten op
$stmt = $pdo->query("SELECT * FROM Guests WHERE status = 'actief'");
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Functie om te controleren of een gast al bestaat
function guestExists($pdo, $email, $phone) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Guests WHERE email = :email OR phone = :phone");
    $stmt->execute(['email' => $email, 'phone' => $phone]);
    return $stmt->fetchColumn() > 0;
}

// Voeg een nieuwe gast toe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Controleer of de gast al bestaat
    if (guestExists($pdo, $email, $phone)) {
        $error_message = "Een gast met dit emailadres of telefoonnummer bestaat al.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO Guests (company_name, contact_person, email, phone, status)
                               VALUES (:company_name, :contact_person, :email, :phone, 'actief')");
        $stmt->execute([
            'company_name' => $company_name,
            'contact_person' => $contact_person,
            'email' => $email,
            'phone' => $phone
        ]);

        header("Location: guests.php");
        exit;
    }
}

// Verwijder een gast (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete' && $userRole === 'admin') {
    $guest_id = $_POST['guest_id'];
    $stmt = $pdo->prepare("UPDATE Guests SET status = 'verwijderd' WHERE id = :id");
    $stmt->execute(['id' => $guest_id]);

    header("Location: guests.php");
    exit;
}

// Bewerk een gast
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update') {
    $guest_id = $_POST['guest_id'];
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $pdo->prepare("UPDATE Guests SET company_name = :company_name, contact_person = :contact_person,
                           email = :email, phone = :phone WHERE id = :id");
    $stmt->execute([
        'id' => $guest_id,
        'company_name' => $company_name,
        'contact_person' => $contact_person,
        'email' => $email,
        'phone' => $phone
    ]);

    header("Location: guests.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De Samenkomst - Gasten</title>
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
        .add-guest-form {
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
        function editGuest(id) {
            var row = document.getElementById('row_' + id);
            var form = document.getElementById('form_' + id);
            row.style.display = 'none';
            form.style.display = 'table-row';
        }

        // Toon het toevoegformulier
        function showAddForm() {
            var form = document.getElementById('addGuestForm');
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
        <h1>De Samenkomst - Gasten</h1>
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
            <button onclick="showAddForm()">Nieuwe gast toevoegen</button>
            <div id="addGuestForm" class="add-guest-form">
            <br>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <label for="company_name">Bedrijfsnaam:</label>
                    <input type="text" id="company_name" name="company_name" required><br><br>

                    <label for="contact_person">Contactpersoon:</label>
                    <input type="text" id="contact_person" name="contact_person" required><br><br>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required><br><br>

                    <label for="phone">Telefoonnummer:</label>
                    <input type="text" id="phone" name="phone" required><br><br>

                    <button type="submit">Toevoegen</button>
                </form>
            </div>
        </section>

        <!-- Alle gasten tonen -->
        <section>
            <h2>Alle gasten</h2>
            <table>
                <thead>
                    <tr>
                        <th>Bedrijfsnaam</th>
                        <th>Contactpersoon</th>
                        <th>Email</th>
                        <th>Telefoon</th>
                        <?php if ($userRole === 'admin'): ?>
                            <th>Actie</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guests as $guest): ?>
                        <!-- Rijen voor elke gast -->
                        <tr id="row_<?= $guest['id'] ?>">
                            <td><?= htmlspecialchars($guest['company_name']) ?></td>
                            <td><?= htmlspecialchars($guest['contact_person']) ?></td>
                            <td><?= htmlspecialchars($guest['email']) ?></td>
                            <td><?= htmlspecialchars($guest['phone']) ?></td>
                            <?php if ($userRole === 'admin'): ?>
                                <td>
                                    <!-- Bewerk knop -->
                                    <button onclick="editGuest(<?= $guest['id'] ?>)">Bewerk</button>

                                    <!-- Verwijder formulier -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze gast wilt verwijderen?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="guest_id" value="<?= $guest['id'] ?>">
                                        <button type="submit">Verwijder</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Bewerk formulier -->
                        <tr id="form_<?= $guest['id'] ?>" style="display: none;">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="guest_id" value="<?= $guest['id'] ?>">

                                <td><input type="text" name="company_name" value="<?= htmlspecialchars($guest['company_name']) ?>" required></td>
                                <td><input type="text" name="contact_person" value="<?= htmlspecialchars($guest['contact_person']) ?>" required></td>
                                <td><input type="email" name="email" value="<?= htmlspecialchars($guest['email']) ?>" required></td>
                                <td><input type="text" name="phone" value="<?= htmlspecialchars($guest['phone']) ?>" required></td>

                                <td>
                                    <button type="submit">Opslaan</button>
                                    <button type="button" onclick="document.getElementById('form_<?= $guest['id'] ?>').style.display = 'none'; document.getElementById('row_<?= $guest['id'] ?>').style.display = 'table-row';">Annuleren</button>
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
