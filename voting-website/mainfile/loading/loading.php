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
        .loading-container {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .loading-circle {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: rgba(34, 197, 94, 0.7); /* green-500 with transparency */
            animation: pulse 2s infinite ease-in-out;
        }
        .loading-circle:nth-child(2) {
            animation-delay: -1s;
        }
        @keyframes pulse {
            0% {
                transform: scale(0);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 0;
            }
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
        <div class="loading-container mx-auto">
            <div class="loading-circle"></div>
            <div class="loading-circle"></div>
        </div>
        <h1 class="text-4xl font-bold mt-4">Loading</h1>
        <p class="mt-2 text-gray-400">Please wait while we prepare your dashboard...</p>
    </div>
</body>
</html>