<?php
/**
 * Simple Brevo SMTP Email Sender
 */

function sendEmailViaSMTP($toName, $subject, $message) {
    require_once __DIR__ . '/config.php';
    
    $from = FROM_MAIL;
    $fromName = FROM_NAME;
    $to = TO_MAIL;
    
    try {
        $sock = fsockopen(BREVO_SMTP_HOST, BREVO_SMTP_PORT, $errno, $errstr, 30);
        if (!$sock) throw new Exception("Connection failed: $errstr");
        
        stream_set_blocking($sock, true);
        stream_set_timeout($sock, 30);
        
        $read = function() use ($sock) { return fgets($sock, 1024); };
        $write = function($msg) use ($sock) { fputs($sock, $msg . "\r\n"); };
        
        // Server greeting
        $read();
        
        // EHLO
        $write("EHLO nestystay.net");
        while (substr($read(), 3, 1) === '-');
        
        // STARTTLS
        $write("STARTTLS");
        $read();
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // EHLO after TLS
        $write("EHLO nestystay.net");
        while (substr($read(), 3, 1) === '-');
        
        // AUTH CRAM-MD5
        $write("AUTH CRAM-MD5");
        $resp = $read();
        
        if (strpos($resp, '334') !== false) {
            $challenge = base64_decode(trim(substr($resp, 4)));
            $hmac = hash_hmac('md5', $challenge, BREVO_SMTP_PASSWORD, true);
            $auth = base64_encode(BREVO_SMTP_USERNAME . ' ' . bin2hex($hmac));
            $write($auth);
            $read();
        }
        
        // Send email
        $write("MAIL FROM:<$from>");
        $read();
        
        $write("RCPT TO:<$to>");
        $read();
        
        $write("DATA");
        $read();
        
        $headers = "From: $fromName <$from>\r\n";
        $headers .= "To: $toName <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        fputs($sock, $headers . "\r\n" . $message . "\r\n.\r\n");
        $read();
        
        $write("QUIT");
        fclose($sock);
        
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
