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
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #8A9A5B; 
            color: #3D2B1F; 
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            animation: fadeIn 1.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
            animation: slideInDown 1s ease-out;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        header h1 {
            color: #F4D03F; 
            font-size: 2.5rem;
        }

        main {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            animation: scaleUp 1s ease-out;
        }

        @keyframes scaleUp {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        h2 {
            color: #3D2B1F; 
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 0.5rem;
            color: #3D2B1F; 
        }

        input {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            transition: border-color 0.3s ease;
        }

        input:focus {
            border-color: #F4D03F; 
            outline: none;
        }

        button {
            padding: 0.75rem;
            background-color: #F4D03F; 
            color: #3D2B1F; 
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        button:hover {
            background-color: #DAA520; 
            transform: scale(1.05);
        }

        p {
            margin-bottom: 1rem;
            text-align: center;
        }

        nav a {
            margin: 0 1rem;
            color: white;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 0.5rem;
            text-align: left;
        }

        table th {
            background-color: #4CAF50 !important;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <h1>De Samenkomst</h1>
    </header>

    <main>
        <section>
            <h2>Inloggen</h2>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="username">Gebruikersnaam:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Wachtwoord:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Inloggen</button>
            </form>
        </section>
    </main>
</body>
</html>
