<?php
// Direct form submission test
require_once 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;

// Load .env
$dotenv = new \Symfony\Component\DotEnv\DotEnv();
$dotenv->bootEnv(__DIR__ . '/.env');
// Parse .env manually
$envFile = __DIR__ . '/.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$dsn = null;
foreach ($lines as $line) {
    if (strpos($line, 'MAILER_DSN=') === 0) {
        $dsn = substr($line, 11);
        break;
    }
}
if (!$dsn) {
    exit(1);
}

echo "🧪 Testing direct email send to carpet form recipient...\n\n";

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from(new Address('carpediemcafe6@gmail.com', 'Carpe Diem Contact Form'))
        ->replyTo('tester@example.com')
        ->to('carpediemcafe6@gmail.com')
        ->subject('[Contact] Test Form Submission')
        ->html(sprintf(
            '<h2>New Contact Inquiry</h2><p><strong>Name:</strong> %s</p><p><strong>Email:</strong> %s</p><p><strong>Subject:</strong> %s</p><p><strong>Message:</strong><br>%s</p>',
            htmlspecialchars('Test User', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars('tester@example.com', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars('Test Subject', ENT_QUOTES, 'UTF-8'),
            nl2br(htmlspecialchars('This is a test email from PHP directly', ENT_QUOTES, 'UTF-8'))
        ));
    
    echo "📧 Sending test email...\n";
    $mailer->send($email);
    echo "✅ Email sent successfully via Brevo!\n";
    echo "\n📝 If this email was sent, check your Gmail spam folder.\n";
    echo "💡 If not received, there may be an authentication issue with Brevo.\n";
    
} catch (\Throwable $e) {
    echo "❌ Email send failed: " . $e->getMessage() . "\n";
    echo "Exception Type: " . get_class($e) . "\n";
    if ($e->getPrevious()) {
        echo "Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
}
?>
