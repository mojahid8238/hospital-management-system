<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

$patient_id = null;

// Get patient_id for the logged-in user
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($patient_id);
$stmt->fetch();
$stmt->close();

$message = '';

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id) {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $full_appointment_datetime = $appointment_date . ' ' . $appointment_time;
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $doctor_id, $full_appointment_datetime, $reason);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Appointment booked successfully!</p>";
        } else {
            $message = "<p style='color: red;'>Error booking appointment: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Fetch doctors
$doctors = [];
$result = $conn->query("SELECT id, name, specialization FROM doctors ORDER BY name ASC");
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
    <title>Book Appointment</title>
    <link rel="stylesheet" href="../assets/css/book-appoinment.css">
    
</head>
<body>

<div class="container">
    <h2>Book New Appointment</h2>
    <p>Welcome, <?php echo $_SESSION['username']; ?>! Book your appointment here:</p>

    <?php echo $message; ?>

    <?php if (empty($doctors)): ?>
        <p>No doctors available for booking at the moment.</p>
    <?php else: ?>
        <form action="book-appointment.php" method="POST" onsubmit="return validateDoctorSelection()">
            

            <!-- 🔽 Specialization Filter -->
            <label for="specializationFilter">Filter by Specialization:</label>
            <select id="specializationFilter">
                <option value="">All Specializations</option>
                <?php
                $specializations = array_unique(array_column($doctors, 'specialization'));
                sort($specializations);
                foreach ($specializations as $spec) {
                    echo "<option value=\"" . htmlspecialchars($spec) . "\">" . htmlspecialchars($spec) . "</option>";
                }
                ?>
            </select>

            <!-- 🔍 Doctor Search -->
            <label for="searchDoctor">Search Doctor:</label>
            <div class="search-wrapper">
                <input type="text" id="searchDoctor" placeholder="Type doctor name..." autocomplete="off">
                <ul id="doctorSuggestions"></ul>
            </div>

            <!-- Hidden input to hold selected doctor ID -->
            <input type="hidden" name="doctor_id" id="selectedDoctorId" required>

            <label for="appointment_date">Date:</label>
            <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">

            <label for="appointment_time">Time:</label>
            <input type="time" id="appointment_time" name="appointment_time" required>

            <label for="reason">Reason for Appointment:</label>
            <textarea id="reason" name="reason" rows="4" required></textarea>

            <button type="submit">Book Appointment</button>
        </form>
    <?php endif; ?>
</div>

<script>
const doctors = <?php echo json_encode($doctors); ?>;
const input = document.getElementById('searchDoctor');
const suggestions = document.getElementById('doctorSuggestions');
const specializationFilter = document.getElementById('specializationFilter');
const hiddenDoctorId = document.getElementById('selectedDoctorId');

input.addEventListener('input', showSuggestions);
specializationFilter.addEventListener('change', showSuggestions);

function showSuggestions() {
    const query = input.value.toLowerCase();
    const specialization = specializationFilter.value.toLowerCase();
    suggestions.innerHTML = '';

    if (query.length < 1) {
        suggestions.style.display = 'none';
        return;
    }

    const filtered = doctors.filter(doc => {
        const matchesName = doc.name.toLowerCase().includes(query);
        const matchesSpec = !specialization || doc.specialization.toLowerCase() === specialization;
        return matchesName && matchesSpec;
    });

    if (filtered.length === 0) {
        suggestions.style.display = 'none';
        return;
    }

    filtered.forEach(doc => {
        const li = document.createElement('li');
        li.textContent = `${doc.name} (${doc.specialization})`;
        li.dataset.id = doc.id;
        li.addEventListener('click', () => {
            input.value = `${doc.name} (${doc.specialization})`;
            hiddenDoctorId.value = doc.id;
            suggestions.innerHTML = '';
            suggestions.style.display = 'none';
        });
        suggestions.appendChild(li);
    });

    suggestions.style.display = 'block';
}

function validateDoctorSelection() {
    if (!hiddenDoctorId.value) {
        alert("Please select a valid doctor from the suggestions.");
        return false;
    }
    return true;
}
</script>

</body>
</html>
