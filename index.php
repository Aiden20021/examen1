RVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_end_date') {
    $reservation_id = $_POST['reservation_id'];
    $new_end_date = $_POST['new_end_date'];

    if (updateReservationAndContract($pdo, $reservation_id, $new_end_date)) {
        echo "<script>alert('Einddatum succesvol bijgewerkt!');</script>";
    } else {
        echo "<script>alert('Fout: Kon de einddatum niet bijwerken.');</script>";
    }

    // Vernieuw de pagina
    header("Refresh:0");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De Samenkomst - Dashboard</title>
    <style>
       body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4; /* Lichtgrijs achtergrond */
    }
    header {
        background-color: #6B8E23; /* Mosgroen */
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
        color: #8B4513; /* Baksteenbruin */
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
        background-color: #6B8E23; /* Mosgroen */
        color: white;
    }
    button {
        background-color: #8B4513; /* Baksteenbruin */
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 5px;
    }
    button:hover {
        background-color: #A0522D; /* Donkerder baksteenbruin */
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
            <h2>Actieve Reserveringen voor Vergaderkamers</h2>
            <?php if (empty($meeting_rooms)): ?>
                <p>Er zijn geen actieve reserveringen voor vergaderkamers.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kamernummer</th>
                            <th>Gastennaam</th>
                            <th>Datum</th>
                            <th>Tijdstippen</th>
                            <th>Kamertype</th>
                            <th>Einddatum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meeting_rooms as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['company_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['reservation_date']) ?></td>
                                <td>
                                    <?= htmlspecialchars($reservation['start_time']) ?> - <?= htmlspecialchars($reservation['end_time']) ?>
                                </td>
                                <td><?= htmlspecialchars($reservation['room_type']) ?></td>
                                <td>
                                    <!-- Formulier om de einddatum te bewerken -->
                                    <form method="POST" style="display: flex; gap: 10px;">
                                        <input type="hidden" name="action" value="update_end_date">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <input type="date" name="new_end_date" value="<?= htmlspecialchars($reservation['end_date']) ?>" required>
                                        <button type="submit" class="extend-button">Opslaan Verlengen</button>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section>
            <h2>Actieve Reserveringen voor Kantoorruimtes</h2>
            <?php if (empty($office_spaces)): ?>
                <p>Er zijn geen actieve reserveringen voor kantoorruimtes.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kamernummer</th>
                            <th>Gastennaam</th>
                            <th>Datum</th>
                            <th>Tijdstippen</th>
                            <th>Kamertype</th>
                            <th>Einddatum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($office_spaces as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['company_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['reservation_date']) ?></td>
                                <td>
                                    Gehele periode
                                </td>
                                <td><?= htmlspecialchars($reservation['room_type']) ?></td>
                                <td>
                                    <!-- Formulier om de einddatum te bewerken -->
                                    <form method="POST" style="display: flex; gap: 10px;">
                                        <input type="hidden" name="action" value="update_end_date">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <input type="date" name="new_end_date" value="<?= htmlspecialchars($reservation['end_date']) ?>" required>
                                        <button type="submit" class="extend-button">Opslaan Verlengen</button>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>