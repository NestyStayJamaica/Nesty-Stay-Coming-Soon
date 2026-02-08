<?php
header('Content-Type: application/json');

/* =========================
   LOAD ENV CONFIGURATION
========================= */
require_once __DIR__ . '/config.php';

/* =========================
   BASIC SECURITY & METHOD
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
    exit;
}

/* =========================
   SANITIZE INPUT
========================= */
function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$name     = clean($_POST['name']);
$email    = clean($_POST['email']);
$whatsapp = clean($_POST['whatsapp']);
$location = clean($_POST['location']);
$category = clean($_POST['category']);
$tier     = clean($_POST['tier']);

/* =========================
   VALIDATION
========================= */
if (!$name || !$email || !$whatsapp || !$location || !$category || !$tier) {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required."
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email address."
    ]);
    exit;
}

if (!in_array($tier, ['Platinum', 'Gold'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid founder tier."
    ]);
    exit;
}

/* =========================
   LOAD TRACKER
========================= */
$trackerFile = 'tracker.json';

if (!file_exists($trackerFile)) {
    echo json_encode([
        "status" => "error",
        "message" => "Tracker file missing."
    ]);
    exit;
}

$tracker = json_decode(file_get_contents($trackerFile), true);

if (!$tracker) {
    echo json_encode([
        "status" => "error",
        "message" => "Tracker file corrupted."
    ]);
    exit;
}

/* =========================
   CHECK AVAILABILITY
========================= */
if ($tier === "Platinum" && $tracker['platinum_filled'] >= $tracker['platinum_total']) {
    echo json_encode([
        "status" => "error",
        "message" => "Platinum Founding Membership is fully booked."
    ]);
    exit;
}

if ($tier === "Gold" && $tracker['gold_filled'] >= $tracker['gold_total']) {
    echo json_encode([
        "status" => "error",
        "message" => "Gold Founding Membership is fully booked."
    ]);
    exit;
}

/* =========================
   SEND EMAIL VIA BREVO SMTP
========================= */
require_once __DIR__ . '/smtp-sender.php';

$subject = "New $tier Founder Registration";

$message = "NEW FOUNDER REGISTRATION\n\n" .
           "Name: $name\n" .
           "Email: $email\n" .
           "WhatsApp: $whatsapp\n" .
           "Location: $location\n" .
           "Category: $category\n" .
           "Tier: $tier";

$emailResult = sendEmailViaSMTP($toName = "New Founder", $subject, $message);

if (!$emailResult['success']) {
    echo json_encode([
        "status" => "error",
        "message" => "Unable to send email. Please try again later.",
        "debug" => $emailResult['error'] ?? 'Unknown error'
    ]);
    exit;
}

/* =========================
   UPDATE TRACKER (ATOMIC)
========================= */
if ($tier === "Platinum") {
    $tracker['platinum_filled']++;
}

if ($tier === "Gold") {
    $tracker['gold_filled']++;
}

file_put_contents($trackerFile, json_encode($tracker, JSON_PRETTY_PRINT));

/* =========================
   SUCCESS RESPONSE
========================= */
echo json_encode([
    "status" => "success",
    "message" => "Thank you! Your $tier founding membership has been successfully registered."
]);
