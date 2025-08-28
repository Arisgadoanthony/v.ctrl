<?php
session_start();
require_once "../../public/assets/actions/connection/db_connection.php"; // <-- your DB connection file

$message = "";
$showRegisterForm = isset($_SESSION['show_register_form']) ? $_SESSION['show_register_form'] : false;

// ---------------- HANDLE REGISTER ----------------
if (isset($_POST['register'])) {
    $_SESSION['show_register_form'] = true; // Set session flag to keep form visible

    $fullname = trim($conn->real_escape_string($_POST['fullname']));
    $school_id = trim($conn->real_escape_string($_POST['school_id']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password'];

    // Default to 0 if not provided or applicable (for non-students)
    $student_year_id = 0;
    $student_section_id = 0;

    // --- NEW VALIDATION: School ID prefix, Full Name format, and Email format ---
    if (!preg_match("/^[A-Z].*$/", $fullname) || preg_match("/\d/", $fullname)) {
        $message = "❌ Full name must start with an uppercase letter and contain no numbers.";
    } elseif (!preg_match("/^(GC|GR|GST).*$/", $school_id)) {
        $message = "❌ School ID must start with GC, GR, or GST.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Please enter a valid email address.";
    } else {
        // Determine type by school_id prefix
        $type = "student"; // Default type
        if (str_starts_with($school_id, "GST")) {
            $type = "admin";
        } elseif (str_starts_with($school_id, "GR")) {
            $type = "teacher";
        }

        // If it's a student, get year and section IDs. Otherwise, they remain 0.
        if ($type === "student") {
            $student_year_id = isset($_POST['student_year_id']) ? intval($_POST['student_year_id']) : 0;
            $student_section_id = isset($_POST['student_section_id']) ? intval($_POST['student_section_id']) : 0;
            
            // Basic validation for student fields
            if ($student_year_id === 0 || $student_section_id === 0) {
                $message = "❌ Please select a valid year and section for student accounts.";
            }
        }
    }

    // Password validation: Uppercase start, at least 8 chars, 1 number
    if (empty($message) && (!preg_match("/^[A-Z].{7,}$/", $password) || !preg_match("/[0-9]/", $password))) {
        $message = "❌ Password must start with uppercase, be at least 8 characters, and include a number.";
    } else if (empty($message)) { // Only proceed if no previous validation errors
        // Prevent duplicate fullname, school_id, or email
        $check = $conn->query("SELECT * FROM accounts WHERE fullname='$fullname' OR school_id='$school_id' OR email='$email'");
        if ($check && $check->num_rows > 0) {
            $message = "❌ Full name, School ID, or Email already exists!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Set voting status to 'not' for new accounts
            $voting_status = 'not';

            $sql = "INSERT INTO accounts (fullname, school_id, email, password, status, type, voting_status, student_year_id, student_section_id)
                    VALUES ('$fullname', '$school_id', '$email', '$hashedPassword', 'active', '$type', '$voting_status', '$student_year_id', '$student_section_id')";
            if ($conn->query($sql)) {
                $message = "✅ Account created successfully. Please login.";
                $_SESSION['show_register_form'] = false; // Reset session flag on success
            } else {
                $message = "❌ Error: " . $conn->error;
            }
        }
    }
}

// ---------------- HANDLE LOGIN ----------------
if (isset($_POST['login'])) {
    $_SESSION['show_register_form'] = false; // Reset session flag on login attempt

    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM accounts WHERE email='$email' LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Check voting status before proceeding
            if ($row['voting_status'] === 'done') {
                $message = "❌ You have already cast your vote. You cannot log in again.";
            } else {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['authenticated'] = true; // Set the authentication flag
                header("Location: ../loading/loading.php");
                exit;
            }
        } else {
            $message = "❌ Incorrect password!";
        }
    } else {
        $message = "❌ No account found with that email!";
    }
}

// Fetch school years for registration form
$school_years = [];
$years_result = $conn->query("SELECT id, name FROM school_years ORDER BY id");
if ($years_result) {
    while ($row = $years_result->fetch_assoc()) {
        $school_years[] = $row;
    }
}

