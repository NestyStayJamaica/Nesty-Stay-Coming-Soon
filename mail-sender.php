<?php
/**
 * Simple Brevo REST API Email Sender
 */

function sendEmailViaBrevoApi($toName, $subject, $message) {
    require_once __DIR__ . '/config.php';
    
    $from = FROM_MAIL;
    $fromName = FROM_NAME;
    $apiKey = BREVO_API_KEY;
    $to = TO_MAIL;
    
    $emailData = [
        'sender' => [
            'name' => $fromName,
            'email' => $from
        ],
        'to' => [
            [
                'name' => $toName,
                'email' => $to
            ]
        ],
        'subject' => $subject,
        'textContent' => $message
    ];
    
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    if ($httpCode === 201) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        $data = json_decode($response, true);
        return ['success' => false, 'error' => $data['message'] ?? $response];
    }
}
?>
