
<?php
session_start();

header("Content-Type: application/json");

require "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Please enter your email and password."
    ]);

    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, password, role
        FROM users
        WHERE email = ?"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user["password"])) {
        echo json_encode([
            "success" => false,
            "message" => "Incorrect email or password."
        ]);

        exit;
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["full_name"] = $user["full_name"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];

    echo json_encode([
        "success" => true,
        "message" => "Login successful. Welcome, " . $user["full_name"] . "."
    ]);

    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Something went wrong. Please try again."
    ]);

    exit;
}
