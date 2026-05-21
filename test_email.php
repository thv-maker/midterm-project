<?php
// Simple email test - check .env and SMTP connection

$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    echo "❌ .env file not found\n";
    exit(1);
}

// Parse .env manually
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$mailerDsn = null;

foreach ($lines as $line) {
    if (strpos($line, 'MAILER_DSN=') === 0) {
        $mailerDsn = substr($line, 11);
        break;
    }
}

if (!$mailerDsn) {
    echo "❌ MAILER_DSN not found in .env\n";
    exit(1);
}

echo "📧 MAILER_DSN found: " . substr($mailerDsn, 0, 40) . "...\n";
echo "🔗 Testing Brevo SMTP connection...\n\n";

// Test basic connection
$host = 'smtp-relay.brevo.com';
$port = 587;

echo "Attempting connection to $host:$port...\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 5);

if ($socket) {
    echo "✅ TCP connection successful!\n";
    fclose($socket);
    echo "\n📝 SMTP server is reachable.\n";
} else {
    echo "❌ Connection failed: $errstr (errno: $errno)\n";
    echo "This may indicate network/firewall issues.\n";
}
?>

