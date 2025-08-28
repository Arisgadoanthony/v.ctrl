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
</head>
<body class="flex items-center justify-center min-h-screen text-white">
    <div class="text-center">
        <!-- V logo created with SVG -->
        <dotlottie-wc src="https://lottie.host/528a1040-6521-48a5-bbf4-0fb6cf51785f/sGb4lXO9Aj.lottie" loading="lazy" style="width: 400px;height: 400px" speed="1" autoplay loop></dotlottie-wc>
        <p class="text-gray-400" style="margin-top: -100px;">Please wait, your credentials are being stored in vault...</p>
    </div>
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.6.2/dist/dotlottie-wc.js" type="module"></script>
 <script>
        setTimeout(() => {
            window.location.href = '../../mainfile/login/auth.php';
        }, 4000);
    </script>
</body>
</html>
