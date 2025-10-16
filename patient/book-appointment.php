<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

// Fetch doctors
$doctors = [];
$result = $conn->query("SELECT d.id, d.name, s.name as specialization FROM doctors d JOIN specializations s ON d.specialization_id = s.id ORDER BY d.name ASC");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
        }
        .navbar {
            background-color: #1976d2;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar .nav-left a {
            color: #fff;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .navbar .nav-right {
            display: flex;
            align-items: center;
        }
        .navbar .nav-right .user-icon {
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 15px;
        }
        .navbar .nav-right a {
            color: #fff;
            text-decoration: none;
            margin-left: 10px;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .search-filter-container {
            margin-bottom: 20px;
        }
        .search-bar, .filter-bar {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 5px;
            margin-bottom: 10px;
        }
        .search-bar input, .filter-bar select {
            border: none;
            outline: none;
            width: 100%;
            padding: 5px;
        }
        .search-bar i, .filter-bar i {
            margin: 0 10px;
            color: #777;
        }
        .doctor-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .doctor-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #fafafa;
        }
        .doctor-item .icon {
            font-size: 2rem;
            color: #1976d2;
            margin-right: 15px;
        }
        .doctor-info {
            flex-grow: 1;
        }
        .doctor-info h4 {
            margin: 0 0 5px 0;
        }
        .doctor-info p {
            margin: 0;
            color: #666;
        }
        .book-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="../includes/homepage.php">Patient Panel</a>
        </div>
        <div class="nav-right">
            <span class="user-icon" id="profileToggle">👤</span>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="search-filter-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchDoctor" placeholder="Search Doctor...">
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
                ?>
                    <li class="doctor-item" data-name="<?php echo strtolower($escaped_name); ?>" data-spec="<?php echo strtolower($escaped_spec); ?>">
                        <div class="icon"><i class="fas fa-user-md"></i></div>
                        <div class="doctor-info">
                            <h4><?php echo $escaped_name; ?></h4>
                            <p><?php echo $escaped_spec; ?></p>
                        </div>
                        <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>" class="book-btn">Book</a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        const searchDoctor = document.getElementById('searchDoctor');
        const specializationFilter = document.getElementById('specializationFilter');
        const doctorList = document.getElementById('doctorList');
        const doctorItems = Array.from(doctorList.getElementsByClassName('doctor-item'));

        searchDoctor.addEventListener('input', filterDoctors);
        specializationFilter.addEventListener('change', filterDoctors);

        function filterDoctors() {
            const searchTerm = searchDoctor.value.toLowerCase();
            const specTerm = specializationFilter.value.toLowerCase();

            doctorItems.forEach(item => {
                const name = item.dataset.name;
                const spec = item.dataset.spec;
                const nameMatch = name.includes(searchTerm);
                const specMatch = spec.includes(specTerm);

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