<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

// Fetch doctors
$doctors = [];
// UPDATED: Ensure d.profile_pic is included
$result = $conn->query("SELECT d.id, d.name, d.profile_pic, s.name as specialization FROM doctors d JOIN specializations s ON d.specialization_id = s.id ORDER BY d.name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Linking to the external style.css -->
    <link rel="stylesheet" href="../assets/css/style.css"> 
   <link rel="stylesheet" href="../assets/css/appointment.css" />
   
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="../includes/homepage.php">Patient Panel</a>
        </div>
        <div class="nav-right">
            <span class="user-icon" id="profileToggle"><i class="fas fa-user-circle"></i></span>
        </div>
    </header>

    <div class="container panel-card">
        <h2>Book a New Appointment</h2>
        <div class="search-filter-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchDoctor" placeholder="Search Doctor by name...">
            </div>
            <div class="filter-bar">
                <i class="fas fa-filter"></i>
                <select id="specializationFilter">
                    <option value="">All Specializations</option>
                    <?php
                    $specializations_filter = [];
                    $result_spec = $conn->query("SELECT id, name FROM specializations ORDER BY name ASC");
                    if ($result_spec) {
                        while ($row_spec = $result_spec->fetch_assoc()) {
                            $specializations_filter[] = $row_spec;
                        }
                    }
                    foreach ($specializations_filter as $spec) {
                        echo "<option value=\"" . htmlspecialchars($spec['name']) . "\">" . htmlspecialchars($spec['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <ul class="doctor-list" id="doctorList">
            <?php if (empty($doctors)): ?>
                <p>No doctors available for booking at the moment.</p>
            <?php else: ?>
                <?php foreach ($doctors as $doctor):
                    // Ensure data attributes are properly escaped for HTML
                    $escaped_name = htmlspecialchars($doctor['name']);
                    $escaped_spec = htmlspecialchars($doctor['specialization']);
                    
                    // --- CORRECTED IMAGE PATH LOGIC ---
                    // 1. Define the base path for all profile pictures
                    $base_img_dir = '../assets/images/profile_pics/';

                    // 2. Define the path for the default avatar
                    $default_img_path = $base_img_dir . 'default-doctor.png';
                    
                    // 3. Process the profile_pic from the database
                    $profile_pic_db = $doctor['profile_pic'] ?? '';
                    
                    if (!empty($profile_pic_db) && $profile_pic_db !== 'default-avatar.png') {
                        // Use basename() to safely extract only the filename, removing any unnecessary path fragments that might be in the DB
                        $filename = basename($profile_pic_db);
                        $doctor_img_path = $base_img_dir . htmlspecialchars($filename);
                    } else {
                        // Use Font Awesome icon if the image path is default or empty
                        $doctor_img_path = null; 
                    }
                    // -----------------------------

                ?>
                    <li class="doctor-item" data-name="<?php echo strtolower($escaped_name); ?>" data-spec="<?php echo strtolower($escaped_spec); ?>">
                        
                        <div class="doctor-avatar">
                            <?php if ($doctor_img_path !== null): ?>
                                <img src="<?php echo $doctor_img_path; ?>" 
                                     alt="Dr. <?php echo $escaped_name; ?> Profile"
                                     onerror="this.onerror=null; this.src='<?php echo $default_img_path; ?>';">
                            <?php else: ?>
                                <!-- Fallback to Font Awesome icon if no custom image is set -->
                                <i class="fas fa-user-md"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="doctor-info">
                            <h4>Dr. <?php echo $escaped_name; ?></h4>
                            <p><?php echo $escaped_spec; ?></p>
                        </div>
                        <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>" class="book-btn">Book Now</a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        const searchDoctor = document.getElementById('searchDoctor');
        const specializationFilter = document.getElementById('specializationFilter');
        const doctorList = document.getElementById('doctorList');
        // Filter out any potential non-li elements if fetching from doctorList directly
        const doctorItems = Array.from(doctorList.getElementsByClassName('doctor-item')); 

        // Attach event listeners
        searchDoctor.addEventListener('input', filterDoctors);
        specializationFilter.addEventListener('change', filterDoctors);

        /**
         * Filters the list of doctors based on the search term (fuzzy match) 
         * and the selected specialization (exact match).
         */
        function filterDoctors() {
            const searchTerm = searchDoctor.value.toLowerCase();
            const specTerm = specializationFilter.value.toLowerCase();

            doctorItems.forEach(item => {
                const name = item.dataset.name;
                const spec = item.dataset.spec;
                
                // 1. Search Match (fzf-like: includes the substring)
                const nameMatch = name.includes(searchTerm);

                // 2. Specialization Match (Exact match OR specTerm is empty ("") for "All")
                // FIX: Changed spec.includes(specTerm) to spec === specTerm for exact matching.
                const specMatch = specTerm === "" || spec === specTerm;

                // Combined visibility logic
                if (nameMatch && specMatch) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
