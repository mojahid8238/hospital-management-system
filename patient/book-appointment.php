<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

// Fetch doctors
$doctors = [];
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
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/view-appointments.css">

</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Patient Panel</a>
        </div>
        <div class="nav-right">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="patientSidebar">
            <h3>Patient Options</h3>
            <ul>
                <li><a href="book-appointment.php">Book New Appointment</a></li>
                <li><a href="dashboard.php">Your Appointments & History</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
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
                            $escaped_name = htmlspecialchars($doctor['name']);
                            $escaped_spec = htmlspecialchars($doctor['specialization']);
                            $base_img_dir = '../assets/images/profile_pics/';
                            $default_img_path = $base_img_dir . 'default-doctor.png';
                            $profile_pic_db = $doctor['profile_pic'] ?? '';
                            if (!empty($profile_pic_db) && $profile_pic_db !== 'default-avatar.png') {
                                $filename = basename($profile_pic_db);
                                $doctor_img_path = $base_img_dir . htmlspecialchars($filename);
                            } else {
                                $doctor_img_path = null; 
                            }
                        ?>
                            <li class="doctor-item" data-name="<?php echo strtolower($escaped_name); ?>" data-spec="<?php echo strtolower($escaped_spec); ?>">
                                <div class="doctor-avatar">
                                    <?php if ($doctor_img_path !== null): ?>
                                        <img src="<?php echo $doctor_img_path; ?>" 
                                             alt="Dr. <?php echo $escaped_name; ?> Profile"
                                             onerror="this.onerror=null; this.src='<?php echo $default_img_path; ?>';">
                                    <?php else:
                                        // Fallback to Font Awesome icon if no custom image is set 
                                    ?><i class="fas fa-user-md"></i>
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
        </main>
    </div>

    <!-- Profile side overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Patient Dashboard</a></li>
                <li><a href="../includes/homepage.php">Patient Homepage</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <script>
        function initializeDoctorFilters() {
            const searchDoctor = document.getElementById('searchDoctor');
            const specializationFilter = document.getElementById('specializationFilter');
            const doctorList = document.getElementById('doctorList');
            // Ensure doctorList exists before proceeding
            if (!doctorList) return;

            const doctorItems = Array.from(doctorList.getElementsByClassName('doctor-item'));

            // Remove existing listeners to prevent duplicates if called multiple times
            if (searchDoctor) {
                searchDoctor.removeEventListener('input', filterDoctors);
                searchDoctor.addEventListener('input', filterDoctors);
            }
            if (specializationFilter) {
                specializationFilter.removeEventListener('change', filterDoctors);
                specializationFilter.addEventListener('change', filterDoctors);
            }

            function filterDoctors() {
                const searchTerm = searchDoctor ? searchDoctor.value.toLowerCase() : '';
                const specTerm = specializationFilter ? specializationFilter.value.toLowerCase() : '';

                doctorItems.forEach(item => {
                    const name = item.dataset.name;
                    const spec = item.dataset.spec;
                    const nameMatch = name.includes(searchTerm);
                    const specMatch = specTerm === "" || spec === specTerm;

                    if (nameMatch && specMatch) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const patientSidebar = document.getElementById('patientSidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarLinks = document.querySelectorAll('.sidebar-link');

            sidebarToggle.addEventListener('click', () => {
                patientSidebar.classList.toggle('closed');
            });

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetPage = this.dataset.target;
                    fetch(targetPage)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const bodyContent = doc.querySelector('.container').innerHTML;
                            mainContent.innerHTML = '<div class="container">' + bodyContent + '</div>';
                            initializeDoctorFilters(); // Re-initialize after content update
                        })
                        .catch(error => {
                            console.error('Error loading page:', error);
                            mainContent.innerHTML = '<p style="color: red;">Error loading content.</p>';
                        });
                });
            });

            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page');
            if (page) {
                const targetPage = page + '.php';
                fetch(targetPage)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const bodyContent = doc.querySelector('.container').innerHTML;
                        mainContent.innerHTML = '<div class="container">' + bodyContent + '</div>';
                        initializeDoctorFilters(); // Re-initialize after content update
                    })
                    .catch(error => {
                        console.error('Error loading page:', error);
                        mainContent.innerHTML = '<p style="color: red;">Error loading content.</p>';
                    });
            }

            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            const profilePicInput = document.getElementById('profilePicInput');
            const profilePicUploadForm = document.getElementById('profilePicUploadForm');
            const profileImageDisplay = document.getElementById('profileImageDisplay');
            const uploadMessage = document.getElementById('uploadMessage');

            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                profileOverlay.classList.add('open');
            });

            document.addEventListener('click', function(event) {
                // Check if the click is outside the profile overlay and not on the profile toggle button
                if (!profileOverlay.contains(event.target) && !profileToggle.contains(event.target)) {
                    profileOverlay.classList.remove('open');
                }
            });



            const profileContent = document.querySelector('.profile-content');
            if (profileContent) {
                profileContent.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            }



            profileImageDisplay.addEventListener('click', function() {
                profilePicInput.click();
            });

            profilePicInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const formData = new FormData(profilePicUploadForm);
                    fetch(profilePicUploadForm.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const newImagePath = '/hospital-management-system/' + data.profile_pic_path + '?t=' + new Date().getTime();
                            profileImageDisplay.src = newImagePath;
                            document.getElementById('profileToggle').src = newImagePath;
                            uploadMessage.textContent = 'Profile picture updated successfully!';
                            uploadMessage.style.color = 'green';
                            setTimeout(() => {
                                uploadMessage.textContent = '';
                            }, 1000);
                        } else {
                            uploadMessage.textContent = data.message || 'Error uploading profile picture.';
                            uploadMessage.style.color = 'red';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        uploadMessage.textContent = 'An error occurred during upload.';
                        uploadMessage.style.color = 'red';
                    });
                }
            });

            initializeDoctorFilters(); // Initial call on DOMContentLoaded

            const doctorItems = document.querySelectorAll('.doctor-item');
            doctorItems.forEach(item => {
                item.style.cursor = 'pointer';
                item.addEventListener('click', function() {
                    const link = this.querySelector('.book-btn');
                    if (link) {
                        window.location.href = link.href;
                    }
                });
            });
        });
    </script>
</body>
</html>