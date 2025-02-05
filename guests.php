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

// Voeg een nieuwe gast toe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

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

// Verwijder een gast (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete' && $userRole === 'admin') {
    $guest_id = $_POST['guest_id'];
    $stmt = $pdo->prepare("UPDATE Guests SET status = 'verwijderd' WHERE id = :id");
    $stmt->execute(['id' => $guest_id]);

    header("Location: guests.php");
    exit;
}

// Bewerk een gast (alleen Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update' && $userRole === 'admin') {
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De Samenkomst - Gasten</title>
    <link rel="stylesheet" href="css/styles.css">
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
        <!-- Nieuwe gast toevoegen -->
        <section>
            <h2>Nieuwe gast toevoegen</h2>
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
                        <tr>
                            <td><?= htmlspecialchars($guest['company_name']) ?></td>
                            <td><?= htmlspecialchars($guest['contact_person']) ?></td>
                            <td><?= htmlspecialchars($guest['email']) ?></td>
                            <td><?= htmlspecialchars($guest['phone']) ?></td>
                            <?php if ($userRole === 'admin'): ?>
                                <td>
                                    <!-- Bewerk formulier -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="guest_id" value="<?= $guest['id'] ?>">
                                        <input type="hidden" name="company_name" value="<?= $guest['company_name'] ?>">
                                        <input type="hidden" name="contact_person" value="<?= $guest['contact_person'] ?>">
                                        <input type="hidden" name="email" value="<?= $guest['email'] ?>">
                                        <input type="hidden" name="phone" value="<?= $guest['phone'] ?>">
                                        <button type="submit">Bewerk</button>
                                    </form>

                                    <!-- Verwijder formulier -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze gast wilt verwijderen?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="guest_id" value="<?= $guest['id'] ?>">
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