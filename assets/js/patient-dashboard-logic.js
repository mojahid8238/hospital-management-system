document.addEventListener('DOMContentLoaded', function () {
    const upcomingAppointmentList = document.getElementById('upcomingAppointmentList');
    const pendingAppointmentList = document.getElementById('pendingAppointmentList');
    const medicalHistoryTableBody = document.getElementById('medicalHistoryTableBody');
    const universalSearchInput = document.getElementById('universalSearch');
    const universalStatusFilter = document.getElementById('universalStatusFilter');
    const universalSortBy = document.getElementById('universalSortBy');

    // Stats Elements
    const totalVisitsCount = document.getElementById('totalVisitsCount');
    const activeRecordsCount = document.getElementById('activeRecordsCount');
    const nextVisitDate = document.getElementById('nextVisitDate');

    function fetchAndRenderPatientData() {
        const searchQuery = universalSearchInput ? universalSearchInput.value.toLowerCase() : '';
        const statusFilter = universalStatusFilter ? universalStatusFilter.value : 'all';
        const sortBy = universalSortBy ? universalSortBy.value : 'appointment_date_asc';

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

                let totalVisits = 0;
                let activeRecords = appointments.length;
                let soonestVisit = null;

                appointments.forEach(appointment => {
                    const appointmentDateTime = new Date(appointment.appointment_date);
                    const date = appointmentDateTime.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
                    const time = appointmentDateTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    if (appointment.status === 'Completed') totalVisits++;
                    if (appointment.status === 'Scheduled') {
                        if (!soonestVisit || appointmentDateTime < soonestVisit) {
                            soonestVisit = appointmentDateTime;
                        }
                    }

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
                        const listItem = document.createElement('div');
                        listItem.className = 'doctor-list-item';
                        listItem.innerHTML = `
                            <div class="doctor-info">
                                <div class="doctor-avatar">
                                    <img src="../${appointment.profile_pic || 'assets/images/default-avatar.png'}" alt="Doctor Profile">
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. ${appointment.doctor_name}</h4>
                                    <p>${appointment.specialization}</p>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <p><strong><i class="far fa-calendar-alt"></i></strong> ${date}</p>
                                <p><strong><i class="far fa-clock"></i></strong> ${time}</p>
                            </div>
                            <div class="doctor-info">
                                <p><i class="fas fa-notes-medical"></i> ${appointment.reason}</p>
                                <p><span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></p>
                            </div>
                            <div class="doctor-info button-group">
                                <button class="btn btn-outline-danger btn-sm cancel-btn" data-appointment-id="${appointment.id}"><i class="fas fa-times"></i> Cancel</button>
                                <button class="btn btn-outline-primary btn-sm chat-btn"
                                        data-doctor-id="${appointment.doctor_user_id}"
                                        data-doctor-name="${appointment.doctor_name}"
                                        data-profile-pic="${appointment.profile_pic || 'assets/images/default-avatar.png'}"
                                        data-conversation-id="${appointment.conversation_id || ''}">
                                    <i class="fas fa-comment"></i>
                                </button>
                            </div>
                        `;
                        pendingAppointmentList.appendChild(listItem);
                    } else if (appointment.status === 'Scheduled') {
                        const listItem = document.createElement('div');
                        listItem.className = 'doctor-list-item';
                        listItem.innerHTML = `
                            <div class="doctor-info">
                                <div class="doctor-avatar">
                                    <img src="../${appointment.profile_pic || 'assets/images/default-avatar.png'}" alt="Doctor Profile">
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. ${appointment.doctor_name}</h4>
                                    <p>${appointment.specialization}</p>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <p><strong><i class="far fa-calendar-alt"></i></strong> ${date}</p>
                                <p><strong><i class="far fa-clock"></i></strong> ${time}</p>
                            </div>
                            <div class="doctor-info">
                                <p><i class="fas fa-notes-medical"></i> ${appointment.reason}</p>
                                <p><span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></p>
                            </div>
                            <div class="doctor-info button-group">
                                <button class="btn btn-outline-primary btn-sm chat-btn"
                                        data-doctor-id="${appointment.doctor_user_id}"
                                        data-doctor-name="${appointment.doctor_name}"
                                        data-profile-pic="${appointment.profile_pic || 'assets/images/default-avatar.png'}"
                                        data-conversation-id="${appointment.conversation_id || ''}">
                                    <i class="fas fa-comment"></i> Message
                                </button>
                            </div>
                        `;
                        upcomingAppointmentList.appendChild(listItem);
                    } else if (appointment.status === 'Completed') {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td data-label="ID">#${appointment.id}</td>
                            <td data-label="Doctor">Dr. ${appointment.doctor_name}</td>
                            <td data-label="Specialization">${appointment.specialization}</td>
                            <td data-label="Date">${date} at ${time}</td>
                            <td data-label="Reason">${appointment.reason}</td>
                            <td data-label="Status"><span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.status}</span></td>
                            <td data-label="Type">${appointment.type}</td>
                        `;
                        medicalHistoryTableBody.appendChild(row);
                    }
                });

                // Update Stats
                if (totalVisitsCount) totalVisitsCount.innerText = totalVisits;
                if (activeRecordsCount) activeRecordsCount.innerText = activeRecords;
                if (nextVisitDate) {
                    if (soonestVisit) {
                        const datePart = soonestVisit.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
                        const timePart = soonestVisit.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false });
                        nextVisitDate.innerText = `${datePart}, ${timePart}`;
                        nextVisitDate.style.fontSize = '1.5rem'; // Keep consistent font size
                    } else {
                        nextVisitDate.innerText = 'No upcoming';
                        nextVisitDate.style.fontSize = '1.5rem';
                    }
                }

                // Show/hide sections based on data
                const confirmedSection = document.getElementById('confirmedAppointmentsSection');
                const pendingSection = document.getElementById('pendingAppointmentsSection');
                const historySection = document.getElementById('medicalHistorySection');
                const panelCard = document.querySelector('.container.panel-card');

                const hasUpcoming = upcomingAppointmentList.children.length > 0;
                const hasPending = pendingAppointmentList.children.length > 0;
                const hasHistory = medicalHistoryTableBody.children.length > 0;

                confirmedSection.style.display = hasUpcoming ? 'block' : 'none';
                pendingSection.style.display = hasPending ? 'block' : 'none';
                historySection.style.display = hasHistory ? 'block' : 'none';

                // Check if any filters are active
                const isFiltering = universalSearchInput.value !== '' ||
                    universalStatusFilter.value !== 'all' ||
                    universalSortBy.value !== 'appointment_date_asc';

                if (!hasUpcoming && !hasPending && !hasHistory && !isFiltering) {
                    panelCard.style.display = 'none';
                } else {
                    panelCard.style.display = 'block';
                }

                addEventListenersToButtons();
            })
            .catch(error => {
                console.error('Error fetching patient data:', error);
            });
    }



    function addEventListenersToButtons() {
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function () {
                if (!confirm('Are you sure you want to cancel this appointment?')) return;
                const appointmentId = this.dataset.appointmentId;
                fetch('../patient/cancel_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: appointmentId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) fetchAndRenderPatientData();
                    })
                    .catch(error => console.error('Error canceling appointment:', error));
            });
        });

        document.querySelectorAll('.chat-btn').forEach(button => {
            button.addEventListener('click', function () {
                const doctorId = this.dataset.doctorId;
                const doctorName = this.dataset.doctorName;
                const doctorProfilePic = this.dataset.profilePic;
                const conversationId = this.dataset.conversationId;
                if (window.openMiniMessenger) {
                    window.openMiniMessenger(doctorId, doctorName, doctorProfilePic, conversationId);
                }
            });
        });
    }

    function checkActiveCall() {
        fetch('../patient/check_active_call.php')
            .then(response => response.json())
            .then(data => {
                const existingNotification = document.getElementById('video-call-notification');
                if (data.active) {
                    if (!existingNotification) {
                        const notification = document.createElement('div');
                        notification.id = 'video-call-notification';
                        notification.style.cssText = `
                            position: fixed;
                            bottom: 20px;
                            right: 20px;
                            background: var(--primary-color);
                            color: white;
                            padding: 20px;
                            border-radius: 12px;
                            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                            z-index: 9999;
                            display: flex;
                            flex-direction: column;
                            gap: 10px;
                            animation: slideIn 0.5s ease;
                        `;
                        notification.innerHTML = `
                            <div style="font-weight: 700; font-size: 1.1rem;">
                                <i class="fas fa-video"></i> Video Call Started
                            </div>
                            <div style="font-size: 0.9rem;">
                                Dr. ${data.doctor_name} is waiting for you.
                            </div>
                            <button class="btn btn-light btn-sm" id="join-call-btn" style="background: white; color: var(--primary-color); border: none; font-weight: 600;">
                                Join Now
                            </button>
                        `;
                        document.body.appendChild(notification);

                        document.getElementById('join-call-btn').addEventListener('click', () => {
                            window.open(`../video_call.php?room=${data.room_name}&appointment_id=${data.appointment_id}`, '_blank');
                            notification.remove();
                        });
                    }
                } else {
                    if (existingNotification) {
                        existingNotification.remove();
                    }
                }
            })
            .catch(error => console.error('Error checking active call:', error));
    }

    // Add CSS for animation if not exists
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    if (universalSearchInput) universalSearchInput.addEventListener('input', fetchAndRenderPatientData);
    if (universalStatusFilter) universalStatusFilter.addEventListener('change', fetchAndRenderPatientData);
    if (universalSortBy) universalSortBy.addEventListener('change', fetchAndRenderPatientData);

    fetchAndRenderPatientData();
    setInterval(fetchAndRenderPatientData, 10000);

    // Check for active calls every 5 seconds
    checkActiveCall();
    setInterval(checkActiveCall, 5000);
});