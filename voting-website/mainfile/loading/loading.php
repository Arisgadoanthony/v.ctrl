<?php
session_start();
require_once "../../public/assets/actions/connection/db_connection.php"; // <-- your DB connection file

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT school_id FROM accounts WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: ../login/auth.php");
    exit;
}

$school_id = $user['school_id'];

// Decide redirect path
if (strpos($school_id, "GST") === 0) {
    $redirect = "../admin/adminpage.php";
} elseif (strpos($school_id, "GR") === 0) {
    $redirect = "../prof/teacherpage.php";
} elseif (strpos($school_id, "GC") === 0) {
    $redirect = "../user/studentpage.php";
} else {
    // default fallback
    $redirect = "../login/auth.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #111827; /* bg-gray-900 */
        }
    </style>
    <script>
        const redirectUrl = "<?= $redirect ?>";
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 3000);
    </script>
</head>
<body class="flex items-center justify-center min-h-screen text-white">
    <div class="text-center">
        <!-- V logo created with SVG -->
        <dotlottie-wc src="https://lottie.host/528a1040-6521-48a5-bbf4-0fb6cf51785f/sGb4lXO9Aj.lottie" loading="lazy" style="width: 400px;height: 400px" speed="1" autoplay loop></dotlottie-wc>
        <p class="text-gray-400" style="margin-top: -100px;">Please wait while authenticating...</p>
    </div>
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.6.2/dist/dotlottie-wc.js" type="module"></script>
</body>
</html>
