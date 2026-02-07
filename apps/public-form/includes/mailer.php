<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Check if email sending is enabled
 */
function isMailEnabled(): bool {
    return ($_ENV['MAIL_ENABLED'] ?? 'false') === 'true';
}

/**
 * Create and configure a PHPMailer instance
 */
function createMailer(): ?PHPMailer {
    if (!isMailEnabled()) {
        return null;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'localhost';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'] ?? '';
        $mail->Password = $_ENV['SMTP_PASS'] ?? '';

        $secure = $_ENV['SMTP_SECURE'] ?? 'ssl';
        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 465);

        // Default sender
        $mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@example.com',
            $_ENV['SMTP_FROM_NAME'] ?? 'CDM Media Ministry'
        );

        // Content settings
        $mail->isHTML(false);
        $mail->CharSet = 'UTF-8';

        return $mail;

    } catch (Exception $e) {
        error_log("Mailer configuration error: " . $e->getMessage());
        return null;
    }
}

/**
 * Send an email
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Plain text email body
 * @return bool True if sent successfully
 */
function sendEmail(string $to, string $subject, string $body): bool {
    $mail = createMailer();

    if ($mail === null) {
        error_log("Email not sent (disabled or config error): to=$to, subject=$subject");
        return false;
    }

    try {
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email send failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Load and render an email template
 *
 * @param string $template Template name (without .php extension)
 * @param array $data Variables to pass to the template
 * @return string Rendered template content
 */
function loadEmailTemplate(string $template, array $data): string {
    $templatePath = __DIR__ . '/../templates/emails/' . $template . '.php';

    if (!file_exists($templatePath)) {
        error_log("Email template not found: $templatePath");
        return '';
    }

    // Extract variables for the template
    extract($data);

    // Capture output
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

/**
 * Send confirmation email to requestor after submission
 */
function sendConfirmationEmail(array $request, array $services): bool {
    $body = loadEmailTemplate('confirmation', [
        'requestor_name' => $request['requestor_name'],
        'reference_no' => $request['reference_no'],
        'event_name' => $request['event_name'],
        'event_dates' => $request['event_dates'] ?? '',
        'services' => $services,
    ]);

    return sendEmail(
        $request['email'],
        "Request Received - {$request['reference_no']}",
        $body
    );
}

/**
 * Send approval email to requestor
 */
function sendApprovalEmail(array $request): bool {
    $body = loadEmailTemplate('approved', [
        'requestor_name' => $request['requestor_name'],
        'reference_no' => $request['reference_no'],
        'event_name' => $request['event_name'],
        'event_dates' => $request['event_dates'] ?? '',
    ]);

    return sendEmail(
        $request['email'],
        "Request Approved - {$request['reference_no']}",
        $body
    );
}

/**
 * Send rejection email to requestor
 */
function sendRejectionEmail(array $request, string $reason): bool {
    $body = loadEmailTemplate('rejected', [
        'requestor_name' => $request['requestor_name'],
        'reference_no' => $request['reference_no'],
        'event_name' => $request['event_name'],
        'rejection_reason' => $reason,
    ]);

    return sendEmail(
        $request['email'],
        "Request Update - {$request['reference_no']}",
        $body
    );
}
