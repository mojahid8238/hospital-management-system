document.addEventListener('DOMContentLoaded', function () {
    const conversationsList = document.getElementById('conversationItems');
    const chatHeader = document.getElementById('chatHeader');
    const chatMessages = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');

    let activeConversationId = null;
    let activeReceiverId = null;

    // Variables passed from PHP
    const otherParticipantUserId = window.otherParticipantUserId;
    console.log('JavaScript Debug - otherParticipantUserId from window:', otherParticipantUserId);
    const initialAppointmentId = window.initialAppointmentId;
    const targetDoctorId = window.targetDoctorId;
    const targetPatientId = window.targetPatientId;

    // If a specific participant is targeted, initialize activeReceiverId immediately
    if (otherParticipantUserId) {
        activeReceiverId = otherParticipantUserId;
        console.log('activeReceiverId after initialization:', activeReceiverId);
        // You might want to fetch the other participant's name here if not already known
        chatHeader.innerHTML = `<h3>Chat with ${window.otherParticipantName || 'User ID: ' + otherParticipantUserId}</h3>`; // Use name if available
        chatMessages.innerHTML = '<div class="alert alert-info">Start a new conversation.</div>';
    }

    function fetchConversations(selectInitial = false) {
        fetch('../messaging/get_conversations.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderConversations(data.conversations);
                }
            });
    }

    function renderConversations(conversations, selectInitial) {
        conversationsList.innerHTML = '';
        let initialConversationFound = false;
        conversations.forEach(conv => {
            const listItem = document.createElement('li');
            listItem.className = 'conversation-item';
            listItem.dataset.conversationId = conv.conversation_id;
            listItem.dataset.receiverId = conv.other_participant_id;

            const profilePic = conv.other_participant_profile_pic ? `/hospital-management-system/${conv.other_participant_profile_pic}` : '/hospital-management-system/assets/images/default-avatar.png';

            listItem.innerHTML = `
                <img src="${profilePic}" alt="Avatar">
                <div class="conversation-details">
                    <h4>${conv.other_participant_name}</h4>
                    <p>${conv.last_message || ''}</p>
                </div>
                ${conv.unread_count > 0 ? `<span class="unread-dot">.</span>` : ''}
            `;
            listItem.addEventListener('click', () => {
                activeConversationId = conv.conversation_id;
                activeReceiverId = conv.other_participant_id;
                chatHeader.innerHTML = `<h3>${conv.other_participant_name}</h3>`;
                fetchMessages(activeConversationId);
                document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
                listItem.classList.add('active');

                // Mark messages as read only if activeConversationId is set
                if (activeConversationId) {
                    console.log('Calling mark_as_read.php for conversation_id:', activeConversationId);
                    fetch('../messaging/mark_as_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ conversation_id: activeConversationId })
                    })
                .then(response => response.json())
                .then(data => {
                    console.log('mark_as_read.php response:', data);
                    if (data.success) {
                        console.log('Messages marked as read successfully.');
                        // Remove unread count badge from UI
                        const unreadCountBadge = listItem.querySelector('.unread-count');
                        if (unreadCountBadge) {
                            unreadCountBadge.remove();
                        }
                    } else {
                        console.error('Failed to mark messages as read:', data.message);
                    }
                })
                                    .catch(error => console.error('Error marking messages as read:', error));
                                }            });
            conversationsList.appendChild(listItem);

            // Check if this is the initial conversation to select
            if (selectInitial && otherParticipantUserId && conv.other_participant_id == otherParticipantUserId) {
                listItem.click(); // Simulate click to activate
                initialConversationFound = true;
            }
        });

        // If initial participant was specified but no existing conversation found, prepare for new chat
        if (selectInitial && otherParticipantUserId && !initialConversationFound) {
            activeConversationId = null; // No existing conversation
            activeReceiverId = otherParticipantUserId;
            // You might want to fetch the other participant's name here if not already known
            chatHeader.textContent = `Chat with User ID: ${otherParticipantUserId}`; // Placeholder
            chatMessages.innerHTML = '<div class="alert alert-info">Start a new conversation.</div>';
        }
    }

    function fetchMessages(conversationId) {
        if (!conversationId) return;
        fetch(`../messaging/get_messages.php?conversation_id=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages);
                }
            });
    }

    function renderMessages(messages) {
        chatMessages.innerHTML = '';
        messages.forEach(msg => {
            const messageElement = document.createElement('div');
            messageElement.classList.add('message');
            if (msg.sender_id === currentUserId) {
                messageElement.classList.add('sent');
            } else {
                messageElement.classList.add('received');
            }
            messageElement.innerHTML = `
                <p>${msg.message_content}</p>
                <span class="timestamp">${new Date(msg.timestamp).toLocaleTimeString()}</span>
            `;
            chatMessages.appendChild(messageElement);
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
        const messageContent = messageInput.value.trim();
        console.log('sendMessage called.');
        console.log('messageContent:', messageContent);
        console.log('activeReceiverId:', activeReceiverId);
        if (messageContent === '' || !activeReceiverId) {
            console.log('Message content is empty or activeReceiverId is not set. Returning.');
            return;
        }

        const formData = new FormData();
        formData.append('receiver_id', activeReceiverId);
        formData.append('message_content', messageContent);
        if (initialAppointmentId) {
            formData.append('appointment_id', initialAppointmentId);
        }

        fetch('../messaging/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                if (data.conversation_id) {
                    activeConversationId = data.conversation_id;
                }
                fetchMessages(activeConversationId);
            }
        });
    }

    sendMessageBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Initial load
    if (otherParticipantUserId) {
        // Perform initial fetch for the targeted participant
        fetchConversations(true);
        if (activeConversationId) {
            fetchMessages(activeConversationId);
        }
    } else {
        // Perform initial fetch for general conversations
        fetchConversations();
    }

    // Set up polling: faster initial poll, then normal interval
    setTimeout(() => {
        setInterval(() => {
            fetchConversations();
            if (activeConversationId) {
                fetchMessages(activeConversationId);
            }
        }, 5000); // Normal polling interval
    }, 1000); // Shorter delay for the first poll after initial load
});
