
<?php
require "db.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "field" => "formMessage",
        "message" => "Invalid request."
    ]);
    exit;
}

$name = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm_password"] ?? "";
$role = $_POST["role"] ?? "";

if ($name === "" || strlen($name) > 100) {
    $field = "nameError";
    $message = "Enter a valid name.";

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    $field = "emailError";
    $message = "Enter a valid email address.";

} elseif (strlen($password) < 8) {
    $field = "passwordError";
    $message = "Password must contain at least 8 characters.";

} elseif ($password !== $confirm) {
    $field = "confirmError";
    $message = "Passwords do not match.";

} elseif (!in_array($role, ["citizen", "provider"], true)) {
    $field = "roleError";
    $message = "Choose a valid account type.";

} else {
    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            echo json_encode([
                "success" => false,
                "field" => "emailError",
                "message" => "This email is already registered."
            ]);
            exit;
        }

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare(
            "INSERT INTO users
            (full_name, email, password, role)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $name,
            $email,
            $hashedPassword,
            $role
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Registration successful. You can log in."
        ]);
        exit;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);

        echo json_encode([
        "success" => false,
        "field" => "formMessage",
        "message" => "Registration failed. Please try again."
        
        ]);
        exit;
    }
}

echo json_encode([
    "success" => false,
    "field" => $field,
    "message" => $message
]);
