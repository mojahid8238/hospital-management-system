import { printPrescription } from "./patient-prescriptions.js";
const localVideo = document.getElementById('local-video');
const remoteVideo = document.getElementById('remote-video');
const statusOverlay = document.getElementById('status-overlay');
const statusText = document.getElementById('status-text');
const waitingMessage = document.getElementById('waiting-message');

const audioBtn = document.getElementById('audio-btn');
const videoBtn = document.getElementById('video-btn');
const endCallBtn = document.getElementById('end-call-btn');

// State
let localStream;
let peerConnection;
let socket;
let isAudioMuted = false;
let isVideoOff = false;

// WebRTC Configuration
const rtcConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

async function init() {
    try {
        // 1. Get Local Media
        statusText.innerText = "Accessing Camera & Microphone...";
        localStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });
        localVideo.srcObject = localStream;

        // 2. Connect to Signaling Server
        statusText.innerText = "Connecting to Server...";
        socket = io(CONFIG.signalingUrl);

        socket.on('connect', () => {
            console.log('Connected to signaling server');
            statusOverlay.style.display = 'none';
            joinRoom();
        });

        setupSocketListeners();

    } catch (err) {
        console.error("Initialization Error:", err);
        statusText.innerText = "Error: " + err.message;
        statusText.style.color = "#ef4444";
    }
}

function joinRoom() {
    socket.emit('join-room', CONFIG.roomId, CONFIG.userId);
}

function setupSocketListeners() {
    // When a new user connects, we initiate the offer (if we are already there)
    // Or we wait for an offer if we just joined? 
    // Simple logic: The one who joins 'second' triggers 'user-connected' for the first.
    // The first user (already in room) receives 'user-connected' and creates the Offer.

    socket.on('user-connected', async (userId) => {
        console.log('User connected:', userId);
        waitingMessage.style.display = 'none';
        await createOffer();
    });

    socket.on('user-disconnected', () => {
        console.log('User disconnected');
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        remoteVideo.srcObject = null;
        waitingMessage.style.display = 'block';
        alert("Participant has left the call.");
    });

    socket.on('offer', async (data) => {
        await handleOffer(data.offer);
    });

    socket.on('answer', async (data) => {
        await handleAnswer(data.answer);
    });

    socket.on('ice-candidate', async (data) => {
        await handleIceCandidate(data.candidate);
    });

    socket.on('signal', (data) => {
        if (data.signal && data.signal.type === 'prescription-updated') {
            handlePrescriptionUpdate(data.signal.content);
        }
        if (data.signal && data.signal.type === 'call-ended') {
            alert("The call has been ended by the other participant.");
            window.close();
            window.location.href = CONFIG.isDoctor ? 'doctor/dashboard.php' : 'patient/dashboard.php';
        }
    });
}

// --- WebRTC Core Functions ---

function createPeerConnection() {
    if (peerConnection) return;

    peerConnection = new RTCPeerConnection(rtcConfig);

    // Add local tracks
    localStream.getTracks().forEach(track => {
        peerConnection.addTrack(track, localStream);
    });

    // Handle remote tracks
    peerConnection.ontrack = (event) => {
        console.log('Received remote track');
        remoteVideo.srcObject = event.streams[0];
        waitingMessage.style.display = 'none';
    };

    // Handle ICE candidates
    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            socket.emit('ice-candidate', {
                room: CONFIG.roomId,
                candidate: event.candidate
            });
        }
    };

    // Connection State Logging
    peerConnection.onconnectionstatechange = () => {
        console.log("Connection State:", peerConnection.connectionState);
    };
}

async function createOffer() {
    createPeerConnection();
    const offer = await peerConnection.createOffer();
    await peerConnection.setLocalDescription(offer);

    socket.emit('offer', {
        room: CONFIG.roomId,
        offer: offer
    });
}

async function handleOffer(offer) {
    createPeerConnection();
    await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
    const answer = await peerConnection.createAnswer();
    await peerConnection.setLocalDescription(answer);

    socket.emit('answer', {
        room: CONFIG.roomId,
        answer: answer
    });
}

async function handleAnswer(answer) {
    await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
}

async function handleIceCandidate(candidate) {
    if (peerConnection) {
        await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
    }
}

