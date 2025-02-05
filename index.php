<?php
session_start();
include 'db.php';

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Haal vandaagse reserveringen op
$vandaag = date('Y-m-d');
$reservations_today = $pdo->prepare("SELECT r.*, g.company_name, ro.name AS room_name FROM Reservations r 
                                    JOIN Guests g ON r.guest_id = g.id 
                                    JOIN Rooms ro ON r.room_id = ro.id 
                                    WHERE r.reservation_date = :date AND r.status = 'bevestigd'");
$reservations_today->execute(['date' => $vandaag]);
$reservations = $reservations_today->fetchAll(PDO::FETCH_ASSOC);

// Haal komende contractvervallen op
$contracts_expiring = $pdo->prepare("SELECT c.*, g.company_name, ro.name AS room_name FROM Contracts c 
                                     JOIN Guests g ON c.guest_id = g.id 
                                     JOIN Rooms ro ON c.room_id = ro.id 
                                     WHERE c.end_date <= :one_month AND c.status != 'verlopen'");
$contracts_expiring->execute(['one_month' => date('Y-m-d', strtotime('+1 month'))]);
$contracts = $contracts_expiring->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De Samenkomst - Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <h1>Welkom bij De Samenkomst</h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="rooms.php">Kamers</a>
            <a href="guests.php">Gasten</a>
            <a href="reservations.php">Reserveringen</a>
            <a href="logout.php">Uitloggen</a>
        </nav>
    </header>

    <main>
        <section>
            <h2>Vandaagse reserveringen</h2>
            <?php if (empty($reservations)): ?>
                <p>Er zijn geen reserveringen voor vandaag.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kamernummer</th>
                            <th>Gastennaam</th>
                            <th>Tijdstippen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['company_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['start_time']) ?> - <?= htmlspecialchars($reservation['end_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section>
            <h2>Komende contractvervallen</h2>
            <?php if (empty($contracts)): ?>
                <p>Er zijn geen contracts die binnenkort verlopen.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kamernummer</th>
                            <th>Bedrijfsnaam</th>
                            <th>Einddatum</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                            <tr>
                                <td><?= htmlspecialchars($contract['room_name']) ?></td>
                                <td><?= htmlspecialchars($contract['company_name']) ?></td>
                                <td><?= htmlspecialchars($contract['end_date']) ?></td>
                                <td><button onclick="extendContract(<?= $contract['id'] ?>)">Verlengen</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <script src="js/script.js"></script>
</body>
</html>