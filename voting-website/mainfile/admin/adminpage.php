<?php
session_start();
require_once "../../public/assets/actions/connection/db_connection.php"; // <-- your DB connection file

// Prevent direct access and ensure a valid user ID is in the session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: ../../auth.php");
    exit;
}

// Function to safely fetch data (prevents undefined index warnings)
function safe_fetch_assoc($result) {
    return $result ? $result->fetch_assoc() : null;
}

// Handle all AJAX actions in one file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? null;
    $success = false;
    $message = '';
    $data_to_return = []; // Initialize data container for re-fetch

    // Action: Update Account Status
    if ($action === 'update_status') {
        $account_id = $_POST['account_id'] ?? null;
        $new_status = $_POST['new_status'] ?? null;

        if (!$account_id || !in_array($new_status, ['active', 'inactive', 'dropped', 'graduated', 'resigned'])) {
            $message = 'Invalid data provided for status update.';
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $account_id);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Status updated successfully.';
            } else {
                $message = 'Failed to update status: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Add Position
    elseif ($action === 'add_position') {
        $position_name = $_POST['name'] ?? null;
        if (empty($position_name)) {
            $message = 'Position name cannot be empty.';
        } else {
            $stmt = $conn->prepare("INSERT INTO positions (name) VALUES (?)");
            $stmt->bind_param("s", $position_name);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Position added successfully.';
            } else {
                $message = 'Failed to add position: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Edit Position
    elseif ($action === 'edit_position') {
        $position_id = $_POST['id'] ?? null;
        $position_name = $_POST['name'] ?? null;
        if (empty($position_id) || empty($position_name)) {
            $message = 'Invalid data provided for position edit.';
        } else {
            $stmt = $conn->prepare("UPDATE positions SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $position_name, $position_id);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Position updated successfully.';
            } else {
                $message = 'Failed to update position: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Delete Position
    elseif ($action === 'delete_position') {
        $position_id = $_POST['id'] ?? null;
        if (empty($position_id)) {
            $message = 'Position ID cannot be empty.';
        } else {
            // Check for associated candidates before deleting position
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM candidates WHERE position_id = ?");
            $check_stmt->bind_param("i", $position_id);
            $check_stmt->execute();
            $check_stmt->bind_result($candidate_count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($candidate_count > 0) {
                $message = 'Cannot delete position because there are candidates associated with it. Delete associated candidates first or update their positions.';
            } else {
                $stmt = $conn->prepare("DELETE FROM positions WHERE id = ?");
                $stmt->bind_param("i", $position_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'Position deleted successfully.';
                } else {
                    $message = 'Failed to delete position: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    // Action: Add Partylist
    elseif ($action === 'add_partylist') {
        $partylist_name = $_POST['name'] ?? null;
        if (empty($partylist_name)) {
            $message = 'Partylist name cannot be empty.';
        } else {
            $stmt = $conn->prepare("INSERT INTO partylists (name) VALUES (?)");
            $stmt->bind_param("s", $partylist_name);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Partylist added successfully.';
            } else {
                $message = 'Failed to add partylist: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Edit Partylist
    elseif ($action === 'edit_partylist') {
        $partylist_id = $_POST['id'] ?? null;
        $partylist_name = $_POST['name'] ?? null;
        if (empty($partylist_id) || empty($partylist_name)) {
            $message = 'Invalid data provided for partylist edit.';
        } else {
            $stmt = $conn->prepare("UPDATE partylists SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $partylist_name, $partylist_id);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Partylist updated successfully.';
            } else {
                $message = 'Failed to update partylist: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Delete Partylist
    elseif ($action === 'delete_partylist') {
        $partylist_id = $_POST['id'] ?? null;
        if (empty($partylist_id)) {
            $message = 'Partylist ID cannot be empty.';
        } else {
            // Check for associated candidates before deleting partylist
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM candidates WHERE partylist_id = ?");
            $check_stmt->bind_param("i", $partylist_id);
            $check_stmt->execute();
            $check_stmt->bind_result($candidate_count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($candidate_count > 0) {
                $message = 'Cannot delete partylist because there are candidates associated with it. Delete associated candidates first or update their partylists.';
            } else {
                $stmt = $conn->prepare("DELETE FROM partylists WHERE id = ?");
                $stmt->bind_param("i", $partylist_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'Partylist deleted successfully.';
                } else {
                    $message = 'Failed to delete partylist: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    // Action: Add School Year
    elseif ($action === 'add_school_year') {
        $school_year_name = $_POST['name'] ?? null;
        if (empty($school_year_name)) {
            $message = 'School Year name cannot be empty.';
        } else {
            $stmt = $conn->prepare("INSERT INTO school_years (name) VALUES (?)");
            $stmt->bind_param("s", $school_year_name);
            if ($stmt->execute()) {
                $success = true;
                $message = 'School Year added successfully.';
            } else {
                $message = 'Failed to add school year: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Edit School Year
    elseif ($action === 'edit_school_year') {
        $school_year_id = $_POST['id'] ?? null;
        $school_year_name = $_POST['name'] ?? null;
        if (empty($school_year_id) || empty($school_year_name)) {
            $message = 'Invalid data provided for school year edit.';
        } else {
            $stmt = $conn->prepare("UPDATE school_years SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $school_year_name, $school_year_id);
            if ($stmt->execute()) {
                $success = true;
                $message = 'School Year updated successfully.';
            } else {
                $message = 'Failed to update school year: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Delete School Year
    elseif ($action === 'delete_school_year') {
        $school_year_id = $_POST['id'] ?? null;
        if (empty($school_year_id)) {
            $message = 'School Year ID cannot be empty.';
        } else {
            // Check for associated candidates before deleting school year
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM candidates WHERE school_year_id = ?");
            $check_stmt->bind_param("i", $school_year_id);
            $check_stmt->execute();
            $check_stmt->bind_result($candidate_count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($candidate_count > 0) {
                $message = 'Cannot delete school year because there are candidates associated with it. Delete associated candidates first or update their school years.';
            } else {
                $stmt = $conn->prepare("DELETE FROM school_years WHERE id = ?");
                $stmt->bind_param("i", $school_year_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'School Year deleted successfully.';
                } else {
                    $message = 'Failed to delete school year: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    // Action: Add Candidate
    elseif ($action === 'add_candidate') {
        $cand_name = $_POST['name'] ?? null;
        $cand_position_id = $_POST['position_id'] ?? null;
        $cand_image_base64 = $_POST['image_base64'] ?? null;
        $cand_school_year_id = $_POST['school_year_id'] ?? null;
        $cand_election_year = $_POST['election_year'] ?? null;
        $cand_partylist_id = $_POST['partylist_id'] ?? null;

        if (empty($cand_name) || empty($cand_position_id) || empty($cand_school_year_id) || empty($cand_election_year) || empty($cand_partylist_id)) {
            $message = 'Invalid data provided for candidate addition. Name, Position, School Year, Election Year, and Partylist are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO candidates (name, position_id, image, school_year_id, election_year, partylist_id, vote_count) VALUES (?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("sisisi", $cand_name, $cand_position_id, $cand_image_base64, $cand_school_year_id, $cand_election_year, $cand_partylist_id);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Candidate added successfully.';
            } else {
                $message = 'Failed to add candidate: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Edit Candidate
    elseif ($action === 'edit_candidate') {
        $cand_id = $_POST['id'] ?? null;
        $cand_name = $_POST['name'] ?? null;
        $cand_position_id = $_POST['position_id'] ?? null;
        $cand_image_base64 = $_POST['image_base64'] ?? null;
        $cand_school_year_id = $_POST['school_year_id'] ?? null;
        $cand_election_year = $_POST['election_year'] ?? null;
        $cand_partylist_id = $_POST['partylist_id'] ?? null;

        if (empty($cand_id) || empty($cand_name) || empty($cand_position_id) || empty($cand_school_year_id) || empty($cand_election_year) || empty($cand_partylist_id)) {
            $message = 'Invalid data provided for candidate edit. Name, Position, School Year, Election Year, and Partylist are required.';
        } else {
            // Check if the image_base64 is a placeholder or an actual image
            $image_to_update = $cand_image_base64;
            // Assuming placeholder is 'https://placehold.co/100x100/CCCCCC/000000?text=No+Image' or similar
            // If the image is a placeholder and not a real base64 string, don't update it to avoid storing placeholders.
            // You might need a more robust check if your placeholders vary.
            if (strpos($image_to_update, 'data:image/') === false && strpos($image_to_update, 'placehold.co') !== false) {
                 // Fetch the existing image from the database if it's a placeholder
                 $existing_image_stmt = $conn->prepare("SELECT image FROM candidates WHERE id = ?");
                 $existing_image_stmt->bind_param("i", $cand_id);
                 $existing_image_stmt->execute();
                 $existing_image_stmt->bind_result($existing_image);
                 $existing_image_stmt->fetch();
                 $existing_image_stmt->close();
                 $image_to_update = $existing_image; // Keep the existing image
            }


            $stmt = $conn->prepare("UPDATE candidates SET name = ?, position_id = ?, image = ?, school_year_id = ?, election_year = ?, partylist_id = ? WHERE id = ?");
            // The `image` column in your `candidates` table from `candidates.sql` is `longtext`.
            // The `bind_param` for `image` should be 's' for string.
            // The previous code had 'sisisisi' which means string, int, string, int, string, int, string, int.
            // However, the parameters are (name, position_id, image, school_year_id, election_year, partylist_id, cand_id).
            // This order needs to match the types: s, i, s, i, i, i, i
            // Corrected to "sisiiii"
            $stmt->bind_param("sisiiii", $cand_name, $cand_position_id, $image_to_update, $cand_school_year_id, $cand_election_year, $cand_partylist_id, $cand_id);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Candidate updated successfully.';
            } else {
                $message = 'Failed to update candidate: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Action: Delete Candidate
    elseif ($action === 'delete_candidate') {
        $cand_id = $_POST['id'] ?? null;
        if (empty($cand_id)) {
            $message = 'Candidate ID cannot be empty.';
        } else {
            $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
            $stmt->bind_param("i", $cand_id);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Candidate deleted successfully.';
            } else {
                $message = 'Failed to delete candidate: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // New Action: Fetch All Candidate Related Data (and Dashboard Data)
    elseif ($action === 'fetch_all_data') {
        $success = true;
        $message = 'Data fetched successfully.';

        // Fetch positions
        $positions = [];
        $positions_result = $conn->query("SELECT * FROM positions ORDER BY name ASC");
        if ($positions_result) { while ($row = $positions_result->fetch_assoc()) { $positions[] = $row; } }
        else { $success = false; $message .= " Failed to fetch positions."; }

        // Fetch partylists
        $partylists = [];
        $partylists_result = $conn->query("SELECT * FROM partylists ORDER BY name ASC");
        if ($partylists_result) { while ($row = $partylists_result->fetch_assoc()) { $partylists[] = $row; } }
        else { $success = false; $message .= " Failed to fetch partylists."; }

        // Fetch school years
        $school_years = [];
        $school_years_result = $conn->query("SELECT * FROM school_years ORDER BY name ASC");
        if ($school_years_result) { while ($row = $school_years_result->fetch_assoc()) { $school_years[] = $row; } }
        else { $success = false; $message .= " Failed to fetch school years."; }

        // Fetch candidates with joined data
        $candidates = [];
        $candidates_query = "
            SELECT 
                c.*, 
                p.name as position_name,
                sy.name as school_year_name,
                pl.name as partylist_name
            FROM candidates c
            LEFT JOIN positions p ON c.position_id = p.id
            LEFT JOIN school_years sy ON c.school_year_id = sy.id
            LEFT JOIN partylists pl ON c.partylist_id = pl.id
            ORDER BY p.name ASC, c.name ASC
        ";
        $candidates_result = $conn->query($candidates_query);
        if ($candidates_result) { while ($row = $candidates_result->fetch_assoc()) { $candidates[] = $row; } }
        else { $success = false; $message .= " Failed to fetch candidates."; }
        
        // Fetch Dashboard counts
        $total_accounts = $conn->query("SELECT COUNT(*) FROM accounts")->fetch_row()[0];
        $total_voted = $conn->query("SELECT COUNT(*) FROM accounts WHERE voting_status = 'done'")->fetch_row()[0];
        $total_active_voters = $conn->query("SELECT COUNT(*) FROM accounts WHERE status = 'active' AND type = 'student'")->fetch_row()[0];
        $voter_turnout = ($total_active_voters > 0) ? round(($total_voted / $total_active_voters) * 100) : 0;
        $total_candidates = $conn->query("SELECT COUNT(*) FROM candidates")->fetch_row()[0];


        // Fetch account type counts for pie chart
        $account_type_counts = [];
        $account_types_result = $conn->query("SELECT type, COUNT(*) as count FROM accounts GROUP BY type");
        if ($account_types_result) {
            while ($row = $account_types_result->fetch_assoc()) {
                $account_type_counts[$row['type']] = $row['count'];
            }
        } else { $success = false; $message .= " Failed to fetch account type counts."; }


        $data_to_return = [
            'candidates' => $candidates,
            'positions' => $positions,
            'partylists' => $partylists,
            'school_years' => $school_years,
            'dashboard_counts' => [ // New: Include dashboard data here
                'total_accounts' => $total_accounts,
                'total_voted' => $total_voted,
                'total_active_voters' => $total_active_voters,
                'voter_turnout' => $voter_turnout,
                'total_candidates' => $total_candidates
            ],
            'account_type_counts' => $account_type_counts // New: Include pie chart data
        ];
    }

    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data_to_return]);

    $conn->close();
    exit;
}

// Fetch current user data (for initial page load)
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM accounts WHERE id = '$user_id' LIMIT 1");
if (!$result || $result->num_rows == 0) {
    die("User not found!");
}
$user = $result->fetch_assoc();
$initial = strtoupper(substr($user['fullname'], 0, 1));

// Initial load dashboard data (will be updated by JS after fetchAllCandidateRelatedData)
$total_accounts = $conn->query("SELECT COUNT(*) FROM accounts")->fetch_row()[0];
$total_voted = $conn->query("SELECT COUNT(*) FROM accounts WHERE voting_status = 'done'")->fetch_row()[0];
$total_active_voters = $conn->query("SELECT COUNT(*) FROM accounts WHERE status = 'active' AND type = 'student'")->fetch_row()[0];
$voter_turnout = ($total_active_voters > 0) ? round(($total_voted / $total_active_voters) * 100) : 0;
$total_candidates = $conn->query("SELECT COUNT(*) FROM candidates")->fetch_row()[0];

$account_type_counts = [];
$account_types_result = $conn->query("SELECT type, COUNT(*) as count FROM accounts GROUP BY type");
if ($account_types_result) {
    while ($row = $account_types_result->fetch_assoc()) {
        $account_type_counts[$row['type']] = $row['count'];
    }
}

// Fetch all accounts for the accounts table (no server-side filtering for initial load, client-side handles it)
$accounts_query_result = $conn->query("SELECT * FROM accounts");
$accounts = [];
if ($accounts_query_result) {
    while ($row = $accounts_query_result->fetch_assoc()) {
        $accounts[] = $row;
    }
}

// Ensure all PHP variables for initial load are defined, even if tables don't exist
$positions = [];
$partylists = [];
$school_years = [];
$candidates = [];

// No need to fetch these again here, as `fetchAllCandidateRelatedData` will populate them
// This keeps the initial PHP rendering light and dynamic data consistent with AJAX.

// Close connection if no more queries needed
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
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
        max-height: 90vh; /* Limit modal height */
        overflow-y: auto; /* Enable scrolling for modal content */
        position: relative; /* Needed for absolute positioning of close button */
    }
    .modal-close-button {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9CA3AF; /* Gray 400 */
        cursor: pointer;
        transition: color 0.2s ease-in-out;
    }
    .modal-close-button:hover {
        color: #E5E7EB; /* Gray 200 */
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
    /* Simple transition for content change */
    .fade-in {
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Filter Toggle Button for small screens */
    @media (max-width: 767px) {
        .filter-controls-container {
            display: none;
            flex-direction: column;
        }
        .filter-controls-container.active {
            display: flex;
        }
        .filter-toggle-button {
            display: block;
        }
    }
    @media (min-width: 768px) {
        .filter-controls-container {
            display: flex !important; /* Always show on larger screens */
        }
        .filter-toggle-button {
            display: none;
        }
    }

    .pie-chart-legend ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .pie-chart-legend li {
        display: flex;
        align-items: center;
        margin-bottom: 4px;
    }
    .pie-chart-legend span.color-box {
        width: 12px;
        height: 12px;
        display: inline-block;
        margin-right: 8px;
        border-radius: 2px;
    }

    /* For pie chart to cover circle */
    canvas#accountTypePieChart {
        display: block;
        max-width: 100%;
        height: auto;
        border-radius: 50%; /* Ensure it's circular */
        transform: rotate(180deg); /* Rotate to fix initial rendering issue */
    }
    .candidate-image-preview-container {
      width: 100%; /* Make container full width */
      padding-top: 100%; /* Create a square aspect ratio */
      position: relative;
      overflow: hidden;
    }
    .candidate-image-preview-container img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Webkit Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 12px;
    }

    ::-webkit-scrollbar-track {
        background: #1f2937; /* Darker gray for track */
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background-color: #4B5563; /* Gray 600 for thumb */
        border-radius: 10px;
        border: 3px solid #1f2937; /* Padding around thumb */
    }

    ::-webkit-scrollbar-thumb:hover {
        background-color: #6B7280; /* Lighter gray on hover */
    }

    /* Notification Toast */
    .toast-notification {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 100;
        padding: 0.75rem 1.25rem;
        border-radius: 0.5rem;
        color: white;
        font-weight: 600;
        opacity: 0;
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        transform: translateX(100%);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .toast-notification.show {
        opacity: 1;
        transform: translateX(0);
    }
    .toast-notification.success {
        background: linear-gradient(to right, #b8ffbeff, #ffffffff); /* Green */
        border: 2px solid #10B981;
        color: #10B981;
    }
    .toast-notification.error {
        background: linear-gradient(to right, #ffc8c8ff, #fffafaff); /* Red */
        border: 2px solid #DC2626;
        color: #DC2626;
    }

    /* Decision Modal (Confirmation/Alert) */
    .decision-modal-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.75);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000; /* Higher than other modals */
        backdrop-filter: blur(5px); /* Blur effect */
    }
    .decision-modal-content {
        background-color: #1F2937; /* Gray 800 */
        border-radius: 0.75rem;
        padding: 2rem;
        max-width: 24rem;
        width: 90%;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        text-align: center;
        position: relative;
    }
  </style>
</head>
<body class="bg-gray-900 min-h-screen flex flex-col">

  <!-- Notification Toast Container -->
  <div id="toastContainer" class="fixed top-4 right-4 z-[1000]"></div>

  <!-- Decision Modal (Central Confirmation/Alert) -->
  <div id="decisionModal" class="decision-modal-overlay hidden">
      <div class="decision-modal-content">
          <h3 id="decisionModalTitle" class="text-xl font-semibold text-white mb-4"></h3>
          <p id="decisionModalMessage" class="text-gray-300 mb-6"></p>
          <div id="decisionModalActions" class="flex justify-center space-x-4">
              <!-- Buttons will be dynamically inserted here -->
          </div>
      </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="fixed inset-0 hidden items-center justify-center modal-overlay">
      <div class="bg-gray-800 rounded-lg p-6 max-w-sm w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
          <button class="modal-close-button" onclick="hideModal(logoutModal)" aria-label="Close logout modal">&times;</button>
          <h3 id="logoutModalTitle" class="text-xl font-semibold text-white mb-4">Confirm Logout</h3>
          <p class="text-gray-300 mb-6">Are you sure you want to log out?</p>
          <div class="flex justify-end space-x-4">
              <button id="cancelLogout" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Cancel</button>
              <button id="confirmLogout" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Logout</button>
          </div>
      </div>
  </div>

  <!-- Profile Dropdown Modal -->
  <div id="profileModal" class="absolute hidden bg-gray-800 rounded-lg shadow-xl profile-modal p-6 w-full max-w-xs transition-opacity duration-300 ease-in-out opacity-0" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
    <button class="modal-close-button" onclick="toggleProfileModal()" aria-label="Close profile modal">&times;</button>
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

  <div class="flex-grow flex flex-col p-2 sm:p-6">
    <div class="bg-gray-800 rounded-lg shadow-xl p-8 mb-6 flex-shrink-0">
      <h2 class="text-3xl font-500 text-white mb-2">Welcome, <span class="text-green-400"><?= htmlspecialchars($user['fullname']) ?></span>🎉</h2>
      <p class="text-gray-400 mb-6">Use the buttons below to navigate and manage the voting system.</p>
      <div class="flex flex-wrap gap-4 justify-around sm:justify-start">
        <button id="dashboardBtn" class="nav-btn px-4 py-3 flex items-center bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors" data-content="dashboard">
          <i class="fas fa-home sm:mr-2"></i> <span class="sm:block hidden">Dashboard</span>
        </button>
        <button id="accountsBtn" class="nav-btn px-4 py-3 flex items-center bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors" data-content="accounts">
          <i class="fas fa-users sm:mr-2"></i> <span class="sm:block hidden">Accounts</span>
        </button>
        <button id="candidatesBtn" class="nav-btn px-4 py-3 flex items-center bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors" data-content="candidates">
          <i class="fas fa-user-tie sm:mr-2"></i> <span class="sm:block hidden">Candidates</span>
        </button>
        <button id="reportsBtn" class="nav-btn px-4 py-3 flex items-center bg-yellow-600 text-white rounded-lg font-semibold hover:bg-yellow-700 transition-colors" data-content="reports">
          <i class="fas fa-chart-bar sm:mr-2"></i> <span class="sm:block hidden">Reports</span>
        </button>
      </div>
    </div>

    <div id="contentContainer" class="flex-grow">

      <!-- Dashboard Content -->
      <div id="dashboardContent" class="bg-gray-800 rounded-lg shadow-xl p-2 sm:p-4 h-full fade-in">
        <h3 class="text-2xl font-bold text-white mb-6">Dashboard Overview</h3>
        <div class="grid grid-cols-2 sm:grid-cols-1 lg:grid-cols-4 gap-2 mb-8">
          <div class="bg-gray-700 p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-400">Total User's Accounts</p>
              <p id="dashboard-total-accounts" class="text-3xl font-bold text-green-400"><?= htmlspecialchars($total_accounts) ?></p>
            </div>
            <i class="fas fa-users text-4xl text-gray-500 opacity-50"></i>
          </div>
          <div class="bg-gray-700 p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-400">Voters Who Voted</p>
              <p id="dashboard-total-voted" class="text-3xl font-bold text-yellow-400"><?= htmlspecialchars($total_voted) ?></p>
            </div>
            <i class="fas fa-user-check text-4xl text-gray-500 opacity-50"></i>
          </div>
          <div class="bg-gray-700 p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-400">Total Candidates</p>
              <p id="dashboard-total-candidates" class="text-3xl font-bold text-purple-400"><?= htmlspecialchars($total_candidates) ?></p>
            </div>
            <i class="fas fa-user-tie text-4xl text-gray-500 opacity-50"></i>
          </div>
          <div class="bg-gray-700 p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-400">Voter Turnout</p>
              <p id="dashboard-voter-turnout" class="text-3xl font-bold text-blue-400"><?= htmlspecialchars($voter_turnout) ?>%</p>
            </div>
            <i class="fas fa-vote-yea text-4xl text-gray-500 opacity-50"></i>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-gray-700 p-6 rounded-lg shadow-md flex flex-col sm:flex-row items-center justify-around">
            <div class="mb-4 sm:mb-0 sm:mr-6">
                <h4 class="text-lg font-semibold text-white mb-4 text-center sm:text-left">Account Types Distribution</h4>
                <canvas id="accountTypePieChart" width="200" height="200" class="mx-auto"></canvas>
            </div>
            <div id="pie-chart-legend" class="pie-chart-legend text-gray-300">
                <!-- Legend will be rendered here by JavaScript -->
            </div>
          </div>
          
          <div class="bg-gray-700 p-6 rounded-lg shadow-md">
            <h4 class="text-lg font-semibold text-white mb-4">Voting Progress</h4>
            <div class="w-full bg-gray-600 rounded-full h-4">
              <div id="voting-progress-bar" class="bg-green-500 h-4 rounded-full" style="width: <?= htmlspecialchars($voter_turnout) ?>%;"></div>
            </div>
            <p id="voting-progress-text" class="text-right text-gray-400 mt-2"><?= htmlspecialchars($voter_turnout) ?>% of eligible voters have cast their ballot</p>
            <h4 class="text-lg font-semibold text-white mb-4 mt-6">Votes by Position</h4>
            <div class="w-full h-48 bg-gray-600 rounded-lg flex items-center justify-center">
              <span class="text-gray-400 text-lg">Graph Placeholder (Coming Soon!)</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Accounts Content -->
      <div id="accountsContent" class="hidden bg-gray-800 rounded-lg shadow-xl p-2 sm:p-4 h-full">
        <div class="flex items-center justify-between w-full mt-2 p-2 mb-4 border-b border-gray-600 pb-2" style="display: flex; align-items: center;">
          <h3 class="text-white flex flex-col">
            <strong class="text-2xl font-bold ">Accounts</strong>
            <span>Your account management</span>
          </h3>

          <button id="filterToggleButton" class="md:hidden px-4 py-2 text-white rounded-lg">
                  <i class="fas fa-filter mr-2"></i>
          </button>
        </div>
        <div class="mb-6">
          
            <div id="filterControlsContainer" class="filter-controls-container flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 items-center hidden sm:block">
                <div class="flex-grow w-full md:w-auto">
                    <label for="search" class="sr-only">Search</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="search" name="search" class="block w-full rounded-md border-gray-700 bg-gray-700 py-2 pl-10 pr-3 text-white placeholder-gray-400 focus:border-green-500 focus:ring-green-500 sm:text-sm" placeholder="Search by Full Name...">
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full md:w-auto">
                    <select id="filter-status" name="filter-status" class="rounded-md border-gray-700 bg-gray-700 text-white py-2 pl-3 pr-10 focus:border-green-500 focus:ring-green-500 sm:text-sm">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="dropped">Dropped</option>
                        <option value="graduated">Graduated</option>
                        <option value="resigned">Resigned</option>
                    </select>
                    <select id="filter-type" name="filter-type" class="rounded-md border-gray-700 bg-gray-700 text-white py-2 pl-3 pr-10 focus:border-green-500 focus:ring-green-500 sm:text-sm">
                        <option value="all">All Types</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                    <select id="filter-voting-status" name="filter-voting-status" class="rounded-md border-gray-700 bg-gray-700 text-white py-2 pl-3 pr-10 focus:border-green-500 focus:ring-green-500 sm:text-sm">
                        <option value="all">All Voting Statuses</option>
                        <option value="done">Done</option>
                        <option value="not">Not Voted</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full leading-normal">
            <thead>
              <tr class="bg-gray-700">
                <th class="cursor-pointer px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider sortable" data-sort-by="fullname">
                  Full Name <i class="fas fa-sort ml-1"></i>
                </th>
                <th class="cursor-pointer px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider sortable" data-sort-by="school_id">
                  School ID <i class="fas fa-sort ml-1"></i>
                </th>
                <th class="cursor-pointer px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider sortable" data-sort-by="email">
                  Email <i class="fas fa-sort ml-1"></i>
                </th>
                <th class="cursor-pointer px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider sortable" data-sort-by="type">
                  Type <i class="fas fa-sort ml-1"></i>
                </th>
                <th class="cursor-pointer px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider sortable whitespace-nowrap" data-sort-by="voting_status">
                  Voting Status <i class="fas fa-sort ml-1"></i>
                </th>
                <th class="cursor-pointer px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider sortable" data-sort-by="status">
                  Status <i class="fas fa-sort ml-1"></i>
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody id="accounts-table-body" class="bg-white divide-y divide-gray-200">
              <?php foreach ($accounts as $account): ?>
                <?php if ($account['type'] === 'admin') continue; // Do not display admin rows ?>
                <tr data-fullname="<?php echo htmlspecialchars($account['fullname']); ?>"
                    data-type="<?php echo htmlspecialchars($account['type']); ?>"
                    data-status="<?php echo htmlspecialchars($account['status']); ?>"
                    data-voting-status="<?php echo htmlspecialchars($account['voting_status']); ?>"
                    class="hover:bg-gray-700 transition-colors duration-200" id="account-row-<?= htmlspecialchars($account['id']) ?>">
                  <td class="px-5 py-5 border-b border-gray-700 bg-gray-800 text-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-xs font-bold text-white uppercase mr-3">
                            <?= htmlspecialchars(substr($account['fullname'], 0, 1)) ?>
                        </div>
                        <p class="text-white whitespace-nowrap"><?= htmlspecialchars($account['fullname']) ?></p>
                    </div>
                  </td>
                  <td class="px-5 py-5 border-b border-gray-700 bg-gray-800 text-sm">
                    <p class="text-white whitespace-nowrap"><?= htmlspecialchars($account['school_id']) ?></p>
                  </td>
                  <td class="px-5 py-5 border-b border-gray-700 bg-gray-800 text-sm">
                    <p class="text-white whitespace-nowrap"><?= htmlspecialchars($account['email']) ?></p>
                  </td>
                  <td class="px-5 py-5 border-b border-gray-700 bg-gray-800 text-sm capitalize">
                    <p class="text-white whitespace-nowrap"><?= htmlspecialchars($account['type']) ?></p>
                  </td>
                  <td class="px-5 py-5 border-b border-gray-700 bg-gray-800 text-sm" id="voting-status-display-<?= htmlspecialchars($account['id']) ?>">
                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight">
                      <span aria-hidden="true" class="absolute inset-0 opacity-50 rounded-full
                          <?= $account['voting_status'] === 'done' ? 'bg-green-600' : 'bg-red-600' ?>"></span>
                      <span class="relative text-white capitalize"><?= htmlspecialchars($account['voting_status']) ?></span>
                    </span>
                  </td>
                  <td class="px-5 py-5 border-b border-gray-700 bg-gray-800 text-sm" id="account-status-display-<?= htmlspecialchars($account['id']) ?>">
                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight">
                      <span aria-hidden="true" class="absolute inset-0 opacity-50 rounded-full
                          <?= $account['status'] === 'active' ? 'bg-green-600' : 'bg-red-600' ?>"></span>
                      <span class="relative text-white capitalize"><?= htmlspecialchars($account['status']) ?></span>
                    </span>
                  </td>
                  <td class="px-5 py-5 border-b border-gray-700 bg-gray-800 text-sm whitespace-nowrap">
                    <select onchange="handleStatusChange(this.value, <?= htmlspecialchars($account['id']) ?>)" class="block w-full py-1 rounded-md border-gray-700 bg-gray-700 shadow-sm focus:border-green-500 focus:ring-green-500 text-white text-sm">
                      <option value="">Change Status</option>
                      <?php
                      $status_options = [];
                      if ($account['type'] === 'student') {
                          $status_options = ['active', 'inactive', 'dropped', 'graduated'];
                      } elseif ($account['type'] === 'teacher') {
                          $status_options = ['active', 'inactive', 'resigned'];
                      }

                      foreach ($status_options as $option) {
                          $selected = ($account['status'] === $option) ? 'selected' : '';
                          echo "<option value=\"{$option}\" {$selected}>" . htmlspecialchars(ucfirst($option)) . "</option>";
                      }
                      ?>
                    </select>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Candidates Content -->
      <div id="candidatesContent" class="hidden bg-gray-800 rounded-lg shadow-xl sm:p-8 p-2 h-full">
        
        <div class="mb-6 p-2 border-b border-gray-600">
          <h3 class="text-2xl font-bold text-white">Election Management</h3>
          <h4 class="text-sm font-500 text-white w-full mb-2">Candidate & Election Configurations</h4>
        </div>

        <!-- All Management Buttons -->
        <div class="flex flex-wrap gap-4 mb-8 rounded-lg shadow-md w-full items-center justify-around">
            <button id="addCandidateBtn" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-user-plus py-2"></i> <span class="hidden sm:block">Add New Candidate</span>
            </button>
            <button id="viewLeadingCandidatesBtn" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fa fa-shuffle py-2"></i> <span class="hidden sm:block">View Leading Candidates</span>
            </button>
            <button id="managePositionsBtn" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-cogs py-2"></i> <span class="hidden sm:block">Manage Positions</span>
            </button>
            <button id="managePartylistsBtn" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-users py-2"></i> <span class="hidden sm:block">Manage Candidate Partylists</span>
            </button>
            <button id="manageSchoolYearsBtn" class="flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                <i class="fas fa-graduation-cap py-2"></i> <span class="hidden sm:block">Manage Students School Years</span>
            </button>
        </div>


        <!-- Candidates Table -->
        <div class="p-6 bg-gray-700 rounded-lg shadow-md">
            <h4 class="text-xl font-semibold text-white mb-4">Candidates List</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider whitespace-nowrap">Candidate</th>
                            <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-widerv whitespace-nowrap">Position</th>
                            <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider whitespace-nowrap">School Year</th>
                            <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider whitespace-nowrap">Election Year</th>
                            <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider whitespace-nowrap">Partylist</th>
                            <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider whitespace-nowrap">Votes</th>
                            <th class="px-5 py-3 border-b-2 border-gray-600 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="candidates-table-body" class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($candidates)): // Changed to always check, as JS will re-render anyway ?>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr id="candidate-row-<?= htmlspecialchars($candidate['id']) ?>"
                                    data-name="<?= htmlspecialchars($candidate['name']) ?>"
                                    data-position-id="<?= htmlspecialchars($candidate['position_id']) ?>"
                                    data-image="<?= htmlspecialchars($candidate['image'] ?? '') ?>"
                                    data-school-year-id="<?= htmlspecialchars($candidate['school_year_id']) ?>"
                                    data-election-year="<?= htmlspecialchars($candidate['election_year']) ?>"
                                    data-partylist-id="<?= htmlspecialchars($candidate['partylist_id']) ?>"
                                    data-vote-count="<?= htmlspecialchars($candidate['vote_count']) ?>"
                                    class="hover:bg-gray-600 transition-colors duration-200 bg-gray-700">
                                    <td class="px-5 py-5 border-b border-gray-600 text-sm text-white">
                                        <div class="flex items-center">
                                            <img src="<?= htmlspecialchars($candidate['image'] ?? 'https://placehold.co/50x50/CCCCCC/000000?text=' . substr($candidate['name'], 0, 1)) ?>" onerror="this.onerror=null;this.src='https://placehold.co/50x50/CCCCCC/000000?text=' + '<?= substr($candidate['name'], 0, 1) ?>';" alt="Candidate Image" class="h-10 w-10 rounded-full object-cover mr-3">
                                            <p class="text-white whitespace-nowrap"><?= htmlspecialchars($candidate['name']) ?></p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-600 text-sm text-white whitespace-nowrap" data-field="position_name"><?= htmlspecialchars($candidate['position_name'] ?? 'N/A') ?></td>
                                    <td class="px-5 py-5 border-b border-gray-600 text-sm text-white whitespace-nowrap" data-field="school_year_name"><?= htmlspecialchars($candidate['school_year_name'] ?? 'N/A') ?></td>
                                    <td class="px-5 py-5 border-b border-gray-600 text-sm text-white whitespace-nowrap" data-field="election_year"><?= htmlspecialchars($candidate['election_year']) ?></td>
                                    <td class="px-5 py-5 border-b border-gray-600 text-sm text-white whitespace-nowrap" data-field="partylist_name"><?= htmlspecialchars($candidate['partylist_name'] ?? 'N/A') ?></td>
                                    <td class="px-5 py-5 border-b border-gray-600 text-sm text-white whitespace-nowrap" data-field="vote_count"><?= htmlspecialchars($candidate['vote_count']) ?></td>
                                    <td class="px-5 py-5 border-b border-gray-600 text-sm text-center whitespace-nowrap flex items-center w-full">
                                        <button onclick="editCandidate(<?= htmlspecialchars($candidate['id']) ?>)" class="text-yellow-500 hover:text-yellow-400 mx-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteCandidate(<?= htmlspecialchars($candidate['id']) ?>)" class="text-red-500 hover:text-red-400 mx-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="px-5 py-5 border-b border-gray-600 bg-gray-700 text-sm text-center text-gray-400">No candidates found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>

      <!-- Reports Content -->
      <div id="reportsContent" class="hidden bg-gray-800 rounded-lg shadow-xl p-8 h-full">
        <h3 class="text-2xl font-bold text-white mb-6">Voting Reports</h3>
        <p class="text-gray-400">Voting reports content will be displayed here.</p>
      </div>

    </div>
  </div>

  <!-- Position Management Modal -->
  <div id="positionManagementModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-lg w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="positionManagementModalTitle">
          <button class="modal-close-button" onclick="hideModal(positionManagementModal)" aria-label="Close position management modal">&times;</button>
          <h3 id="positionManagementModalTitle" class="text-xl font-semibold text-white mb-4 py-2" style="border-bottom: 1px solid #ededed25">Manage Positions</h3>
          <button id="addPositionModalBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition mb-4">
              <i class="fas fa-plus mr-2"></i> Add New Position
          </button>
          <div class="overflow-x-auto max-h-60">
              <table class="min-w-full leading-normal">
                  <thead>
                      <tr class="bg-gray-700">
                          <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Position Name</th>
                          <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                      </tr>
                  </thead>
                  <tbody id="positions-modal-table-body" class="bg-white divide-y divide-gray-200">
                      <!-- Positions will be loaded here by JavaScript -->
                  </tbody>
              </table>
          </div>
      </div>
  </div>

  <!-- Position Add/Edit Modal (triggered from PositionManagementModal) -->
  <div id="positionModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-sm w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="positionModalTitle">
          <button class="modal-close-button" onclick="closePositionAddEditModal()" aria-label="Close add/edit position modal">&times;</button>
          <h3 id="positionModalTitle" class="text-xl font-semibold text-white mb-4 py-2" style="border-bottom: 1px solid #ededed25">Add New Position</h3>
          <form id="positionForm" class="space-y-4">
              <input type="hidden" id="positionId" name="id">
              <div>
                  <label for="positionName" class="block text-gray-300 text-sm font-bold mb-2">Position Name:</label>
                  <input type="text" id="positionName" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-white leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
              </div>
              <div class="flex justify-end space-x-4">
                  <button type="button" id="cancelPositionModal" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Cancel</button>
                  <button type="submit" id="savePositionBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Save Position</button>
              </div>
          </form>
      </div>
  </div>

  <!-- Partylist Management Modal -->
  <div id="partylistManagementModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-lg w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="partylistManagementModalTitle">
          <button class="modal-close-button" onclick="hideModal(partylistManagementModal)" aria-label="Close partylist management modal">&times;</button>
          <h3 id="partylistManagementModalTitle" class="text-xl font-semibold text-white mb-4 py-2" style="border-bottom: 1px solid #ededed25">Manage Partylists</h3>
          <button id="addPartylistModalBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition mb-4">
              <i class="fas fa-plus mr-2"></i> Add New Partylist
          </button>
          <div class="overflow-x-auto max-h-60">
              <table class="min-w-full leading-normal">
                  <thead>
                      <tr class="bg-gray-700">
                          <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Partylist Name</th>
                          <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                      </tr>
                  </thead>
                  <tbody id="partylists-modal-table-body" class="bg-white divide-y divide-gray-200">
                      <!-- Partylists will be loaded here by JavaScript -->
                  </tbody>
              </table>
          </div>
      </div>
  </div>

  <!-- Partylist Add/Edit Modal -->
  <div id="partylistModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-sm w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="partylistModalTitle">
          <button class="modal-close-button" onclick="closePartylistAddEditModal()" aria-label="Close add/edit partylist modal">&times;</button>
          <h3 id="partylistModalTitle" class="text-xl font-semibold text-white mb-4">Add New Partylist</h3>
          <form id="partylistForm" class="space-y-4">
              <input type="hidden" id="partylistId" name="id">
              <div>
                  <label for="partylistName" class="block text-gray-300 text-sm font-bold mb-2">Partylist Name:</label>
                  <input type="text" id="partylistName" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
              </div>
              <div class="flex justify-end space-x-4">
                  <button type="button" id="cancelPartylistModal" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Cancel</button>
                  <button type="submit" id="savePartylistBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Save Partylist</button>
              </div>
          </form>
      </div>
  </div>

  <!-- School Year Management Modal -->
  <div id="schoolYearManagementModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-lg w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="schoolYearManagementModalTitle">
          <button class="modal-close-button" onclick="hideModal(schoolYearManagementModal)" aria-label="Close school year management modal">&times;</button>
          <h3 id="schoolYearManagementModalTitle" class="text-xl font-semibold text-white mb-4 py-2" style="border-bottom: 1px solid #ededed25">Manage School Years</h3>
          <button id="addSchoolYearModalBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition mb-4">
              <i class="fas fa-plus mr-2"></i> Add New School Year
          </button>
          <div class="overflow-x-auto max-h-60">
              <table class="min-w-full leading-normal">
                  <thead>
                      <tr class="bg-gray-700">
                          <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">School Year Name</th>
                          <th class="px-5 py-3 border-b-2 border-gray-600 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                      </tr>
                  </thead>
                  <tbody id="school-years-modal-table-body" class="bg-white divide-y divide-gray-200">
                      <!-- School Years will be loaded here by JavaScript -->
                  </tbody>
              </table>
          </div>
      </div>
  </div>

  <!-- School Year Add/Edit Modal -->
  <div id="schoolYearModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-sm w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="schoolYearModalTitle">
          <button class="modal-close-button" onclick="closeSchoolYearAddEditModal()" aria-label="Close add/edit school year modal">&times;</button>
          <h3 id="schoolYearModalTitle" class="text-xl font-semibold text-white mb-4 py-2" style="border-bottom: 1px solid #ededed25">Add New School Year</h3>
          <form id="schoolYearForm" class="space-y-4">
              <input type="hidden" id="schoolYearId" name="id">
              <div>
                  <label for="schoolYearName" class="block text-gray-300 text-sm font-bold mb-2">School Year Name:</label>
                  <input type="text" id="schoolYearName" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-white leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
              </div>
              <div class="flex justify-end space-x-4">
                  <button type="button" id="cancelSchoolYearModal" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Cancel</button>
                  <button type="submit" id="saveSchoolYearBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Save School Year</button>
              </div>
          </form>
      </div>
  </div>


  <!-- Candidate Add/Edit Modal -->
  <div id="candidateModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-lg w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="candidateModalTitle">
          <button class="modal-close-button" onclick="closeCandidateModal()" aria-label="Close add/edit candidate modal">&times;</button>
          <h3 id="candidateModalTitle" class="text-xl font-semibold text-white mb-4 py-2" style="border-bottom: 1px solid #ededed25">Add New Candidate</h3>
          <form id="candidateForm" class="space-y-4">
              <input type="hidden" id="candidateId" name="id">
              <input type="hidden" id="candidateImageBase64" name="image_base64"> <!-- Hidden input for Base64 image data -->
              
              <!-- Image Upload and Preview -->
              <div class="mb-4 text-center p-4 border border-gray-600 rounded-lg bg-gray-700">
                  <label for="candidateImageFile" class="block text-gray-300 text-sm font-bold mb-2">Candidate Image:</label>
                  <div class="candidate-image-preview-container mb-3 border-2 border-gray-500 rounded-lg overflow-hidden mx-auto max-w-xs">
                    <img id="candidateImagePreview" class="object-cover" src="https://placehold.co/100x100/CCCCCC/000000?text=No+Image" alt="Image Preview">
                  </div>
                  <input type="file" id="candidateImageFile" name="image_file" accept="image/*" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-300 leading-tight focus:outline-none focus:shadow-outline bg-gray-800 border-gray-600">
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                      <label for="candidateName" class="block text-gray-300 text-sm font-bold mb-2">Candidate Name:</label>
                      <input type="text" id="candidateName" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
                  </div>
                  <div>
                      <label for="candidatePosition" class="block text-gray-300 text-sm font-bold mb-2">Position:</label>
                      <select id="candidatePosition" name="position_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
                          <option value="">Select Position</option>                      
                      </select>
                  </div>
                  <div>
                      <label for="candidateSchoolYear" class="block text-gray-300 text-sm font-bold mb-2">School Year:</label>
                      <select id="candidateSchoolYear" name="school_year_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
                          <option value="">Select School Year</option>                      
                      </select>
                  </div>
                  <div>
                      <label for="candidateElectionYear" class="block text-gray-300 text-sm font-bold mb-2">Election Year:</label>
                      <input id="candidateElectionYear" name="election_year" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
                  </div>
              </div>
              <div>
                  <label for="candidatePartylist" class="block text-gray-300 text-sm font-bold mb-2">Partylist:</label>
                  <select id="candidatePartylist" name="partylist_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-700 border-gray-600 text-white" required>
                      <option value="">Select Partylist</option>                      
                  </select>
              </div>
              <div class="flex justify-end space-x-4 mt-6">
                  <button type="button" id="cancelCandidateModal" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Cancel</button>
                  <button type="submit" id="saveCandidateBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Save Candidate</button>
              </div>
          </form>
      </div>
  </div>

  <!-- Leading Candidates Modal -->
  <div id="leadingCandidatesModal" class="fixed inset-0 hidden items-center justify-center modal-overlay p-4 backdrop-blur">
      <div class="bg-gray-800 rounded-lg p-6 max-w-xl w-full modal-content shadow-lg" role="dialog" aria-modal="true" aria-labelledby="leadingCandidatesModalTitle">
          <button class="modal-close-button" onclick="hideModal(leadingCandidatesModal)" aria-label="Close leading candidates modal">&times;</button>
          <h3 id="leadingCandidatesModalTitle" class="text-xl font-semibold text-white mb-4 py-2" style="border-bottom: 1px solid #ededed25">Leading Candidates Per Position</h3>
          <div id="leadingCandidatesContent" class="space-y-6">
              <!-- Leading candidates will be rendered here by JavaScript -->
          </div>
      </div>
  </div>


  <script>
    // PHP variables passed to JavaScript
    // These will be overridden by fetchAllCandidateRelatedData, but kept for initial non-AJAX page load data.
    let ACCOUNT_TYPE_COUNTS = <?= json_encode($account_type_counts) ?>; 
    let ALL_POSITIONS_DATA = <?= json_encode($positions) ?>; 
    let ALL_CANDIDATES_DATA = <?= json_encode($candidates) ?>; 
    let ALL_PARTYLISTS_DATA = <?= json_encode($partylists) ?>; 
    let ALL_SCHOOL_YEARS_DATA = <?= json_encode($school_years) ?>; 

    // Dashboard count elements for dynamic update
    const dashboardTotalAccounts = document.getElementById('dashboard-total-accounts');
    const dashboardTotalVoted = document.getElementById('dashboard-total-voted');
    const dashboardTotalCandidates = document.getElementById('dashboard-total-candidates');
    const dashboardVoterTurnout = document.getElementById('dashboard-voter-turnout');
    const votingProgressBar = document.getElementById('voting-progress-bar');
    const votingProgressText = document.getElementById('voting-progress-text');


    // Get all the necessary elements from the DOM
    const logoutModal = document.getElementById('logoutModal');
    const profileModal = document.getElementById('profileModal');
    const profileBtn = document.getElementById('profileBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    const cancelLogoutBtn = document.getElementById('cancelLogout');

    // Get content and navigation elements
    const dashboardBtn = document.getElementById('dashboardBtn');
    const accountsBtn = document.getElementById('accountsBtn');
    const candidatesBtn = document.getElementById('candidatesBtn');
    const reportsBtn = document.getElementById('reportsBtn');
    const dashboardContent = document.getElementById('dashboardContent');
    const accountsContent = document.getElementById('accountsContent');
    const candidatesContent = document.getElementById('candidatesContent');
    const reportsContent = document.getElementById('reportsContent');
    const navButtons = document.querySelectorAll('.nav-btn'); // Get all nav buttons


    // Accounts page filters/sorts
    const searchInput = document.getElementById('search');
    const filterStatusSelect = document.getElementById('filter-status');
    const filterTypeSelect = document.getElementById('filter-type');
    const filterVotingStatusSelect = document.getElementById('filter-voting-status');
    const accountsTableBody = document.getElementById('accounts-table-body');
    const filterToggleButton = document.getElementById('filterToggleButton');
    const filterControlsContainer = document.getElementById('filterControlsContainer');

    // Candidates page elements
    const candidatesTableBody = document.getElementById('candidates-table-body');
    const addCandidateBtn = document.getElementById('addCandidateBtn');
    const viewLeadingCandidatesBtn = document.getElementById('viewLeadingCandidatesBtn');
    const managePositionsBtn = document.getElementById('managePositionsBtn'); // New reference
    const managePartylistsBtn = document.getElementById('managePartylistsBtn'); // New reference
    const manageSchoolYearsBtn = document.getElementById('manageSchoolYearsBtn'); // New reference


    // Modals
    // Position Management Modals
    const positionManagementModal = document.getElementById('positionManagementModal');
    const positionsModalTableBody = document.getElementById('positions-modal-table-body');
    const addPositionModalBtn = document.getElementById('addPositionModalBtn');

    const positionModal = document.getElementById('positionModal');
    const positionModalTitle = document.getElementById('positionModalTitle');
    const positionForm = document.getElementById('positionForm');
    const positionIdInput = document.getElementById('positionId');
    const positionNameInput = document.getElementById('positionName');
    const cancelPositionModalBtn = document.getElementById('cancelPositionModal');

    // Partylist Management Modals
    const partylistManagementModal = document.getElementById('partylistManagementModal');
    const partylistsModalTableBody = document.getElementById('partylists-modal-table-body');
    const addPartylistModalBtn = document.getElementById('addPartylistModalBtn');

    const partylistModal = document.getElementById('partylistModal');
    const partylistModalTitle = document.getElementById('partylistModalTitle');
    const partylistForm = document.getElementById('partylistForm');
    const partylistIdInput = document.getElementById('partylistId');
    const partylistNameInput = document.getElementById('partylistName');
    const cancelPartylistModalBtn = document.getElementById('cancelPartylistModal');

    // School Year Management Modals
    const schoolYearManagementModal = document.getElementById('schoolYearManagementModal');
    const schoolYearsModalTableBody = document.getElementById('school-years-modal-table-body');
    const addSchoolYearModalBtn = document.getElementById('addSchoolYearModalBtn');

    const schoolYearModal = document.getElementById('schoolYearModal');
    const schoolYearModalTitle = document.getElementById('schoolYearModalTitle');
    const schoolYearForm = document.getElementById('schoolYearForm');
    const schoolYearIdInput = document.getElementById('schoolYearId');
    const schoolYearNameInput = document.getElementById('schoolYearName');
    const cancelSchoolYearModalBtn = document.getElementById('cancelSchoolYearModal');

    // Candidate Modals
    const candidateModal = document.getElementById('candidateModal');
    const candidateModalTitle = document.getElementById('candidateModalTitle');
    const candidateForm = document.getElementById('candidateForm');
    const candidateIdInput = document.getElementById('candidateId');
    const candidateNameInput = document.getElementById('candidateName');
    const candidatePositionSelect = document.getElementById('candidatePosition');
    const candidateImageFileInput = document.getElementById('candidateImageFile');
    const candidateImagePreview = document.getElementById('candidateImagePreview');
    const candidateImageBase64Input = document.getElementById('candidateImageBase64');
    const candidateSchoolYearSelect = document.getElementById('candidateSchoolYear'); // Changed
    const candidateElectionYearInput = document.getElementById('candidateElectionYear');
    const candidatePartylistSelect = document.getElementById('candidatePartylist'); // Changed
    const cancelCandidateModalBtn = document.getElementById('cancelCandidateModal');

    // Leading Candidates Modal
    const leadingCandidatesModal = document.getElementById('leadingCandidatesModal');
    const leadingCandidatesContent = document.getElementById('leadingCandidatesContent');

    // Toast Notification elements
    const toastContainer = document.getElementById('toastContainer');

    // Decision Modal elements
    const decisionModal = document.getElementById('decisionModal');
    const decisionModalTitle = document.getElementById('decisionModalTitle');
    const decisionModalMessage = document.getElementById('decisionModalMessage');
    const decisionModalActions = document.getElementById('decisionModalActions');


    // --- General UI Functions ---

    function showModal(modalElement) {
      modalElement.classList.remove('hidden');
      modalElement.classList.add('flex');
    }

    function hideModal(modalElement) {
      modalElement.classList.add('hidden');
      modalElement.classList.remove('flex');
    }

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
        }, 300);
      }
    }

    // Displays a toast notification (success/error)
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;

        // Choose icon based on type
        let icon = '';
        if (type === 'success') {
            icon = '🎉'; // celebratory icon
        } else if (type === 'error') {
            icon = '⚠️'; // warning/alert icon
        } else {
            icon = 'ℹ️'; // info icon (default fallback)
        }

        // Build toast content with icon + message
        toast.innerHTML = `<span class="mr-2">${icon}</span> ${message}`;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 10); // Small delay to trigger transition

        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 3000); // Hide after 3 seconds
    }


    // Shows a central decision modal for confirmations/alerts
    function showDecisionModal(title, message, buttons) {
        decisionModalTitle.textContent = title;
        decisionModalMessage.textContent = message;
        decisionModalActions.innerHTML = ''; // Clear previous buttons

        buttons.forEach(button => {
            const btnElement = document.createElement('button');
            btnElement.textContent = button.text;
            btnElement.className = button.className || 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition';
            btnElement.onclick = () => {
                hideModal(decisionModal);
                if (button.callback) {
                    button.callback();
                }
            };
            decisionModalActions.appendChild(btnElement);
        });
        showModal(decisionModal);
    }

    function showContent(contentToShow, buttonToHighlight) {
      const allContent = [dashboardContent, accountsContent, candidatesContent, reportsContent];
      allContent.forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('fade-in');
      });

      contentToShow.classList.remove('hidden');
      contentToShow.classList.add('fade-in');

      navButtons.forEach(btn => {
        const contentId = btn.dataset.content;
        // Reset all buttons to gray
        btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'bg-indigo-600', 'hover:bg-indigo-700', 'bg-purple-600', 'hover:bg-purple-700', 'bg-yellow-600', 'hover:bg-yellow-700');
        btn.classList.add('bg-gray-600');
      });
      
      // Highlight the active button
      if(buttonToHighlight.id === 'dashboardBtn') {
        buttonToHighlight.classList.remove('bg-gray-600');
        buttonToHighlight.classList.add('bg-green-600', 'hover:bg-green-700');
        drawAccountTypePieChart();
      } else if (buttonToHighlight.id === 'accountsBtn') {
        buttonToHighlight.classList.remove('bg-gray-600');
        buttonToHighlight.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
      } else if (buttonToHighlight.id === 'candidatesBtn') {
        buttonToHighlight.classList.remove('bg-gray-600');
        buttonToHighlight.classList.add('bg-purple-600', 'hover:bg-purple-700');
      } else if (buttonToHighlight.id === 'reportsBtn') {
        buttonToHighlight.classList.remove('bg-gray-600');
        buttonToHighlight.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
      }

      // Update URL to persist active tab
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.set('content', buttonToHighlight.dataset.content);
      window.history.pushState(null, '', `?${urlParams.toString()}`);
    }


    // --- Accounts Page Specific Functions ---

    function filterAccountsTable() {
        const searchText = searchInput.value.toLowerCase();
        const selectedStatus = filterStatusSelect.value;
        const selectedType = filterTypeSelect.value;
        const selectedVotingStatus = filterVotingStatusSelect.value;

        const allTableRows = accountsTableBody.querySelectorAll('tr');

        allTableRows.forEach(row => {
            const fullname = row.dataset.fullname.toLowerCase();
            const type = row.dataset.type;
            const status = row.dataset.status;
            const votingStatus = row.dataset.votingStatus;

            const nameMatch = fullname.includes(searchText);
            const statusMatch = selectedStatus === 'all' || status === selectedStatus;
            const typeMatch = selectedType === 'all' || type === selectedType;
            const votingStatusMatch = selectedVotingStatus === 'all' || votingStatus === selectedVotingStatus;

            if (nameMatch && statusMatch && typeMatch && votingStatusMatch) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });
    }

    function sortAccountsTable(sortBy, sortOrder) {
      const rows = Array.from(accountsTableBody.querySelectorAll('tr'));

      rows.sort((a, b) => {
        let valA, valB;

        if (sortBy === 'fullname') {
          valA = a.querySelector('td:nth-child(1) p').textContent.trim().toLowerCase();
          valB = b.querySelector('td:nth-child(1) p').textContent.trim().toLowerCase();
        } else if (sortBy === 'school_id') {
          valA = a.querySelector('td:nth-child(2) p').textContent.trim().toLowerCase();
          valB = b.querySelector('td:nth-child(2) p').textContent.trim().toLowerCase();
        } else if (sortBy === 'email') {
          valA = a.querySelector('td:nth-child(3) p').textContent.trim().toLowerCase();
          valB = b.querySelector('td:nth-child(3) p').textContent.trim().toLowerCase();
        } else if (sortBy === 'type') {
          valA = a.querySelector('td:nth-child(4) p').textContent.trim().toLowerCase();
          valB = b.querySelector('td:nth-child(4) p').textContent.trim().toLowerCase();
        } else if (sortBy === 'voting_status') {
          valA = a.querySelector('td:nth-child(5) span.relative:last-child').textContent.trim().toLowerCase();
          valB = b.querySelector('td:nth-child(5) span.relative:last-child').textContent.trim().toLowerCase();
        } else if (sortBy === 'status') {
          valA = a.querySelector('td:nth-child(6) span.relative:last-child').textContent.trim().toLowerCase();
          valB = b.querySelector('td:nth-child(6) span.relative:last-child').textContent.trim().toLowerCase();
        } else {
            valA = parseInt(a.id.replace('account-row-', ''));
            valB = parseInt(b.id.replace('account-row-', ''));
        }
        
        if (!isNaN(valA) && !isNaN(valB)) {
            valA = parseFloat(valA);
            valB = parseFloat(valB);
        }

        let comparison = 0;
        if (valA > valB) {
          comparison = 1;
        } else if (valA < valB) {
          comparison = -1;
        }

        return sortOrder === 'desc' ? comparison * -1 : comparison;
      });

      while (accountsTableBody.firstChild) {
        accountsTableBody.removeChild(accountsTableBody.firstChild);
      }
      rows.forEach(row => accountsTableBody.appendChild(row));
    }

    async function handleStatusChange(newStatus, accountId) {
      if (!newStatus) return;

      showDecisionModal('Confirm Status Change', `Are you sure you want to change the status to "${newStatus}"?`, [
          { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
          { text: 'Confirm', className: 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition', callback: async () => {
              try {
                const response = await fetch(window.location.href, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: `action=update_status&account_id=${accountId}&new_status=${newStatus}`
                });
                const result = await response.json();

                if (result.success) {
                  const statusDisplayCell = document.getElementById(`account-status-display-${accountId}`);
                  if (statusDisplayCell) {
                    const statusSpan = statusDisplayCell.querySelector('span:last-child');
                    const statusBg = statusDisplayCell.querySelector('span:first-child');
                    
                    statusSpan.textContent = newStatus;
                    
                    const row = document.getElementById(`account-row-${accountId}`);
                    if (row) {
                        row.dataset.status = newStatus;
                    }

                    statusBg.classList.remove('bg-green-600', 'bg-red-600');
                    if (newStatus === 'active') {
                      statusBg.classList.add('bg-green-600');
                    } else {
                      statusBg.classList.add('bg-red-600');
                    }
                  }
                  showToast('Status updated successfully.', 'success');
                  filterAccountsTable();
                  // Stay on the current page after action
                  const urlParams = new URLSearchParams(window.location.search);
                  urlParams.set('content', 'accounts');
                  window.history.pushState(null, '', `?${urlParams.toString()}`);
                } else {
                  showToast('Failed to update status: ' + result.message, 'error');
                }
              } catch (error) {
                console.error('Error during fetch:', error);
                showToast('An error occurred while updating status.', 'error');
              }
          }}
      ]);
    }


    // --- Dashboard Specific Functions ---

    function updateDashboardUI(dashboardData, accountTypeCounts) {
        dashboardTotalAccounts.textContent = dashboardData.total_accounts;
        dashboardTotalVoted.textContent = dashboardData.total_voted;
        dashboardTotalCandidates.textContent = dashboardData.total_candidates;
        dashboardVoterTurnout.textContent = `${dashboardData.voter_turnout}%`;
        votingProgressBar.style.width = `${dashboardData.voter_turnout}%`;
        votingProgressText.textContent = `${dashboardData.voter_turnout}% of eligible voters have cast their ballot`;
        ACCOUNT_TYPE_COUNTS = accountTypeCounts; // Update global variable
        drawAccountTypePieChart();
    }

    function drawAccountTypePieChart() {
        const canvas = document.getElementById('accountTypePieChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const data = Object.entries(ACCOUNT_TYPE_COUNTS).map(([type, count]) => ({
            label: type,
            value: count
        }));

        const colors = {
            'student': '#34D399', // Green
            'teacher': '#60A5FA', // Blue
            'admin': '#FBBF24'   // Yellow
        };

        const total = data.reduce((sum, item) => sum + item.value, 0);
        let startAngle = 0;
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(canvas.width, canvas.height) / 2; // Use min for full circle, fixed from earlier issue

        ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear previous drawing

        // Sort data so the largest slice is first for better visual
        data.sort((a, b) => b.value - a.value);

        data.forEach(item => {
            const sliceAngle = (item.value / total) * 2 * Math.PI;
            ctx.fillStyle = colors[item.label] || '#9CA3AF'; // Fallback color
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
            ctx.closePath();
            ctx.fill();

            // Draw border for separation
            ctx.strokeStyle = '#1f2937'; // Background color of the card for subtle separation
            ctx.lineWidth = 2; // Thinner line for separation
            ctx.stroke();

            startAngle += sliceAngle;
        });

        const legendContainer = document.getElementById('pie-chart-legend');
        legendContainer.innerHTML = '';
        const ul = document.createElement('ul');
        data.forEach(item => {
            const li = document.createElement('li');
            const percentage = total > 0 ? (item.value / total) * 100 : 0;
            li.innerHTML = `<span class="color-box" style="background-color: ${colors[item.label] || '#9CA3AF'};"></span>
                            <span class="capitalize">${item.label}: ${item.value} (${percentage.toFixed(0)}%)</span>`;
            ul.appendChild(li);
        });
        legendContainer.appendChild(ul);
    }


    // --- Candidates Page Specific Functions (CRUD Operations) ---

    // === Fetch All Data for Candidates Tab (Positions, Partylists, School Years, Candidates, Dashboard) ===
    async function fetchAllCandidateRelatedData() {
        try {
            const response = await fetch(window.location.href, {
                method: 'POST', // Use POST to hit the AJAX handler
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=fetch_all_data` // A dummy action to trigger re-fetch of all related data
            });
            const result = await response.json();
            if (result.success && result.data) {
                // Update the global JS variables
                ALL_CANDIDATES_DATA = result.data.candidates;
                ALL_POSITIONS_DATA = result.data.positions;
                ALL_PARTYLISTS_DATA = result.data.partylists;
                ALL_SCHOOL_YEARS_DATA = result.data.school_years;
                // Update dashboard UI if on dashboard
                if (document.getElementById('dashboardContent').classList.contains('fade-in')) {
                    updateDashboardUI(result.data.dashboard_counts, result.data.account_type_counts);
                }
                return true;
            } else {
                showToast('Failed to fetch updated data: ' + result.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('Error fetching updated data:', error);
            showToast('An error occurred while fetching data.', 'error');
            return false;
        }
    }


    // === Positions CRUD ===
    async function renderPositionsManagementTable() {
        positionsModalTableBody.innerHTML = ''; // Clear existing rows
        const positions = ALL_POSITIONS_DATA; // Use already fetched global data

        if (positions.length === 0) {
            positionsModalTableBody.innerHTML = '<tr><td colspan="2" class="px-5 py-5 border-b border-gray-600 bg-gray-700 text-sm text-center text-gray-400">No positions found.</td></tr>';
            return;
        }

        positions.forEach(position => {
            const row = document.createElement('tr');
            row.id = `position-row-${position.id}`;
            row.className = 'hover:bg-gray-600 transition-colors duration-200 bg-gray-700';
            row.innerHTML = `
                <td class="px-5 py-5 text-sm text-white" data-field="name">${htmlspecialchars(position.name)}</td>
                <td class="px-5 py-5 text-sm flex gap-2">
                    <button onclick="editPosition(${position.id}, '${htmlspecialchars(position.name)}')" class="px-3 py-1 text-yellow-300 rounded-md hover:text-yellow-400 transition mr-2">
                        Edit
                    </button>
                    <button onclick="deletePosition(${position.id})" class="px-3 py-1 text-red-500 rounded-md hover:text-red-700 transition">
                        Delete
                    </button>
                </td>
            `;
            positionsModalTableBody.appendChild(row);
        });
    }

    function openPositionAddEditModal(id = '', name = '') {
        hideModal(positionManagementModal); // Hide management modal
        showModal(positionModal); // Show add/edit modal
        positionIdInput.value = id;
        positionNameInput.value = name;
        positionModalTitle.textContent = id ? 'Edit Position' : 'Add New Position';
    }

    function closePositionAddEditModal() {
        hideModal(positionModal);
        positionForm.reset();
        showModal(positionManagementModal); // Show management modal again
    }

    async function addOrUpdatePosition(event) {
        event.preventDefault();
        const id = positionIdInput.value;
        const name = positionNameInput.value;
        const action = id ? 'edit_position' : 'add_position';

        showDecisionModal('Confirm Action', `Are you sure you want to ${id ? 'update' : 'add'} this position?`, [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Confirm', className: 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=${action}&id=${id}&name=${name}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderPositionsManagementTable(); // Re-render positions table
                        renderCandidatesTable(); // Re-render candidates table
                        closePositionAddEditModal();
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while saving the position.', 'error');
                }
            }}
        ]);
    }

    function editPosition(id, name) {
        openPositionAddEditModal(id, name);
    }

    async function deletePosition(id) {
        showDecisionModal('Confirm Deletion', 'Are you sure you want to delete this position? This will also delete all associated candidates.', [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Delete', className: 'px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete_position&id=${id}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderPositionsManagementTable(); // Re-render positions table
                        renderCandidatesTable(); // Re-render candidates table
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while deleting the position.', 'error');
                }
            }}
        ]);
    }


    // === Partylists CRUD ===
    async function renderPartylistsManagementTable() {
        partylistsModalTableBody.innerHTML = '';
        const partylists = ALL_PARTYLISTS_DATA;

        if (partylists.length === 0) {
            partylistsModalTableBody.innerHTML = '<tr><td colspan="2" class="px-5 py-5 border-b border-gray-600 bg-gray-700 text-sm text-center text-gray-400">No partylists found.</td></tr>';
            return;
        }

        partylists.forEach(partylist => {
            const row = document.createElement('tr');
            row.id = `partylist-row-${partylist.id}`;
            row.className = 'hover:bg-gray-600 transition-colors duration-200 bg-gray-700';
            row.innerHTML = `
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white" data-field="name">${htmlspecialchars(partylist.name)}</td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm">
                    <button onclick="editPartylist(${partylist.id}, '${htmlspecialchars(partylist.name)}')" class="px-3 py-1 text-yellow-300 text-white rounded-md hover:text-yellow-700 transition mr-2">
                        Edit
                    </button>
                    <button onclick="deletePartylist(${partylist.id})" class="px-3 py-1 text-red-600 text-white rounded-md hover:text-red-700 transition">
                        Delete
                    </button>
                </td>
            `;
            partylistsModalTableBody.appendChild(row);
        });
    }

    function openPartylistAddEditModal(id = '', name = '') {
        hideModal(partylistManagementModal);
        showModal(partylistModal);
        partylistIdInput.value = id;
        partylistNameInput.value = name;
        partylistModalTitle.textContent = id ? 'Edit Partylist' : 'Add New Partylist';
    }

    function closePartylistAddEditModal() {
        hideModal(partylistModal);
        partylistForm.reset();
        showModal(partylistManagementModal);
    }

    async function addOrUpdatePartylist(event) {
        event.preventDefault();
        const id = partylistIdInput.value;
        const name = partylistNameInput.value;
        const action = id ? 'edit_partylist' : 'add_partylist';

        showDecisionModal('Confirm Action', `Are you sure you want to ${id ? 'update' : 'add'} this partylist?`, [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Confirm', className: 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=${action}&id=${id}&name=${name}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderPartylistsManagementTable(); // Re-render partylists table
                        renderCandidatesTable(); // Re-render candidates table
                        closePartylistAddEditModal();
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while saving the partylist.', 'error');
                }
            }}
        ]);
    }

    function editPartylist(id, name) {
        openPartylistAddEditModal(id, name);
    }

    async function deletePartylist(id) {
        showDecisionModal('Confirm Deletion', 'Are you sure you want to delete this partylist? This will also affect candidates associated with it.', [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Delete', className: 'px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete_partylist&id=${id}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderPartylistsManagementTable(); // Re-render partylists table
                        renderCandidatesTable(); // Re-render candidates table
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while deleting the partylist.', 'error');
                }
            }}
        ]);
    }

    // === School Years CRUD ===
    async function renderSchoolYearsManagementTable() {
        schoolYearsModalTableBody.innerHTML = '';
        const schoolYears = ALL_SCHOOL_YEARS_DATA;

        if (schoolYears.length === 0) {
            schoolYearsModalTableBody.innerHTML = '<tr><td colspan="2" class="px-5 py-5 border-b border-gray-600 bg-gray-700 text-sm text-center text-gray-400">No school years found.</td></tr>';
            return;
        }

        schoolYears.forEach(sy => {
            const row = document.createElement('tr');
            row.id = `school-year-row-${sy.id}`;
            row.className = 'hover:bg-gray-600 transition-colors duration-200 bg-gray-700';
            row.innerHTML = `
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white" data-field="name">${htmlspecialchars(sy.name)}</td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm">
                    <button onclick="editSchoolYear(${sy.id}, '${htmlspecialchars(sy.name)}')" class="px-3 py-1 text-yellow-300 rounded-md hover:text-yellow-700 transition mr-2">
                        Edit
                    </button>
                    <button onclick="deleteSchoolYear(${sy.id})" class="px-3 py-1 text-red-500 rounded-md hover:text-red-700 transition">
                        Delete
                    </button>
                </td>
            `;
            schoolYearsModalTableBody.appendChild(row);
        });
    }

    function openSchoolYearAddEditModal(id = '', name = '') {
        hideModal(schoolYearManagementModal);
        showModal(schoolYearModal);
        schoolYearIdInput.value = id;
        schoolYearNameInput.value = name;
        schoolYearModalTitle.textContent = id ? 'Edit School Year' : 'Add New School Year';
    }

    function closeSchoolYearAddEditModal() {
        hideModal(schoolYearModal);
        schoolYearForm.reset();
        showModal(schoolYearManagementModal);
    }

    async function addOrUpdateSchoolYear(event) {
        event.preventDefault();
        const id = schoolYearIdInput.value;
        const name = schoolYearNameInput.value;
        const action = id ? 'edit_school_year' : 'add_school_year';

        showDecisionModal('Confirm Action', `Are you sure you want to ${id ? 'update' : 'add'} this school year?`, [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Confirm', className: 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=${action}&id=${id}&name=${name}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderSchoolYearsManagementTable(); // Re-render school years table
                        renderCandidatesTable(); // Re-render candidates table
                        closeSchoolYearAddEditModal();
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while saving the school year.', 'error');
                }
            }}
        ]);
    }

    function editSchoolYear(id, name) {
        openSchoolYearAddEditModal(id, name);
    }

    async function deleteSchoolYear(id) {
        showDecisionModal('Confirm Deletion', 'Are you sure you want to delete this school year? This will also affect candidates associated with it.', [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Delete', className: 'px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete_school_year&id=${id}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderSchoolYearsManagementTable(); // Re-render school years table
                        renderCandidatesTable(); // Re-render candidates table
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while deleting the school year.', 'error');
                }
            }}
        ]);
    }


    // === Candidates CRUD ===
    async function renderCandidatesTable(candidatesToRender = ALL_CANDIDATES_DATA) {
        candidatesTableBody.innerHTML = '';

        if (candidatesToRender.length === 0) {
            candidatesTableBody.innerHTML = '<tr><td colspan="7" class="px-5 py-5 border-b border-gray-600 bg-gray-700 text-sm text-center text-gray-400">No candidates found.</td></tr>';
            return;
        }

        candidatesToRender.forEach(candidate => {
            const row = document.createElement('tr');
            row.id = `candidate-row-${candidate.id}`;
            row.className = 'hover:bg-gray-600 transition-colors duration-200 bg-gray-700';
            // No need for data attributes on the row if we always fetch fresh data or find by ID
            // Keeping them for potential future client-side filtering/sorting, but not used for edit.

            const positionName = ALL_POSITIONS_DATA.find(p => p.id == candidate.position_id)?.name || 'N/A';
            const schoolYearName = ALL_SCHOOL_YEARS_DATA.find(sy => sy.id == candidate.school_year_id)?.name || 'N/A';
            const partylistName = ALL_PARTYLISTS_DATA.find(pl => pl.id == candidate.partylist_id)?.name || 'N/A';
            const imageUrl = htmlspecialchars(candidate.image || 'https://placehold.co/50x50/CCCCCC/000000?text=' + htmlspecialchars(substr(candidate.name, 0, 1)));

            row.innerHTML = `
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white">
                    <div class="flex items-center">
                        <img src="${imageUrl}" onerror="this.onerror=null;this.src='https://placehold.co/50x50/CCCCCC/000000?text=${htmlspecialchars(substr(candidate.name, 0, 1))}';" alt="Candidate Image" class="h-10 w-10 rounded-full object-cover mr-3">
                        <p class="text-white whitespace-no-wrap">${htmlspecialchars(candidate.name)}</p>
                    </div>
                </td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white" data-field="position_name">${htmlspecialchars(positionName)}</td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white" data-field="school_year_name">${htmlspecialchars(schoolYearName)}</td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white" data-field="election_year">${htmlspecialchars(candidate.election_year)}</td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white" data-field="partylist_name">${htmlspecialchars(partylistName)}</td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-white" data-field="vote_count" style="font-weight: bold; color: #96b6ffff;">${htmlspecialchars(candidate.vote_count)}</td>
                <td class="px-5 py-5 border-b border-gray-600 text-sm text-center">
                    <button onclick="editCandidate(${candidate.id})" class="text-yellow-500 hover:text-yellow-400 mx-1">
                        Edit
                    </button>
                    |
                    <button onclick="deleteCandidate(${candidate.id})" class="text-red-500 hover:text-red-400 mx-1">
                        Delete
                    </button>
                </td>
            `;
            candidatesTableBody.appendChild(row);
        });
    }

    async function openCandidateModal(candidate = null) {
        showModal(candidateModal);
        candidateForm.reset();
        candidateImagePreview.src = 'https://placehold.co/100x100/CCCCCC/000000?text=No+Image'; // Reset preview
        candidateImageFileInput.value = ''; // Clear file input

        // Refresh dropdown data just in case, though fetchAllCandidateRelatedData should cover this
        // These calls are light as they just return existing global JS arrays
        const positions = ALL_POSITIONS_DATA;
        const schoolYears = ALL_SCHOOL_YEARS_DATA;
        const partylists = ALL_PARTYLISTS_DATA;

        // Populate position dropdown dynamically
        candidatePositionSelect.innerHTML = '<option value="">Select Position</option>';
        positions.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = p.name;
            candidatePositionSelect.appendChild(option);
        });

        // Populate school year dropdown dynamically
        candidateSchoolYearSelect.innerHTML = '<option value="">Select School Year</option>';
        schoolYears.forEach(sy => {
            const option = document.createElement('option');
            option.value = sy.id;
            option.textContent = sy.name;
            candidateSchoolYearSelect.appendChild(option);
        });

        // Populate partylist dropdown dynamically
        candidatePartylistSelect.innerHTML = '<option value="">Select Partylist</option>';
        partylists.forEach(pl => {
            const option = document.createElement('option');
            option.value = pl.id;
            option.textContent = pl.name;
            candidatePartylistSelect.appendChild(option);
        });


        if (candidate) {
            candidateModalTitle.textContent = 'Edit Candidate';
            candidateIdInput.value = candidate.id;
            candidateNameInput.value = candidate.name;
            candidatePositionSelect.value = candidate.position_id;
            candidateImageBase64Input.value = candidate.image || ''; // Set hidden input with current Base64
            candidateImagePreview.src = candidate.image || 'https://placehold.co/100x100/CCCCCC/000000?text=No+Image'; // Set preview
            candidateSchoolYearSelect.value = candidate.school_year_id;
            candidateElectionYearInput.value = candidate.election_year;
            candidatePartylistSelect.value = candidate.partylist_id;
        } else {
            candidateModalTitle.textContent = 'Add New Candidate';
        }
    }

    function closeCandidateModal() {
        hideModal(candidateModal);
        candidateForm.reset();
        candidateImagePreview.src = 'https://placehold.co/100x100/CCCCCC/000000?text=No+Image'; // Reset preview
        candidateImageFileInput.value = ''; // Clear file input
    }

    // Handle file selection for image preview
    candidateImageFileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                candidateImagePreview.src = e.target.result;
                candidateImageBase64Input.value = e.target.result; // Store Base64 for submission
            };
            reader.readAsDataURL(file);
        } else {
            candidateImagePreview.src = 'https://placehold.co/100x100/CCCCCC/000000?text=No+Image';
            candidateImageBase64Input.value = '';
        }
    });

    async function addOrUpdateCandidate(event) {
        event.preventDefault();
        const id = candidateIdInput.value;
        const name = candidateNameInput.value;
        const position_id = candidatePositionSelect.value;
        const image_base64 = candidateImageBase64Input.value;
        const school_year_id = candidateSchoolYearSelect.value;
        const election_year = candidateElectionYearInput.value;
        const partylist_id = candidatePartylistSelect.value;
        const action = id ? 'edit_candidate' : 'add_candidate';

        showDecisionModal('Confirm Action', `Are you sure you want to ${id ? 'update' : 'add'} this candidate?`, [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Confirm', className: 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=${action}&id=${id}&name=${name}&position_id=${position_id}&image_base64=${encodeURIComponent(image_base64)}&school_year_id=${school_year_id}&election_year=${election_year}&partylist_id=${partylist_id}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderCandidatesTable(); // Re-render candidates table
                        closeCandidateModal();
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while saving the candidate.', 'error');
                }
            }}
        ]);
    }

    async function editCandidate(id) {
        // Ensure data is fresh before attempting to find the candidate
        const fetched = await fetchAllCandidateRelatedData();
        if (!fetched) {
            return; // If re-fetch failed, stop
        }
        
        const candidate = ALL_CANDIDATES_DATA.find(c => c.id == id); // Use loose equality for safety if one is string and other is number
        if (candidate) {
            openCandidateModal(candidate);
        } else {
            showToast('Candidate not found.', 'error');
        }
    }

    async function deleteCandidate(id) {
        showDecisionModal('Confirm Deletion', 'Are you sure you want to delete this candidate?', [
            { text: 'Cancel', className: 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition' },
            { text: 'Delete', className: 'px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition', callback: async () => {
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete_candidate&id=${id}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        await fetchAllCandidateRelatedData(); // Re-fetch all related data
                        renderCandidatesTable(); // Re-render candidates table
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while deleting the candidate.', 'error');
                }
            }}
        ]);
    }

    async function viewLeadingCandidates() {
        showModal(leadingCandidatesModal);
        leadingCandidatesContent.innerHTML = ''; // Clear previous content

        // Ensure data is fresh
        await fetchAllCandidateRelatedData();

        // Group candidates by position and find the one with the highest vote_count
        const positionsWithCandidates = ALL_POSITIONS_DATA.map(pos => {
            const candidatesForPosition = ALL_CANDIDATES_DATA.filter(cand => cand.position_id == pos.id);
            const totalVotesForPosition = candidatesForPosition.reduce((sum, cand) => sum + parseInt(cand.vote_count), 0);

            // Sort candidates for the current position by vote_count in descending order
            candidatesForPosition.sort((a, b) => b.vote_count - a.vote_count);

            return {
                ...pos,
                candidates: candidatesForPosition,
                totalVotes: totalVotesForPosition,
            };
        });

        positionsWithCandidates.forEach(position => {
            const positionTitle = document.createElement('h4');
            positionTitle.className = 'text-lg font-semibold text-white mt-4 mb-2';
            positionTitle.textContent = htmlspecialchars(position.name);
            leadingCandidatesContent.appendChild(positionTitle);

            if (position.candidates.length === 0) {
                const noCandidates = document.createElement('p');
                noCandidates.className = 'text-gray-400 text-sm';
                noCandidates.textContent = 'No candidates for this position.';
                leadingCandidatesContent.appendChild(noCandidates);
            } else {
                position.candidates.forEach(candidate => {
                    const percentage = position.totalVotes > 0 ? (parseInt(candidate.vote_count) / position.totalVotes) * 100 : 0;
                    const partylistName = ALL_PARTYLISTS_DATA.find(pl => pl.id == candidate.partylist_id)?.name || 'N/A';
                    const imageUrl = htmlspecialchars(candidate.image || 'https://placehold.co/50x50/CCCCCC/000000?text=' + htmlspecialchars(substr(candidate.name, 0, 1)));

                    const candidateDiv = document.createElement('div');
                    candidateDiv.className = 'flex items-center space-x-3 mb-2';
                    candidateDiv.innerHTML = `
                        <img src="${imageUrl}" onerror="this.onerror=null;this.src='https://placehold.co/50x50/CCCCCC/000000?text=${htmlspecialchars(substr(candidate.name, 0, 1))}';" alt="Candidate Image" class="h-10 w-10 rounded-full object-cover">
                        <div class="flex-grow">
                            <p class="text-white text-md">${htmlspecialchars(candidate.name)} <span class="text-gray-400 text-sm">(${htmlspecialchars(partylistName)})</span></p>
                            <div class="w-full bg-gray-600 rounded-full h-1">
                                <div class="bg-blue-500 h-1 rounded-full" style="width: ${percentage}%;"></div>
                            </div>
                            <p class="text-right text-gray-400 text-sm mt-1">${parseInt(candidate.vote_count)} votes (${percentage.toFixed(2)}%)</p>
                        </div>
                    `;
                    leadingCandidatesContent.appendChild(candidateDiv);
                });
            }
        });
    }


    // --- Event Listeners ---

    // Profile and Logout
    profileBtn.addEventListener('click', (event) => { event.stopPropagation(); toggleProfileModal(); });
    logoutBtn.addEventListener('click', (event) => { event.stopPropagation(); toggleProfileModal(); showModal(logoutModal); });
    // Close profile modal on outside click
    window.addEventListener('click', (event) => {
      if (!profileModal.contains(event.target) && !profileBtn.contains(event.target)) {
        profileModal.classList.add('opacity-0');
        setTimeout(() => { hideModal(profileModal); }, 300);
      }
    });
    cancelLogoutBtn.addEventListener('click', () => hideModal(logoutModal));
    confirmLogoutBtn.addEventListener('click', () => { window.location.href = '../../public/draft/load.php'; });

    // Modals close on outside click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (event) => {
            // Only close if the click is directly on the overlay, not its content
            if (event.target === overlay) {
                hideModal(overlay);
            }
        });
    });
    // Close decision modal on outside click
    decisionModal.addEventListener('click', (event) => {
        if (event.target === decisionModal) {
            hideModal(decisionModal);
        }
    });


    // Main Navigation
    navButtons.forEach(button => {
      button.addEventListener('click', () => {
        const contentId = button.dataset.content;
        const contentElement = document.getElementById(`${contentId}Content`);
        showContent(contentElement, button);  
      });
    });

    // Accounts Filters and Sorts
    searchInput.addEventListener('input', filterAccountsTable);
    filterStatusSelect.addEventListener('change', filterAccountsTable);
    filterTypeSelect.addEventListener('change', filterAccountsTable);
    filterVotingStatusSelect.addEventListener('change', filterAccountsTable);

    document.querySelectorAll('.sortable').forEach(header => {
      header.addEventListener('click', () => {
        const sortBy = header.dataset.sortBy;
        const currentSortOrder = header.dataset.sortOrder || 'asc';
        let newSortOrder = (currentSortOrder === 'asc') ? 'desc' : 'asc';
        header.dataset.sortOrder = newSortOrder;
        sortAccountsTable(sortBy, newSortOrder);
      });
    });

    // Responsive filter toggle
    filterToggleButton.addEventListener('click', () => {
        filterControlsContainer.classList.toggle('active');
    });

    // Positions Management Events
    managePositionsBtn.addEventListener('click', async () => {
        await fetchAllCandidateRelatedData(); // Ensure data is fresh before rendering table
        renderPositionsManagementTable();
        showModal(positionManagementModal);
    });
    addPositionModalBtn.addEventListener('click', () => openPositionAddEditModal());
    cancelPositionModalBtn.addEventListener('click', closePositionAddEditModal);
    positionForm.addEventListener('submit', addOrUpdatePosition);

    // Partylist Management Events
    managePartylistsBtn.addEventListener('click', async () => {
        await fetchAllCandidateRelatedData(); // Ensure data is fresh before rendering table
        renderPartylistsManagementTable();
        showModal(partylistManagementModal);
    });
    addPartylistModalBtn.addEventListener('click', () => openPartylistAddEditModal());
    cancelPartylistModalBtn.addEventListener('click', closePartylistAddEditModal);
    partylistForm.addEventListener('submit', addOrUpdatePartylist);

    // School Year Management Events
    manageSchoolYearsBtn.addEventListener('click', async () => {
        await fetchAllCandidateRelatedData(); // Ensure data is fresh before rendering table
        renderSchoolYearsManagementTable();
        showModal(schoolYearManagementModal);
    });
    addSchoolYearModalBtn.addEventListener('click', () => openSchoolYearAddEditModal());
    cancelSchoolYearModalBtn.addEventListener('click', closeSchoolYearAddEditModal);
    schoolYearForm.addEventListener('submit', addOrUpdateSchoolYear);


    // Candidate Management Events
    addCandidateBtn.addEventListener('click', () => openCandidateModal());
    viewLeadingCandidatesBtn.addEventListener('click', viewLeadingCandidates);
    cancelCandidateModalBtn.addEventListener('click', closeCandidateModal);
    candidateForm.addEventListener('submit', addOrUpdateCandidate);


    // Initial load: show content based on URL parameter or default to dashboard
    window.addEventListener('load', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        const content = urlParams.get('content');
        let initialContent = dashboardContent;
        let initialButton = dashboardBtn;

        // Fetch all related data upfront to populate global JS variables and update Dashboard
        const fetched = await fetchAllCandidateRelatedData();
        if (!fetched) {
            console.error("Initial data fetch failed. Some features may not work correctly.");
        }

        if (content === 'accounts') {
            initialContent = accountsContent;
            initialButton = accountsBtn;
            searchInput.value = urlParams.get('search') || '';
            filterStatusSelect.value = urlParams.get('filter_status') || 'all';
            filterTypeSelect.value = urlParams.get('filter_type') || 'all';
            filterVotingStatusSelect.value = urlParams.get('filter_voting_status') || 'all';
            filterAccountsTable();
        } else if (content === 'candidates') {
            initialContent = candidatesContent;
            initialButton = candidatesBtn;
            renderCandidatesTable(); // Render candidates table using the fetched data
        } else if (content === 'reports') {
            initialContent = reportsContent;
            initialButton = reportsBtn;
        }
        showContent(initialContent, initialButton);
    });

    // Helper function for HTML escaping in JS (equivalent to PHP's htmlspecialchars)
    function htmlspecialchars(str) {
        if (typeof str != 'string' && typeof str != 'number') {
            return '';
        }
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(str).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Helper for substr in JS (equivalent to PHP's substr)
    function substr(str, start, length) {
        if (typeof str != 'string') {
            return '';
        }
        return str.substring(start, start + length);
    }

    // Expose functions globally for HTML onclick
    window.handleStatusChange = handleStatusChange;
    window.editPosition = editPosition;
    window.deletePosition = deletePosition;
    window.editPartylist = editPartylist;
    window.deletePartylist = deletePartylist;
    window.editSchoolYear = editSchoolYear;
    window.deleteSchoolYear = deleteSchoolYear;
    window.editCandidate = editCandidate;
    window.deleteCandidate = deleteCandidate;
    window.viewLeadingCandidates = viewLeadingCandidates;

  </script>
</body>
</html>
