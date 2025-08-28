<?php
session_start();
require_once "../../public/assets/actions/connection/db_connection.php";

// Add a check for the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prevent direct access and ensure a valid user ID is in the session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: ../../auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Prepare the SQL statement with placeholders (?)
$stmt = $conn->prepare("
    SELECT 
        accounts.*, 
        school_years.name AS school_year_name, 
        sections.name AS section_name 
    FROM accounts
    LEFT JOIN school_years ON accounts.id = school_years.id
    LEFT JOIN sections ON accounts.id = sections.id
    WHERE accounts.id = ?
    LIMIT 1
");

// Check if the prepared statement was successful
if ($stmt === false) {
    die("SQL prepare() failed: " . htmlspecialchars($conn->error));
}

// Bind the user ID parameter
$stmt->bind_param("i", $user_id);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("User not found!");
}

$user = $result->fetch_assoc();

// Close the statement
$stmt->close();

// Extract the first letter of the full name for the profile circle
$initial = strtoupper(substr($user['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body { 
        font-family: 'Poppins', sans-serif; 
    }
    .profile-card {
        background: linear-gradient(to right, #1f2937, #111827);
        border: 1px solid transparent;
        border-image: linear-gradient(to right, #34d399, #10b981) 1;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease-in-out;
    }
    .profile-card:hover {
        transform: translateY(-5px);
    }
    .modal-overlay {
        background-color: rgba(0, 0, 0, 0.75);
        z-index: 50;
    }
    .modal-content {
        z-index: 60;
    }
    .profile-modal {
        top: 90px; /* Adjust based on navbar height */
        right: 16px;
        z-index: 50;
    }
    .profile-circle-container {
      position: relative;
    }
    .active-dot {
      position: absolute;
      bottom: 0;
      right: 0;
      width: 12px;
      height: 12px;
      background-color: #34d399; /* Green color */
      border-radius: 50%;
      border: 2px solid #1f2937; /* Matches profile circle background */
    }
  </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">

  <div id="logoutModal" class="fixed inset-0 hidden items-center justify-center modal-overlay">
      <div class="bg-gray-800 rounded-lg p-6 max-w-sm w-full modal-content shadow-lg">
          <h3 class="text-xl font-semibold text-white mb-4">Confirm Logout</h3>
          <p class="text-gray-300 mb-6">Are you sure you want to log out?</p>
          <div class="flex justify-end space-x-4">
              <button id="cancelLogout" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Cancel</button>
              <button id="confirmLogout" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Logout</button>
          </div>
      </div>
  </div>

  <div id="profileModal" class="absolute hidden bg-gray-800 rounded-lg shadow-xl profile-modal p-6 w-full max-w-xs transition-opacity duration-300 ease-in-out opacity-0">
    <div class="flex flex-col items-center text-center">
      <div class="relative mb-3">
        <div class="flex items-center justify-center h-16 w-16 bg-green-500 rounded-full text-2xl font-bold text-white uppercase">
          <?= htmlspecialchars($initial) ?>
        </div>
        <div class="active-dot"></div>
      </div>
      <p class="text-lg font-semibold text-white"><?= htmlspecialchars($user['fullname']) ?></p>
      <p class="text-sm text-gray-400"><?= htmlspecialchars($user['school_id']) ?> - <span class="capitalize"><?= htmlspecialchars($user['type']) ?></span></p>
      <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($user['email']) ?></p>
      <p class="text-sm text-gray-400">
          <?= htmlspecialchars($user['school_year_name']) ?> - <span class="capitalize">Section <?= htmlspecialchars($user['section_name']) ?></span>
      </p>
    </div>
    
    <hr class="my-4 border-gray-700">

    <div class="flex flex-col space-y-3">
      <button class="w-full px-4 py-2 text-left text-gray-300 rounded-lg hover:bg-gray-700 transition">
        <i class="fas fa-user-circle mr-2"></i> Manage Profile
      </button>
      <button id="logoutBtn" class="w-full px-4 py-2 text-left text-red-400 rounded-lg hover:bg-red-900/50 transition">
        <i class="fas fa-sign-out-alt mr-2"></i> Logout
      </button>
    </div>
  </div>

  <nav class="bg-gray-800 shadow-lg p-4 flex justify-between items-center relative">
    <div class="flex items-center space-x-4">
      <div class="flex flex-col">
        <h1 class="text-2xl font-bold text-white">WEB-VOTING</h1>
        <p class="text-sm text-gray-400 hidden sm:block">Empowering voices, one vote at a time.</p>
      </div>
    </div>

    <div class="relative">
      <button id="profileBtn" class="flex items-center justify-center h-12 w-12 bg-gray-700 rounded-full text-lg font-semibold text-white uppercase focus:outline-none focus:ring-2 focus:ring-green-500 transition-transform transform hover:scale-105">
        <?= htmlspecialchars($initial) ?>
        <span class="absolute right-0 bottom-0 block w-3 h-3 bg-green-500 rounded-full ring-2 ring-gray-800"></span>
      </button>
    </div>
  </nav>
  <div class="main w-full h-full bg-gray-100">

  </div>
  <script>
    // Get all the necessary elements from the DOM
    const logoutModal = document.getElementById('logoutModal');
    const profileModal = document.getElementById('profileModal');
    const profileBtn = document.getElementById('profileBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    const cancelLogoutBtn = document.getElementById('cancelLogout');

    // Function to show the logout modal
    function showLogoutModal() {
      logoutModal.classList.remove('hidden');
      logoutModal.classList.add('flex');
    }

    // Function to hide the logout modal
    function hideLogoutModal() {
      logoutModal.classList.add('hidden');
      logoutModal.classList.remove('flex');
    }

    // Function to show/hide the profile modal
    function toggleProfileModal() {
      const isHidden = profileModal.classList.contains('hidden');
      if (isHidden) {
        profileModal.classList.remove('hidden');
        setTimeout(() => {
          profileModal.classList.remove('opacity-0');
        }, 10);
      } else {
        profileModal.classList.add('opacity-0');
        setTimeout(() => {
          profileModal.classList.add('hidden');
        }, 300); // Wait for the transition to finish
      }
    }

    // Event listener for the profile button to show/hide the profile modal
    profileBtn.addEventListener('click', (event) => {
      event.stopPropagation();
      toggleProfileModal();
    });

    // Event listener for the logout button inside the profile modal
    logoutBtn.addEventListener('click', (event) => {
      event.stopPropagation();
      toggleProfileModal(); // Hide the profile modal
      showLogoutModal(); // Show the logout confirmation modal
    });

    // Event listener to close the profile modal if the user clicks outside of it
    window.addEventListener('click', (event) => {
      if (!profileModal.contains(event.target) && !profileBtn.contains(event.target)) {
        profileModal.classList.add('opacity-0');
        setTimeout(() => {
          profileModal.classList.add('hidden');
        }, 300);
      }
    });

    // Event listener for the "Cancel" button in the logout modal
    cancelLogoutBtn.addEventListener('click', () => {
      hideLogoutModal();
    });

    // Event listener for the "Logout" button in the logout modal
    confirmLogoutBtn.addEventListener('click', () => {
      window.location.href = '../login/auth.php';
    });
  </script>
</body>
</html>