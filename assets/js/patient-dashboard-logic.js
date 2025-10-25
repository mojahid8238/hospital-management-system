document.addEventListener('DOMContentLoaded', function() {
    const upcomingAppointmentList = document.getElementById('upcomingAppointmentList');
    const pendingAppointmentList = document.getElementById('pendingAppointmentList');
    const medicalHistoryTableBody = document.getElementById('medicalHistoryTableBody');
    const universalSearchInput = document.getElementById('universalSearch');
    const universalStatusFilter = document.getElementById('universalStatusFilter');
    const universalSortBy = document.getElementById('universalSortBy');

    function fetchAndRenderPatientData() {
        const searchQuery = universalSearchInput ? universalSearchInput.value.toLowerCase() : '';
        const statusFilter = universalStatusFilter ? universalStatusFilter.value : 'all';
        const sortBy = universalSortBy ? universalSortBy.value : 'appointment_date_asc';

        // The original get_patient_appointments.php returns an array of appointments directly.
        // It does not separate medical history, so we'll assume completed appointments are medical history.
        fetch(`../patient/get_patient_appointments.php?search=${searchQuery}&status=${statusFilter}&sort=${sortBy}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(appointments => {
                upcomingAppointmentList.innerHTML = '';
                pendingAppointmentList.innerHTML = '';
                medicalHistoryTableBody.innerHTML = '';

                appointments.forEach(appointment => {
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

                    if (appointment.status === 'Pending') {
                        const listItem = document.createElement('li');
                        listItem.className = 'doctor-list-item'; // Reusing doctor-list-item class for styling consistency
                        listItem.innerHTML = `
                            <div class="doctor-info">
                                <div class="doctor-avatar">
                                    <img src="/${appointment.profile_pic || 'assets/images/default-avatar.png'}?t=${new Date().getTime()}" alt="Doctor Profile" class="user-profile-pic">
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. ${appointment.doctor_name}</h4>
                                    <p><strong>Specialization:</strong> ${appointment.specialization}</p>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <p><strong>Date:</strong> ${date}</p>
                                <p><strong>Time:</strong> ${time}</p>
                            </div>
                            <div class="doctor-info">
                                <p><strong>Reason:</strong> ${appointment.reason}</p>
                                <p><strong>Type:</strong> ${appointment.type}</p>
                                <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></p>
                            </div>
                            <div class="doctor-info">
                                <button class="btn btn-danger cancel-btn" data-appointment-id="${appointment.id}">Cancel</button>
                                <button class="btn btn-info chat-btn"
                                        data-doctor-id="${appointment.doctor_user_id}"
                                        data-doctor-name="${appointment.doctor_name}"
                                        data-profile-pic="${appointment.profile_pic || 'assets/images/default-avatar.png'}"
                                        data-conversation-id="${appointment.conversation_id || ''}">
                                    Message
                                </button>
                            </div>
                        `;
                        pendingAppointmentList.appendChild(listItem);
                    } else if (appointment.status === 'Scheduled') {
                        const listItem = document.createElement('li');
                        listItem.className = 'doctor-list-item'; // Reusing doctor-list-item class for styling consistency
                        listItem.innerHTML = `
                            <div class="doctor-info">
                                <div class="doctor-avatar">
                                    <img src="/${appointment.profile_pic || 'assets/images/default-avatar.png'}?t=${new Date().getTime()}" alt="Doctor Profile" class="user-profile-pic">
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. ${appointment.doctor_name}</h4>
                                    <p><strong>Specialization:</strong> ${appointment.specialization}</p>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <p><strong>Date:</strong> ${date}</p>
                                <p><strong>Time:</strong> ${time}</p>
                            </div>
                            <div class="doctor-info">
                                <p><strong>Reason:</strong> ${appointment.reason}</p>
                                <p><strong>Type:</strong> ${appointment.type}</p>
                                <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></p>
                            </div>
                            <div class="doctor-info">
                                <button class="btn btn-info chat-btn"
                                        data-doctor-id="${appointment.doctor_user_id}"
                                        data-doctor-name="${appointment.doctor_name}"
                                        data-profile-pic="${appointment.profile_pic || 'assets/images/default-avatar.png'}"
                                        data-conversation-id="${appointment.conversation_id || ''}">
                                    Message
                                </button>
                            </div>
                        `;
                        upcomingAppointmentList.appendChild(listItem);
                    } else if (appointment.status === 'Completed') {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${appointment.id}</td>
                            <td>
                                <img src="/${appointment.profile_pic || 'assets/images/default-avatar.png'}?t=${new Date().getTime()}" alt="Doctor Profile" class="user-profile-pic-small">
                            </td>
                            <td>Dr. ${appointment.doctor_name}</td>
                            <td>${appointment.specialization}</td>
                            <td>${date} ${time}</td>
                            <td>${appointment.reason}</td>
                            <td><span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></td>
                            <td>${appointment.type}</td>
                        `;
                        medicalHistoryTableBody.appendChild(row);
                    }
                });
                addEventListenersToButtons(); // Call after rendering appointments
            })
            .catch(error => {
                console.error('Error fetching patient data:', error);
                upcomingAppointmentList.innerHTML = '<li>Error loading data.</li>';
                pendingAppointmentList.innerHTML = '<li>Error loading data.</li>';
                medicalHistoryTableBody.innerHTML = '<tr><td colspan="4">Error loading data.</td></tr>';
            });
    }

    function addEventListenersToButtons() {
        // Event listener for Cancel button
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.dataset.appointmentId;
                fetch('../patient/cancel_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchAndRenderPatientData(); // Refresh list
                    } else {
                        alert('Failed to cancel appointment: ' + data.message);
                    }
                })
                .catch(error => console.error('Error canceling appointment:', error));
            });
        });

        // Event listener for Chat button
        document.querySelectorAll('.chat-btn').forEach(button => {
            button.addEventListener('click', function() {
                const doctorId = this.dataset.doctorId;
                const doctorName = this.dataset.doctorName;
                const doctorProfilePic = this.dataset.profilePic; // Note: data-profile-pic
                const conversationId = this.dataset.conversationId;
                if (window.openMiniMessenger) {
                    window.openMiniMessenger(doctorId, doctorName, doctorProfilePic, conversationId);
                } else {
                    console.error('openMiniMessenger function is not available.');
                }
            });
        });
    }

    // Add event listeners for search, filter, and sort
    if (universalSearchInput) universalSearchInput.addEventListener('input', fetchAndRenderPatientData);
    if (universalStatusFilter) universalStatusFilter.addEventListener('change', fetchAndRenderPatientData);
    if (universalSortBy) universalSortBy.addEventListener('change', fetchAndRenderPatientData);

    // Initial data fetch
    fetchAndRenderPatientData();

    // Poll for new appointments every 5 seconds
    setInterval(fetchAndRenderPatientData, 5000);
});