// Fetch sections for registration form
$sections = [];
$sections_result = $conn->query("SELECT id, name FROM sections ORDER BY name");
if ($sections_result) {
    while ($row = $sections_result->fetch_assoc()) {
        $sections[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auth</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #1a202c; /* Darker background */
        background-image: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); /* Subtle gradient */
    }
    .toast-center {
        position: fixed;
        top: 10%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 50;
        width: 90%;
        max-width: 800px;
    }
    .toast-transition {
        transition: opacity 0.5s ease-in-out;
    }
    .toast-hide {
        opacity: 0;
    }
    /* Custom input styling for icons */
    .input-with-icon {
      position: relative;
    }
    .input-with-icon input,
    .input-with-icon select {
      padding-left: 2.5rem; /* Space for the icon */
    }
    .input-with-icon svg {
      position: absolute;
      left: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af; /* Gray-400 */
      width: 1.25rem;
      height: 1.25rem;
      pointer-events: none; /* Ensure the icon doesn't block input interaction */
    }
    /* Custom button styles */
    .btn-primary {
      @apply w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300 ease-in-out shadow-lg transform hover:scale-105;
    }
  </style>
  <script>
    function toggleForms() {
      document.getElementById("loginForm").classList.toggle("hidden");
      document.getElementById("registerForm").classList.toggle("hidden");
      // Call handleSchoolIdInput when switching to registration form to set initial state
      if (!document.getElementById("registerForm").classList.contains("hidden")) {
          handleSchoolIdInput();
      }
    }
    function togglePassword(id) {
      const input = document.getElementById(id);
      input.type = input.type === "password" ? "text" : "password";
    }
    // Auto-hide and close toast
    function setupToast() {
      const toast = document.getElementById("toast");
      if (toast) {
        setTimeout(() => {
          hideToast(toast);
        }, 3000);

        const closeBtn = document.getElementById("close-toast-btn");
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                hideToast(toast);
            });
        }
      }
    }

    function hideToast(toastElement) {
        toastElement.classList.add("toast-hide");
        setTimeout(() => {
            toastElement.remove();
        }, 500);
    }

    window.onload = function() {
        setupToast();
        // Ensure initial state of year/section is correct if register form is visible
        if (!document.getElementById("registerForm").classList.contains("hidden")) {
            handleSchoolIdInput();
        }
    };

    // JavaScript function for School ID
    function handleSchoolIdInput() {
        const schoolIdInput = document.getElementById("schoolIdInput");
        const studentYearSelect = document.getElementById("studentYearSelect");
        const studentSectionSelect = document.getElementById("studentSectionSelect");
        
        const schoolId = schoolIdInput.value.toUpperCase().trim();

        // If school ID starts with GR or GST (non-students), disable and clear year/section
        if (schoolId.startsWith("GR") || schoolId.startsWith("GST")) {
            studentYearSelect.disabled = true;
            studentYearSelect.selectedIndex = 0; // Clear selected value
            studentSectionSelect.disabled = true;
            studentSectionSelect.selectedIndex = 0; // Clear selected value
        } else {
            // For other types (e.g., GC for students), enable them and let them retain selection
            studentYearSelect.disabled = false;
            studentSectionSelect.disabled = false;
        }
    }
  </script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen text-gray-100">

  <?php if (!empty($message)) : ?>
    <div id="toast" 
         class="toast-center toast-transition bg-gray-800 border-l-4 
                <?php if(strpos($message,'✅')!==false) echo 'border-green-500'; else echo 'border-red-500'; ?>
                shadow-xl px-4 py-3 rounded-lg text-gray-200">
      <div class="flex justify-between items-center">
        <span><?= $message ?> <?= strpos($message,'✅')!==false ? '🎉' : '😔' ?></span>
        <button id="close-toast-btn" class="text-gray-400 hover:text-white ml-4">&times;</button>
      </div>
    </div>
  <?php endif; ?>

  <div class="bg-gray-800 shadow-2xl rounded-2xl w-96 p-8">
    <div id="loginForm" class="<?= $showRegisterForm ? 'hidden' : '' ?>">
      <h2 class="text-3xl font-bold text-center mb-6 text-white flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
        Login
      </h2>
      <form method="POST" class="space-y-4">
        <div class="input-with-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-at-sign"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>
          <input type="email" name="email" placeholder="Email" required
                 class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="input-with-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" id="loginPassword" name="password" placeholder="Password" required
                 class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center space-x-2">
            <input type="checkbox" onclick="togglePassword('loginPassword')" class="rounded border-gray-500 bg-gray-700">
            <span class="text-gray-300">Show Password</span>
          </label>
          <a href="#" class="text-blue-400 hover:underline">Forgot Password?</a>
        </div>

        <button type="submit" name="login" class="btn-primary w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300 ease-in-out shadow-lg transform hover:scale-105">
          Login
        </button>
      </form>
      <p class="text-sm text-gray-400 mt-4 text-center">
        Don't have an account? 
        <a href="javascript:void(0)" onclick="toggleForms()" class="text-blue-400 hover:underline">Register</a>
      </p>
    </div>

    <div id="registerForm" class="<?= $showRegisterForm ? '' : 'hidden' ?>">
      <h2 class="text-3xl font-bold text-center mb-6 text-white flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-plus"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
        Create Account
      </h2>
      <form method="POST" class="space-y-4">
        <div class="input-with-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input type="text" name="fullname" placeholder="Full Name" required
                 class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="input-with-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-graduation-cap"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.084a1 1 0 0 0-.009 1.838l8.57 3.832a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 6 0 0 0 6 6v-4"/></svg>
          <input type="text" name="school_id" id="schoolIdInput" placeholder="SchoolID (e.g., GC, GR, GST)" required
                 class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500"
                 oninput="handleSchoolIdInput()">
        </div>

        <div class="input-with-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          <input type="email" name="email" placeholder="Email" required
                 class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="input-with-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
            <select name="student_year_id" id="studentYearSelect" required
                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500">
              <option value="" disabled selected>Select Year</option>
              <?php foreach ($school_years as $year) : ?>
                <option value="<?= $year['id'] ?>"><?= htmlspecialchars($year['name']) ?></option>
              <?php endforeach; ?>
            </select>
        </div>

        <div class="input-with-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-grid"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
            <select name="student_section_id" id="studentSectionSelect" required
                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500">
              <option value="" disabled selected>Select Section</option>
              <?php foreach ($sections as $section) : ?>
                <option value="<?= $section['id'] ?>"><?= htmlspecialchars($section['name']) ?></option>
              <?php endforeach; ?>
            </select>
        </div>

        <div class="input-with-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" id="regPassword" name="password" placeholder="Password" required
                 class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500">
        </div>
        
        <label class="flex items-center space-x-2 text-sm text-gray-300">
          <input type="checkbox" onclick="togglePassword('regPassword')" class="rounded border-gray-500 bg-gray-700">
          <span>Show Password</span>
        </label>

        <button type="submit" name="register" class="btn-primary w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300 ease-in-out shadow-lg transform hover:scale-105">
          Create Account
        </button>
      </form>
      <p class="text-sm text-gray-400 mt-4 text-center">
        Already have an account? 
        <a href="javascript:void(0)" onclick="toggleForms()" class="text-blue-400 hover:underline">Login</a>
      </p>
    </div>
  </div>

</body>
</html>