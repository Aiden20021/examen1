<?php
session_start();
include 'db.php';

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd.']);
    exit;
}

// Controleer of het contract ID is ontvangen
if (!isset($_POST['contractId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ongeldig verzoek.']);
    exit;
}

$contractId = $_POST['contractId'];

// Verleng het contract met een maand
$newEndDate = date('Y-m-d', strtotime('+1 month'));
$updateContract = $pdo->prepare("UPDATE Contracts SET end_date = :newEndDate WHERE id = :contractId");
$updateContract->execute(['newEndDate' => $newEndDate, 'contractId' => $contractId]);

if ($updateContract->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Contract succesvol verlengd.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Kon het contract niet verlengen.']);
}
?>