// --- UI Controls ---

audioBtn.addEventListener('click', () => {
    if (!localStream) return;
    isAudioMuted = !isAudioMuted;
    const audioTrack = localStream.getAudioTracks()[0];
    if (audioTrack) audioTrack.enabled = !isAudioMuted;

    if (isAudioMuted) {
        audioBtn.classList.add('active');
        audioBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
    } else {
        audioBtn.classList.remove('active');
        audioBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    }
});

videoBtn.addEventListener('click', () => {
    if (!localStream) return;
    isVideoOff = !isVideoOff;
    const videoTrack = localStream.getVideoTracks()[0];
    if (videoTrack) videoTrack.enabled = !isVideoOff;

    if (isVideoOff) {
        videoBtn.classList.add('active');
        videoBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
    } else {
        videoBtn.classList.remove('active');
        videoBtn.innerHTML = '<i class="fas fa-video"></i>';
    }
});

endCallBtn.addEventListener('click', async () => {
    if (confirm("End the call?")) {
        // Notify backend to end call
        try {
            await fetch('includes/end_call.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    appointment_id: CONFIG.appointmentId
                })
            });

            // Notify peer immediately
            if (socket) {
                socket.emit('signal', {
                    room: CONFIG.roomId,
                    signal: { type: 'call-ended' }
                });
            }

        } catch (err) {
            console.error("Error ending call:", err);
        }

        if (socket) socket.disconnect();
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        if (peerConnection) peerConnection.close();

        // Close tab or redirect
        window.location.href = CONFIG.isDoctor ? 'doctor/dashboard.php' : 'patient/dashboard.php';
        // window.close() is often blocked unless opened by script, but we can try
        window.close();
    }
});

// Backup: Handle tab close/reload
window.addEventListener('pagehide', () => {
    // connect.sendBeacon is more reliable for unload
    const data = JSON.stringify({ appointment_id: CONFIG.appointmentId });
    // sendBeacon sends as text/plain or blob. PHP expects json body.
    // We can use Blob to set correct type, but simple POST to includes/end_call.php 
    // needs to handle raw post or verify how it reads input.
    // php://input reads body.

    // Create a blob for JSON
    const blob = new Blob([data], { type: 'application/json' });
    navigator.sendBeacon('includes/end_call.php', blob);
});

// Initialize on load
init();

// --- Prescription Logic ---
const prescriptionBtn = document.getElementById('prescription-btn');
const prescriptionPanel = document.getElementById('prescription-panel');
const closePrescriptionBtn = document.getElementById('close-prescription');
const savePrescriptionBtn = document.getElementById('save-prescription-btn');
const prescriptionText = document.getElementById('prescription-text');
const patientPrescriptionView = document.getElementById('patient-prescription-view');
const prescriptionStatus = document.getElementById('prescription-status');
const downloadPrescriptionBtn = document.getElementById('download-prescription-btn');
const addMedBtn = document.getElementById('add-med-btn');

if (addMedBtn) {
    addMedBtn.addEventListener('click', () => {
        const name = document.getElementById('med-name').value.trim();
        const dosage = document.getElementById('med-dosage').value.trim();
        const freq = document.getElementById('med-freq').value.trim();
        const duration = document.getElementById('med-duration').value.trim();

        if (name) {
            const line = `${name} | ${dosage} | ${freq} | ${duration}\n`;
            prescriptionText.value += line;

            // Clear inputs
            document.getElementById('med-name').value = '';
            document.getElementById('med-dosage').value = '';
            document.getElementById('med-freq').value = '';
            document.getElementById('med-duration').value = '';
            document.getElementById('med-name').focus();
        }
    });
}

if (prescriptionBtn) {
    prescriptionBtn.addEventListener('click', () => {
        prescriptionPanel.style.display = 'flex';
        // If patient, fetch latest
        if (!CONFIG.isDoctor) {
            fetchPrescription();
        }
    });
}

if (closePrescriptionBtn) {
    closePrescriptionBtn.addEventListener('click', () => {
        prescriptionPanel.style.display = 'none';
    });
}

