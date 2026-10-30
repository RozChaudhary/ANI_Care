
<?php

declare(strict_types=1);

session_start();

header("Content-Type: application/json; charset=UTF-8");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "db.php";
require "vendor/autoload.php";

function respond(string $message): void
{
    echo json_encode([
        "message" => $message
    ]);
    exit;
}

$genericMessage = "If the email is registered, a reset link will be sent shortly.";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit;
}

$email = trim($_POST["email"] ?? "");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);
}

$config = require "mail_config.php";

try {
    $stmt = $pdo->prepare(
        "SELECT id, email FROM users WHERE email = :email LIMIT 1"
    );

    $stmt->execute(["email" => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => true,
            "message" => $genericMessage
        ]);
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);

    $pdo->beginTransaction();

    $delete = $pdo->prepare(
        "DELETE FROM password_resets WHERE user_id = :user_id"
    );

    $delete->execute(["user_id" => $user["id"]]);

    $insert = $pdo->prepare(
        "INSERT INTO password_resets
        (user_id, token_hash, expires_at)
        VALUES
        (:user_id, :token_hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE))"
    );

    $insert->execute([
        "user_id" => $user["id"],
        "token_hash" => $tokenHash
    ]);

    $pdo->commit();

    $resetUrl = rtrim($config["base_url"], "/")
        . "/reset_password.php?token="
        . urlencode($token);

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = $config["username"];
    $mail->Password = $config["app_password"];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->CharSet = "UTF-8";
    $mail->setFrom($config["username"], "ANI Care");
    $mail->addAddress($user["email"]);

    $mail->isHTML(true);
    $mail->Subject = "ANI Care Password Reset";

    $safeUrl = htmlspecialchars(
        $resetUrl,
        ENT_QUOTES,
        "UTF-8"
    );

    $mail->Body = "
        <h2>ANI Care Password Reset</h2>
        <p>We received a request to reset your password.</p>
        <p><a href=\"{$safeUrl}\">Reset My Password</a></p>
        <p>This link expires in 30 minutes and can only be used once.</p>
        <p>If you did not request this, you can ignore this email.</p>
    ";

    $mail->AltBody =
        "Reset your ANI Care password using this link: "
        . $resetUrl
        . "\nThis link expires in 30 minutes.";

    $mail->send();

    echo json_encode([
        "success" => true,
        "message" => $genericMessage
    ]);
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Password reset request failed: " . $e->getMessage());

    if (isset($tokenHash)) {
        try {
            $cleanup = $pdo->prepare(
                "DELETE FROM password_resets WHERE token_hash = :token_hash"
            );

            $cleanup->execute(["token_hash" => $tokenHash]);

        } catch (Throwable $cleanupError) {

            error_log(
                "Password reset cleanup failed: "
                . $cleanupError->getMessage()
            );
        }
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "We could not process your request right now. Please try again later."
    ]);
    exit;
}

?>
