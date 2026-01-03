document.addEventListener('DOMContentLoaded', function () {
    const pendingAppointmentList = document.getElementById('pendingAppointmentList');
    const confirmedAppointmentList = document.getElementById('confirmedAppointmentList');
    const searchPatientInput = document.getElementById('searchPatient');
    const typeFilterSelect = document.getElementById('typeFilter'); // This is now the sole filter for type
    const SortBy = document.getElementById('SortBy'); // New sort dropdown

    // Stats Elements
    const totalAppointmentsCount = document.getElementById('totalAppointmentsCount');
    const pendingAppointmentsCount = document.getElementById('pendingAppointmentsCount');
    const completedAppointmentsCount = document.getElementById('completedAppointmentsCount');

    function fetchAndRenderAppointments() {
        const searchQuery = searchPatientInput ? searchPatientInput.value.toLowerCase() : '';
        // typeFilterSelect now provides the 'type' filter (all, Online, Offline)
        const typeFilterValue = typeFilterSelect ? typeFilterSelect.value : 'all'; 
        
        // Handle sorting
        const sortValue = SortBy ? SortBy.value : 'appointment_date_asc';
        const lastIndex = sortValue.lastIndexOf('_');
        const sortBy = sortValue.substring(0, lastIndex);
        const order = sortValue.substring(lastIndex + 1);

        fetch(`../doctor/get_appointments.php?search=${searchQuery}&status=${typeFilterValue}&sort_by=${sortBy}&order=${order}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(appointments => {
                pendingAppointmentList.innerHTML = '';
                confirmedAppointmentList.innerHTML = '';

                let total = 0;
                let pending = 0;
                let completed = 0;

                appointments.forEach(appointment => {
                    total++;
                    if (appointment.status === 'Pending') pending++;
                    if (appointment.status === 'Completed') completed++;

                    const listItem = document.createElement('div');
                    listItem.className = 'doctor-list-item';

                    const appointmentDateTime = new Date(appointment.appointment_date);
                    const date = appointmentDateTime.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
                    const time = appointmentDateTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

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
                                <p><i class="fas fa-notes-medical"></i> ${appointment.reason}</p>
                            </div>
                        </div>
                        <div class="doctor-info">
                            <p><strong><i class="far fa-calendar-alt"></i></strong> ${date}</p>
                            <p><strong><i class="far fa-clock"></i></strong> ${time}</p>
                        </div>
                        <div class="doctor-info">
                            <p><strong>Type:</strong> ${appointment.type}</p>
                            <p><span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></p>
                        </div>
                        <div class="doctor-info button-group">
                            ${appointment.status === 'Pending' ? `
                                <button class="btn btn-primary btn-sm accept-btn" data-appointment-id="${appointment.id}" data-appointment-type="${appointment.type}"><i class="fas fa-check"></i> Accept</button>
                                <button class="btn btn-danger btn-sm cancel-btn" data-appointment-id="${appointment.id}"><i class="fas fa-times"></i></button>
                            ` : ''}
                            ${appointment.status === 'Scheduled' ? `
                                <button class="btn btn-success btn-sm complete-btn" data-appointment-id="${appointment.id}"><i class="fas fa-check-double"></i> Done</button>
                            ` : ''}
                            <button class="btn btn-outline-primary btn-sm message-btn"
                                    data-patient-id="${appointment.patient_user_id}"
                                    data-patient-name="${appointment.patient_name}"
                                    data-patient-profile-pic="${appointment.patient_profile_pic || 'assets/images/default-avatar.png'}"
                                    data-conversation-id="${appointment.conversation_id || ''}">
                                <i class="fas fa-comment"></i>
                            </button>
                        </div>
                    `;

                    if (appointment.status.toLowerCase() === 'pending') {
                        pendingAppointmentList.appendChild(listItem);
                    } else if (appointment.status.toLowerCase() === 'scheduled') {
                        confirmedAppointmentList.appendChild(listItem);
                    }
                });

                // Update Stats
                if (totalAppointmentsCount) totalAppointmentsCount.innerText = total;
                if (pendingAppointmentsCount) pendingAppointmentsCount.innerText = pending;
                if (completedAppointmentsCount) completedAppointmentsCount.innerText = completed;
                // Show/hide sections based on data
                const pendingCard = document.getElementById('pendingAppointmentsCard');
                const confirmedCard = document.getElementById('confirmedAppointmentsCard');
                const panelCard = document.querySelector('.container.panel-card');

                const hasPending = pendingAppointmentList.children.length > 0;
                const hasConfirmed = confirmedAppointmentList.children.length > 0;

                pendingCard.style.display = hasPending ? 'block' : 'none';
                confirmedCard.style.display = hasConfirmed ? 'block' : 'none';

                // Check if any filters are active
                const isFiltering = searchPatientInput.value !== '' || 
                                    typeFilterSelect.value !== 'all' || // Now type filter
                                    SortBy.value !== 'appointment_date_asc'; // New sort filter

                if (!hasPending && !hasConfirmed && !isFiltering) {
                    panelCard.style.display = 'none';
                } else {
                    panelCard.style.display = 'block';
                }

                addEventListenersToButtons();
            })
            .catch(error => console.error('Error fetching appointments:', error));
    }

    function addEventListenersToButtons() {
        document.querySelectorAll('.message-btn').forEach(button => {
            button.addEventListener('click', function () {
                const patientUserId = this.dataset.patientId;
                const patientName = this.dataset.patientName;
                const patientProfilePic = this.dataset.patientProfilePic;
                const conversationId = this.dataset.conversationId;
                if (window.openMiniMessenger) {
                    window.openMiniMessenger(patientUserId, patientName, patientProfilePic, conversationId);
                }
            });
        });

        document.querySelectorAll('.accept-btn').forEach(button => {
            button.addEventListener('click', function () {
                const appointmentId = this.dataset.appointmentId;
                const appointmentType = this.dataset.appointmentType;
                fetch('../doctor/accept_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId, type: appointmentType })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) fetchAndRenderAppointments();
                    })
                    .catch(error => console.error('Error accepting appointment:', error));
            });
        });

        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function () {
                if (!confirm('Are you sure you want to cancel this appointment?')) return;
                const appointmentId = this.dataset.appointmentId;
                fetch('../doctor/cancel_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) fetchAndRenderAppointments();
                    })
                    .catch(error => console.error('Error canceling appointment:', error));
            });
        });

        document.querySelectorAll('.complete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const appointmentId = this.dataset.appointmentId;
                fetch('../doctor/complete_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) fetchAndRenderAppointments();
                    })
                    .catch(error => console.error('Error completing appointment:', error));
            });
        });
    }

    if (searchPatientInput) searchPatientInput.addEventListener('input', fetchAndRenderAppointments);
    if (typeFilterSelect) typeFilterSelect.addEventListener('change', fetchAndRenderAppointments); // Re-added typeFilterSelect event listener
    if (SortBy) SortBy.addEventListener('change', fetchAndRenderAppointments); // New sort event listener

    fetchAndRenderAppointments();
    setInterval(fetchAndRenderAppointments, 10000); // Polling every 10s
});
