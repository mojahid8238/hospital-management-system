document.addEventListener('DOMContentLoaded', function () {
    const conversationsList = document.getElementById('conversationItems');
    const chatHeader = document.getElementById('chatHeader');
    const chatMessages = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const uploadImageBtn = document.getElementById('uploadImageBtn');
    const imageInput = document.getElementById('imageInput');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const clearImagePreviewBtn = document.getElementById('clearImagePreview');

    let activeConversationId = null;
    let activeReceiverId = null;
    let activeReceiverProfilePic = null;

    const otherParticipantUserId = window.otherParticipantUserId;
    const initialAppointmentId = window.initialAppointmentId;

    if (otherParticipantUserId) {
        activeReceiverId = otherParticipantUserId;
        chatHeader.innerHTML = `<h3>Chat with ${window.otherParticipantName || 'User ID: ' + otherParticipantUserId}</h3>`;
        chatMessages.innerHTML = '<div class="alert alert-info">Start a new conversation.</div>';
    }

    function fetchConversations(selectInitial = false) {
        fetch('../messaging/get_conversations.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderConversations(data.conversations, selectInitial);
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
            listItem.dataset.profilePic = conv.other_participant_profile_pic;

            const profilePic = conv.other_participant_profile_pic ? `/hospital-management-system/${conv.other_participant_profile_pic}` : '/hospital-management-system/assets/images/default-avatar.png';

            listItem.innerHTML = `
                <img src="${profilePic}" alt="Avatar">
                <div class="conversation-details">
                    <h4>${conv.other_participant_name}</h4>
                    <p>${conv.last_message || ''}</p>
                </div>
                ${conv.unread_count > 0 ? `<span class="unread-dot"></span>` : ''}
            `;
            listItem.addEventListener('click', () => {
                activeConversationId = conv.conversation_id;
                activeReceiverId = conv.other_participant_id;
                activeReceiverProfilePic = conv.other_participant_profile_pic;
                chatHeader.innerHTML = `<img src="${profilePic}" alt="Avatar"><h3>${conv.other_participant_name}</h3>`;
                fetchMessages(activeConversationId);
                document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
                listItem.classList.add('active');

                if (activeConversationId) {
                    fetch('../messaging/mark_as_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ conversation_id: activeConversationId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const unreadDot = listItem.querySelector('.unread-dot');
                            if (unreadDot) {
                                unreadDot.remove();
                            }
                        }
                    });
                }
            });
            conversationsList.appendChild(listItem);

            if (selectInitial && otherParticipantUserId && conv.other_participant_id == otherParticipantUserId) {
                listItem.click();
                initialConversationFound = true;
            }
        });

        if (selectInitial && otherParticipantUserId && !initialConversationFound) {
            activeConversationId = null;
            activeReceiverId = otherParticipantUserId;
            chatHeader.innerHTML = `<h3>Chat with ${window.otherParticipantName}</h3>`;
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
            const messageWrapper = document.createElement('div');
            messageWrapper.classList.add('message-wrapper');

            const messageElement = document.createElement('div');
            messageElement.classList.add('message');

            if (msg.sender_id === currentUserId) {
                messageWrapper.classList.add('sent');
                messageElement.classList.add('sent');
            } else {
                messageWrapper.classList.add('received');
                messageElement.classList.add('received');
            }

            let messageContent = '';
            if (msg.message_type === 'image') {
                messageContent = `<img src="/hospital-management-system/${msg.message_content}" class="message-image">`;
            } else {
                messageContent = `<p>${msg.message_content}</p>`;
            }

            messageElement.innerHTML = messageContent;

            const timestampElement = document.createElement('span');
            timestampElement.classList.add('timestamp');
            timestampElement.textContent = new Date(msg.timestamp).toLocaleTimeString();

            messageWrapper.appendChild(messageElement);
            messageWrapper.appendChild(timestampElement);
            chatMessages.appendChild(messageWrapper);
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
        const messageContent = messageInput.value.trim();
        const imageFile = imageInput.files[0];

        if (messageContent === '' && !imageFile) {
            return;
        }

        const formData = new FormData();
        formData.append('receiver_id', activeReceiverId);
        formData.append('message_content', messageContent);
        if (imageFile) {
            formData.append('image', imageFile);
        }
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
                imageInput.value = '';
                imagePreviewContainer.style.display = 'none';
                imagePreview.src = '#';
                if (data.conversation_id) {
                    activeConversationId = data.conversation_id;
                }
                fetchMessages(activeConversationId);
                fetchConversations();
            }
        });
    }

    uploadImageBtn.addEventListener('click', () => imageInput.click());

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.src = '#';
            imagePreviewContainer.style.display = 'none';
        }
    });

    clearImagePreviewBtn.addEventListener('click', function() {
        imageInput.value = '';
        imagePreview.src = '#';
        imagePreviewContainer.style.display = 'none';
    });

    sendMessageBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    fetchConversations(true);

    setInterval(() => {
        if (activeConversationId) {
            fetchMessages(activeConversationId);
        }
        fetchConversations();
    }, 5000);
});
