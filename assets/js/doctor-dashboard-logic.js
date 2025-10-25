document.addEventListener('DOMContentLoaded', function() {
    const pendingAppointmentList = document.getElementById('pendingAppointmentList');
    const confirmedAppointmentList = document.getElementById('confirmedAppointmentList');
    const searchPatientInput = document.getElementById('searchPatient');
    const typeFilterSelect = document.getElementById('typeFilter');

    function fetchAndRenderAppointments() {
        const searchQuery = searchPatientInput ? searchPatientInput.value.toLowerCase() : '';
        const typeFilter = typeFilterSelect ? typeFilterSelect.value : 'all';

        fetch('../doctor/get_appointments.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(appointments => {
                // The original get_appointments.php returns an array directly, not an object with 'success' and 'appointments' keys.
                // So, 'appointments' here is directly the array of appointment objects.
                
                pendingAppointmentList.innerHTML = '';
                confirmedAppointmentList.innerHTML = '';

                appointments.forEach(appointment => {
                    const patientName = appointment.patient_name.toLowerCase();
                    const appointmentType = appointment.type.toLowerCase();

                    if ((searchQuery === '' || patientName.includes(searchQuery)) &&
                        (typeFilter === 'all' || appointmentType === typeFilter.toLowerCase())) {

                        const listItem = document.createElement('li');
                        listItem.className = 'doctor-list-item';

                        // Extract date and time from appointment_date (DATETIME format)
                        const appointmentDateTime = new Date(appointment.appointment_date);
                        const date = appointmentDateTime.toISOString().split('T')[0]; // YYYY-MM-DD
                        const time = appointmentDateTime.toTimeString().split(' ')[0].substring(0, 5); // HH:MM

                        // Helper function to get badge class based on status
                        function getStatusBadgeClass(status) {
                            switch (status) {
                                case 'Pending': return 'bg-warning';
                                case 'Scheduled': return 'bg-primary';
                                case 'Completed': return 'bg-success';
                                case 'Cancelled': return 'bg-danger';
                                default: return 'bg-secondary';
                            }
                        }

                        listItem.innerHTML = `
                            <div class="doctor-info">
                                <div class="doctor-avatar">
                                    <img src="/${appointment.patient_profile_pic || 'assets/images/default-avatar.png'}" alt="Patient Profile">
                                </div>
                                <div class="doctor-details">
                                    <h4>${appointment.patient_name}</h4>
                                    <p><strong>Reason:</strong> ${appointment.reason}</p>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <p><strong>Date:</strong> ${date}</p>
                                <p><strong>Time:</strong> ${time}</p>
                            </div>
                            <div class="doctor-info">
                                <p><strong>Type:</strong> ${appointment.type}</p>
                                <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></p>
                            </div>
                            <div class="doctor-info button-group">
                                ${appointment.status === 'Pending' ? `
                                    <button class="btn btn-success accept-btn" data-appointment-id="${appointment.id}" data-appointment-type="${appointment.type}">Accept</button>
                                    <button class="btn btn-danger cancel-btn" data-appointment-id="${appointment.id}">Cancel</button>
                                ` : ''}
                                ${appointment.status === 'Scheduled' ? `
                                    <button class="btn btn-primary complete-btn" data-appointment-id="${appointment.id}">Complete</button>
                                ` : ''}
                                <button class="btn btn-info message-btn"
                                        data-patient-id="${appointment.patient_user_id}"
                                        data-patient-name="${appointment.patient_name}"
                                        data-patient-profile-pic="${appointment.patient_profile_pic || 'assets/images/default-avatar.png'}"
                                        data-conversation-id="${appointment.conversation_id || ''}">
                                    Message
                                </button>
                            </div>
                        `;

                        if (appointment.status === 'Pending') {
                            pendingAppointmentList.appendChild(listItem);
                        } else if (appointment.status === 'Scheduled') {
                            confirmedAppointmentList.appendChild(listItem);
                        }
                    }
                });

                addEventListenersToButtons();
            })
            .catch(error => console.error('Error fetching appointments:', error));
    }

    function addEventListenersToButtons() {
        document.querySelectorAll('.message-btn').forEach(button => {
            button.addEventListener('click', function() {
                const patientUserId = this.dataset.patientId;
                const patientName = this.dataset.patientName;
                const patientProfilePic = this.dataset.patientProfilePic;
                const conversationId = this.dataset.conversationId;
                if (window.openMiniMessenger) {
                    window.openMiniMessenger(patientUserId, patientName, patientProfilePic, conversationId);
                } else {
                    console.error('openMiniMessenger function is not available.');
                }
            });
        });

        // Add event listeners for accept, cancel, complete buttons (assuming these exist in your PHP)
        document.querySelectorAll('.accept-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.dataset.appointmentId;
                const appointmentType = this.dataset.appointmentType; // Get the type
                fetch('../doctor/accept_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId, type: appointmentType })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchAndRenderAppointments(); // Refresh list
                    } else {
                        alert('Failed to accept appointment: ' + data.message);
                    }
                })
                .catch(error => console.error('Error accepting appointment:', error));
            });
        });

        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.dataset.appointmentId;
                fetch('../doctor/cancel_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchAndRenderAppointments(); // Refresh list
                    } else {
                        alert('Failed to cancel appointment: ' + data.message);
                    }
                })
                .catch(error => console.error('Error canceling appointment:', error));
            });
        });

        document.querySelectorAll('.complete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.dataset.appointmentId;
                fetch('../doctor/complete_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchAndRenderAppointments(); // Refresh list
                    } else {
                        alert('Failed to complete appointment: ' + data.message);
                    }
                })
                .catch(error => console.error('Error completing appointment:', error));
            });
        });
    }

    // Initial fetch and render
    fetchAndRenderAppointments();

    // Add event listeners for search and filter
    if (searchPatientInput) {
        searchPatientInput.addEventListener('input', fetchAndRenderAppointments);
    }
    if (typeFilterSelect) {
        typeFilterSelect.addEventListener('change', fetchAndRenderAppointments);
    }

    // Poll for new appointments every 30 seconds (adjust as needed)
    setInterval(fetchAndRenderAppointments, 30000);
});