if (savePrescriptionBtn) {
    savePrescriptionBtn.addEventListener('click', async () => {
        let content = prescriptionText.value;

        // Auto-add current medicine-adder fields if not empty
        const nameInput = document.getElementById('med-name');
        if (nameInput && nameInput.value.trim()) {
            const name = nameInput.value.trim();
            const dosage = document.getElementById('med-dosage').value.trim();
            const freq = document.getElementById('med-freq').value.trim();
            const duration = document.getElementById('med-duration').value.trim();
            const line = `${name} | ${dosage} | ${freq} | ${duration}\n`;
            content += line;
            prescriptionText.value = content; // Update UI

            // Clear inputs
            nameInput.value = '';
            document.getElementById('med-dosage').value = '';
            document.getElementById('med-freq').value = '';
            document.getElementById('med-duration').value = '';
        }

        if (!content.trim()) return;

        savePrescriptionBtn.disabled = true;
        savePrescriptionBtn.innerText = "Sending...";

        try {
            const res = await fetch('includes/save_prescription.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    appointment_id: CONFIG.appointmentId,
                    content: content
                })
            });
            const data = await res.json();

            if (data.success) {
                prescriptionStatus.innerText = "Sent successfully!";
                prescriptionStatus.style.color = "#22c55e";

                // Notify via Socket
                if (socket) {
                    socket.emit('signal', {
                        room: CONFIG.roomId,
                        signal: { type: 'prescription-updated', content: content }
                    });
                }
            } else {
                prescriptionStatus.innerText = "Error: " + data.message;
                prescriptionStatus.style.color = "#ef4444";
            }
        } catch (err) {
            console.error(err);
            prescriptionStatus.innerText = "Network Error (" + err.message + ")";
        } finally {
            savePrescriptionBtn.disabled = false;
            savePrescriptionBtn.innerText = "Send to Patient";
        }
    });


    // Also listen for incoming signals (handled in setupSocketListeners generally, but we need to check type)
    // We already have socket.on('signal', ...) in server.js broadcast
    // In client setupSocketListeners:
}

// Enhance setupSocketListeners to handle generic signals if not already
// The current client code doesn't listen to 'signal' event, let's add it.

function handlePrescriptionUpdate(content) {
    if (CONFIG.isDoctor) return; // Doctor doesn't need to see this view update usually

    if (patientPrescriptionView) {
        patientPrescriptionView.innerText = content;
        patientPrescriptionView.style.whiteSpace = 'pre-wrap';
        downloadPrescriptionBtn.style.display = 'block';

        // Auto-open panel if closed? Maybe just a notification?
        // Let's auto-open for visibility
        prescriptionPanel.style.display = 'flex';
        alert("New Prescription Received!");
    }
}

async function fetchPrescription() {
    try {
        const res = await fetch(`includes/get_prescription.php?appointment_id=${CONFIG.appointmentId}`);
        const text = await res.text();
        try {
            const data = JSON.parse(text);
            if (data.success && data.content) {
                if (patientPrescriptionView) {
                    patientPrescriptionView.innerText = data.content;
                    downloadPrescriptionBtn.style.display = 'block';
                }
                if (prescriptionText) {
                    // If doctor opens it, restore active text
                    prescriptionText.value = data.content;
                }
            }
        } catch (e) {
            console.error("Invalid JSON from fetching prescription:", text.substring(0, 100)); // Log detailed error
        }
    } catch (err) {
        console.error("Failed to fetch prescription", err);
    }
}

if (downloadPrescriptionBtn) {
    downloadPrescriptionBtn.addEventListener('click', () => {
        const content = CONFIG.isDoctor ? prescriptionText.value : patientPrescriptionView.innerText;
        
        // Get doctor and patient names
        const doctorName = CONFIG.isDoctor ? CONFIG.userName : document.querySelector('.user-label span').innerText;
        const patientName = CONFIG.isDoctor ? document.querySelector('.user-label span').innerText : CONFIG.userName;
        
        const prescriptionData = {
            content: content,
            doctor_name: doctorName,
            // We don't have a real specialization here, so we'll add a default
            specialization: 'General Specialist',
            // Patient name is needed for the print layout
            patient_name: patientName,
        };

        const date = new Date().toLocaleDateString(undefined, {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
        });

        printPrescription(prescriptionData, date);
    });
}

