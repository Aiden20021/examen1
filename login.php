<?php
session_start();
include 'db.php';

// Controleer of de gebruiker al ingelogd is
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Verwerk inloggegevens
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Controleer of de gebruiker bestaat
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = :username AND password = MD5(:password) AND status = 'actief'");
    $stmt->execute(['username' => $username, 'password' => $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Sessie instellen
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Ongeldige gebruikersnaam of wachtwoord.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - De Samenkomst</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <h1>De Samenkomst</h1>z
    </header>

    <main>
        <section>
            <h2>Inloggen</h2>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="username">Gebruikersnaam:</label>
                <input type="text" id="username" name="username" required><br><br>

                <label for="password">Wachtwoord:</label>
                <input type="password" id="password" name="password" required><br><br>

                <button type="submit">Inloggen</button>
            </form>
        </section>
    </main>
</body>
</html>