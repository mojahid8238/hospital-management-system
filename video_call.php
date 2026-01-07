<?php
/**
 * Healthcare Video Call Interface (WebRTC)
 * Premium, Glassmorphism Design
 */
ob_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header("Location: auth/login.php");
    exit();
}

$appointment_id = $_GET['appointment_id'] ?? null;
if (!$appointment_id) {
    die("Error: Invalid meeting parameters.");
}

$user_id = $_SESSION['user_id'];
$is_doctor = is_doctor();
$display_name = "User";
$can_access = false;

// Verify access and get identity
try {
    if ($is_doctor) {
        $stmt = $conn->prepare("SELECT d.name FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.id = ? AND d.user_id = ?");
        $stmt->bind_param("ii", $appointment_id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT p.name FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id = ? AND p.user_id = ?");
        $stmt->bind_param("ii", $appointment_id, $user_id);
    }
    
    if (isset($stmt)) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $can_access = true;
            $display_name = $is_doctor ? "Dr. " . $row['name'] : $row['name'];
        }
        $stmt->close();
    }
} catch (Exception $e) {
    die("System Error: Access verification failed.");
}

if (!$can_access) {
    die("Access Denied: Please log in with the correct account.");
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Consult | HealthCare</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #818cf8;
            --bg-dark: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-light: #f8fafc;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            background-color: var(--bg-dark);
            font-family: 'Outfit', sans-serif;
            color: var(--text-light);
            overflow: hidden;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
        }

        /* Remote Video (Full Screen) */
        #remote-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            z-index: 1;
        }

        /* Local Video (Floating Picture-in-Picture) */
        .local-video-wrapper {
            position: absolute;
            bottom: 120px;
            right: 30px;
            width: 280px;
            aspect-ratio: 16/9;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid var(--glass-border);
            z-index: 10;
            transition: all 0.3s ease;
            background: #000;
        }
        
        .local-video-wrapper:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        }

        #local-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Mirror effect */
        }

        /* Controls Bar */
        .controls-bar {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            padding: 15px 30px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 50px;
            border: 1px solid var(--glass-border);
            z-index: 20;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .control-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            background: rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }

        .control-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .control-btn.active {
            background: #ef4444; /* Red for off/mute */
            color: white;
        }

        .control-btn.end-call {
            background: #ef4444;
            width: 60px;
            height: 60px;
            font-size: 1.4rem;
        }
        
        .control-btn.end-call:hover {
            background: #dc2626;
        }

        /* Status Overlay */
        .status-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.9);
            z-index: 100;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .status-text {
            font-size: 1.5rem;
            margin-top: 20px;
            color: var(--text-light);
            font-weight: 300;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255,255,255,0.1);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* User Label */
        .user-label {
            position: absolute;
            top: 30px;
            left: 30px;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-label i { color: #22c55e; font-size: 0.8rem; }
        

        .waiting-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: rgba(255,255,255,0.5);
            font-size: 1.2rem;
            z-index: 0;
        }

        /* Prescription Panel */
        .prescription-panel {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 95%;
            max-width: 600px;
            max-height: 90vh;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            z-index: 200; /* Increased z-index */
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
            overflow-y: auto; /* Enable scrolling if needed */
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.05);
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .panel-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px; /* Spacing between icon and text */
        }
        .panel-header h3 i {
            color: var(--primary-color);
        }

        .panel-header button {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .panel-header button:hover {
            color: var(--primary-color);
        }

        .panel-body { 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 15px; 
            overflow-y: auto; 
        }

        textarea {
            width: 100%;
            height: 150px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: white;
            padding: 10px;
            font-family: 'Courier New', monospace; /* Monospace for alignment */
            resize: vertical;
            box-sizing: border-box;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .action-btn {
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, transform 0.1s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        .action-btn:active { transform: scale(0.98); }
        .action-btn:hover { background: var(--secondary-color); }
        .action-btn.secondary { background: #334155; }
        .action-btn.secondary:hover { background: #475569; }

        #patient-prescription-view {
            min-height: 200px;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            white-space: pre-wrap;
        }
        
        .placeholder-text { color: #64748b; font-style: italic; text-align: center; margin-top: 80px; }

        .medicine-adder {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 8px;
            margin-bottom: 12px;
            align-items: center;
            background: rgba(255,255,255,0.03);
            padding: 10px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
        }
        .glass-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            padding: 8px 10px;
            border-radius: 6px;
            color: white;
            font-family: inherit;
            width: 100%;
            box-sizing: border-box;
            font-size: 0.9rem;
        }
        .glass-input.small { /* No specific overrides needed for grid */ }
        .medicine-adder button {
            padding: 8px 12px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .glass-input:focus { outline: none; border-color: var(--primary-color); background: rgba(255,255,255,0.1); }
        .prescription-input-group { display: flex; flex-direction: column; }
        
        @media (max-width: 600px) {
            .medicine-adder {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            #med-name { grid-column: 1 / -1; }
            #add-med-btn { grid-column: 1 / -1; }
        }
    </style>
</head>
<body>

    <div id="status-overlay" class="status-overlay">
        <div class="spinner"></div>
        <div class="status-text" id="status-text">Initializing Secure Connection...</div>
    </div>

    <div class="video-container">
        <!-- Remote Video -->
        <video id="remote-video" autoplay playsinline></video>
        <div class="waiting-message" id="waiting-message">Waiting for participant to join...</div>

        <!-- Local Video -->
        <div class="local-video-wrapper">
            <video id="local-video" autoplay playsinline muted></video>
        </div>

        <!-- User Info -->
        <div class="user-label">
            <i class="fas fa-circle"></i>
            <span><?php echo htmlspecialchars($display_name); ?></span>
        </div>

        <!-- Controls -->
        <div class="controls-bar">
            <!-- Prescription Button -->
            <button class="control-btn" id="prescription-btn" title="Prescriptions">
                <i class="fas fa-file-prescription"></i>
            </button>
            <button class="control-btn" id="audio-btn" title="Toggle Mute">
                <i class="fas fa-microphone"></i>
            </button>
            <button class="control-btn" id="video-btn" title="Toggle Video">
                <i class="fas fa-video"></i>
            </button>
            <button class="control-btn end-call" id="end-call-btn" title="End Call">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>

        <!-- Prescription Panel -->
        <div id="prescription-panel" class="prescription-panel" style="display: none;">
            <div class="panel-header">
                <h3><i class="fas fa-file-medical"></i> Prescription</h3>
                <button id="close-prescription"><i class="fas fa-times"></i></button>
            </div>
            <div class="panel-body">
                <?php if ($is_doctor): ?>
                    <div class="prescription-input-group">
                        <div class="medicine-adder">
                            <input type="text" id="med-name" placeholder="Medicine" class="glass-input" list="medicine-list">
                            <datalist id="medicine-list">
                                <option value="Paracetamol">
                                <option value="Amoxicillin">
                                <option value="Ibuprofen">
                                <option value="Metformin">
                                <option value="Atorvastatin">
                                <option value="Omeprazole">
                                <option value="Aspirin">
                                <option value="Cetirizine">
                                <option value="Azithromycin">
                                <option value="Pantoprazole">
                                <option value="Vitamin D3">
                                <option value="B Complex">
                                <option value="Calcium Sandoz">
                                <option value="Dolo 650">
                                <option value="Cough Syrup">
                            </datalist>
                            <input type="text" id="med-dosage" placeholder="Dose (1-0-1)" class="glass-input small">
                            <input type="text" id="med-freq" placeholder="Freq" class="glass-input small">
                            <input type="text" id="med-duration" placeholder="Days" class="glass-input small">
                            <button id="add-med-btn" class="action-btn secondary" title="Add to List"><i class="fas fa-plus"></i></button>
                        </div>
                        <textarea id="prescription-text" placeholder="Or type freely... (Format: Medicine | Dosage | Freq | Duration)"></textarea>
                    </div>
                    <button id="save-prescription-btn" class="action-btn">Send to Patient</button>
                <?php else: ?>
                    <div id="patient-prescription-view">
                        <p class="placeholder-text">Waiting for doctor...</p>
                    </div>
                <?php endif; ?>
                <div id="prescription-status"></div>
                <!-- Download for both, but usually patient uses it -->
                <button id="download-prescription-btn" class="action-btn secondary" style="display: none;">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Pass PHP data to JS -->
    <script>
        const CONFIG = {
            roomId: "appointment-<?php echo $appointment_id; ?>",
            userId: "<?php echo $user_id; ?>",
            userName: "<?php echo addslashes($display_name); ?>",
            isDoctor: <?php echo $is_doctor ? 'true' : 'false'; ?>,
            signalingUrl: window.location.origin,
            appointmentId: "<?php echo $appointment_id; ?>"
        };
    </script>

    <!-- Dependencies -->
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script src="assets/js/video-call.js"></script>
</body>
</html>
