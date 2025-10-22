<?php
session_start();
// include('includes/auth.php'); // Ensure user is logged in
$room_name = "test-room";
if(isset($_GET['room'])) {
    $room_name = htmlspecialchars($_GET['room']);
}

$display_name = $_SESSION['name'] ?? 'Guest'; // Use the logged-in user's name
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Call</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        #meet {
            height: 100%;
            width: 100%;
        }
    </style>
</head>
<body>
    <div id="meet"></div>
    <script src="https://meet.jit.si/external_api.js"></script>
    <script>
        const domain = 'meet.jit.si';
                    const options = {
                        roomName: '<?php echo $room_name; ?>',
                        width: '100%',
                        height: '100%',
                        parentNode: document.querySelector('#meet'),
                        userInfo: {
                            displayName: '<?php echo htmlspecialchars($display_name); ?>'
                        },
                        configOverwrite: {
                            prejoinPageEnabled: false
                        }
                    };
                    const api = new JitsiMeetExternalAPI(domain, options);    </script>
</body>
</html>