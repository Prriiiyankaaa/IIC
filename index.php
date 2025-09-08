<?php
// --- DATABASE CONNECTION ---
// These are your database connection details.
// By default, XAMPP's MySQL user is "root" with no password.
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "complaint_system"; // The database we will create

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . ". Please make sure the database 'complaint_system' exists.");
}

// --- HANDLE FORM SUBMISSIONS ---

// 1. Handle NEW complaint submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_complaint'])) {
    // Sanitize user input to prevent issues
    $user_name = $conn->real_escape_string($_POST['user_name']);
    $complaint_type = $conn->real_escape_string($_POST['complaint_type']);
    $description = $conn->real_escape_string($_POST['description']);

    // Simple validation
    if (!empty($user_name) && !empty($complaint_type) && !empty($description)) {
        // The issue_title field has been removed to fix the error
        $sql = "INSERT INTO complaints (user_name, complaint_type, description, status) VALUES ('$user_name', '$complaint_type', '$description', 'Pending')";

        if ($conn->query($sql) !== TRUE) {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
    // Redirect to the same page to prevent form re-submission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 2. Handle STATUS update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $new_status = $conn->real_escape_string($_POST['status']);

    if ($complaint_id > 0 && !empty($new_status)) {
        $sql = "UPDATE complaints SET status = '$new_status', updated_at = NOW() WHERE id = $complaint_id";
        if ($conn->query($sql) !== TRUE) {
            echo "Error updating record: " . $conn->error;
        }
    }
    // Redirect to the same page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- FETCH ALL COMPLAINTS TO DISPLAY ---
// The issue_title field has been removed to fix the error
$complaints_result = $conn->query("SELECT id, user_name, complaint_type, description, status, created_at, updated_at FROM complaints ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>MUJ Grievance Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        :root {
            --primary-yellow: #FFC700;
            --dark-text: #2C3E50;
            --primary-action: #00796B;
            --primary-action-hover: #00695C;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FFFDE7 0%, #FFF59D 100%);
            background-attachment: fixed;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(15px);
            border-radius: 1rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .status-Pending { background-color: #FEF3C7; color: #92400E; }
        .status-InProgress { background-color: #DBEAFE; color: #1E40AF; }
        .status-Resolved { background-color: #D1FAE5; color: #065F46; }

        .btn {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn:hover {
            box-shadow: 0 7px 14px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .btn-primary {
            background-color: var(--primary-action);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--primary-action-hover);
        }
        
        .form-input, .form-select {
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(209, 213, 219, 0.8);
            transition: all 0.3s ease;
        }
        .form-input:focus, .form-select:focus {
            border-color: var(--primary-action);
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.3);
            outline: none;
        }
        .table-header {
            background-color: var(--dark-text);
            color: white;
        }
        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <a href="https://www.google.com/url?sa=i&url=https%3A%2F%2Fen.wikipedia.org%2Fwiki%2FManipal_University_Jaipur&psig=AOvVaw35j4albOCUCFXZT9rF4h-4&ust=1757424390594000&source=images&cd=vfe&opi=89978449&ved=0CBUQjRxqFwoTCMjV0aiiyY8DFQAAAAAdAAAAABAa" target="_blank" rel="noopener noreferrer" class="absolute top-6 left-6 text-gray-500 hover:text-gray-800 transition-colors" title="About Manipal University Jaipur">
        <i class="fas fa-external-link-alt fa-2x"></i>
    </a>

    <div class="container mx-auto space-y-8">

        <!-- Header -->
        <header class="text-center space-y-4 my-8">
            <img src="https://upload.wikimedia.org/wikipedia/en/1/1f/Manipal_University_Jaipur_logo.png" alt="MUJ Logo" class="mx-auto h-28 w-auto drop-shadow-lg">
            <h1 class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-500 to-orange-500">
                MUJ Grievance Portal
            </h1>
            <p style="color: var(--dark-text);" class="font-medium mt-2 text-lg">An innovative platform for effective problem resolution.</p>
        </header>

        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            <!-- Complaint Submission Form -->
            <div class="lg:col-span-5 mb-8 lg:mb-0">
                <div class="card p-6 md:p-8">
                    <h2 class="text-2xl font-bold mb-6 border-b-2 border-gray-200 pb-4" style="color: var(--dark-text);">File a New Grievance</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
                        <div>
                            <label for="user_name" class="block text-sm font-medium" style="color: var(--dark-text);">Your Full Name</label>
                            <input type="text" id="user_name" name="user_name" required class="form-input mt-1 block w-full px-4 py-2 rounded-lg shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label for="complaint_type" class="block text-sm font-medium" style="color: var(--dark-text);">Complaint Category</label>
                            <select id="complaint_type" name="complaint_type" required class="form-select mt-1 block w-full pl-3 pr-10 py-2 text-base rounded-lg">
                                <option disabled selected>Select a category...</option>
                                <optgroup label="Academic">
                                    <option>Course Registration Issue</option>
                                    <option>Grading/Evaluation Dispute</option>
                                    <option>Faculty/Teaching Quality</option>
                                    <option>Timetable Clash</option>
                                    <option>Examination Related</option>
                                </optgroup>
                                <optgroup label="Hostel & Campus Life">
                                    <option>Hostel Room Allotment</option>
                                    <option>Hostel Maintenance (Plumbing, Electrical)</option>
                                    <option>Mess Food Quality/Hygiene</option>
                                    <option>Campus Security</option>
                                    <option>Sports Facilities</option>
                                </optgroup>
                                <optgroup label="Infrastructure & Services">
                                    <option>Classroom Facilities</option>
                                    <option>Library Services/Resources</option>
                                    <option>Campus Wi-Fi/Internet</option>
                                    <option>Laboratory Equipment</option>
                                    <option>Transport Services</option>
                                </optgroup>
                                <optgroup label="Administrative">
                                    <option>Fee Payment Issue</option>
                                    <option>Scholarship Related</option>
                                    <option>ID Card/Documentation</option>
                                    <option>Student Support Services</option>
                                </optgroup>
                                <option>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium" style="color: var(--dark-text);">Detailed Description</label>
                            <textarea id="description" name="description" rows="5" required class="form-input mt-1 block w-full px-4 py-2 rounded-lg shadow-sm sm:text-sm" placeholder="Please provide as much detail as possible..."></textarea>
                        </div>
                        <div>
                            <button type="submit" name="submit_complaint" class="btn btn-primary w-full flex justify-center py-3 px-4 rounded-lg shadow-sm text-base font-semibold">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Complaint
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Complaint Tracking Table -->
            <div class="lg:col-span-7">
                <div class="card overflow-x-auto p-2">
                     <h2 class="text-2xl font-bold p-6" style="color: var(--dark-text);">Grievance Status Tracker</h2>
                    <table class="min-w-full">
                        <thead class="table-header">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider rounded-tl-lg">Ticket ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Issue Summary</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Submitted By</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider rounded-tr-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/50">
                            <?php if ($complaints_result->num_rows > 0): ?>
                                <?php while($row = $complaints_result->fetch_assoc()): ?>
                                    <?php
                                        // Create a short summary from the description
                                        $full_desc = htmlspecialchars($row['description']);
                                        $summary = substr($full_desc, 0, 40);
                                        if (strlen($full_desc) > 40) {
                                            $summary .= '...';
                                        }
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" style="color: var(--dark-text);">MUJ-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800" title="<?php echo $full_desc; ?>"><?php echo $summary; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($row['user_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge status-<?php echo str_replace(' ', '', $row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="flex items-center space-x-2">
                                                <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                                <select name="status" class="form-select block w-full pl-2 pr-7 py-1 text-sm rounded-md">
                                                    <option value="Pending" <?php if ($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                                    <option value="In Progress" <?php if ($row['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                                                    <option value="Resolved" <?php if ($row['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-primary py-1 px-3 rounded-md shadow-sm text-xs font-medium">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-lg text-gray-500">No grievances have been filed yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
<?php
// Close the database connection
$conn->close();
?>

