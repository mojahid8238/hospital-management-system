function initMessaging() {
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
    const directoryToggle = document.getElementById('directoryToggle');
    const convSearch = document.getElementById('convSearch');

    if (!conversationsList) return; // Not on messaging page

    let activeConversationId = null;
    let activeReceiverId = null;
    let activeReceiverProfilePic = null;
    let viewMode = window.userRole === 'admin' ? 'directory' : 'conversations';

    const currentUserId = window.currentUserId;
    const otherParticipantUserId = window.otherParticipantUserId;
    const initialAppointmentId = window.initialAppointmentId;

    // Handle Directory Toggle
    if (directoryToggle) {
        directoryToggle.addEventListener('click', () => {
            if (viewMode === 'conversations') {
                viewMode = 'directory';
                directoryToggle.innerHTML = '<i class="fas fa-comments"></i> Chats';
                convSearch.placeholder = 'Search users...';
                fetchDirectory();
            } else {
                viewMode = 'conversations';
                directoryToggle.innerHTML = '<i class="fas fa-address-book"></i> Directory';
                convSearch.placeholder = 'Search conversations...';
                fetchConversations();
            }
        });
    }

    function fetchConversations(selectInitial = false) {
        if (viewMode !== 'conversations') return;
        fetch('../messaging/get_conversations.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderConversations(data.conversations, selectInitial);
                }
            });
    }

    function fetchDirectory() {
        if (viewMode !== 'directory') return;
        fetch('../messaging/get_all_users.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderDirectory(data.users);
                }
            });
    }

    function renderDirectory(users) {
        conversationsList.innerHTML = '';
        users.forEach(user => {
            const div = document.createElement('div');
            div.className = 'conversation-item';
            div.dataset.userId = user.user_id;

            const isAdmin = user.role === 'admin';
            const badgeHtml = isAdmin ? '<span style="background: var(--gradient-primary); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.65rem; font-weight: 700; margin-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Admin</span>' : '';

            div.innerHTML = `
                <img src="${profilePic}" alt="Avatar">
                <div class="conv-meta">
                    <h4>${user.name}${badgeHtml}</h4>
                    <p style="text-transform: capitalize;">${user.role}</p>
                </div>
            `;

            div.addEventListener('click', () => {
                activeReceiverId = user.user_id;
                activeConversationId = null; // Reset to check for existence or create new

                chatHeader.innerHTML = `
                    <div class="active-chat-user">
                        <img src="${profilePic}" alt="Avatar">
                        <div>
                            <h3 style="margin:0; font-size:1rem; font-weight:700;">${user.name}${badgeHtml}</h3>
                            <small class="text-muted">${user.role}</small>
                        </div>
                    </div>
                `;

                document.getElementById('chatInputArea').style.display = 'block';
                chatMessages.innerHTML = '<div class="welcome-chat"><p>Loading conversation history...</p></div>';

                // Try to find if a conversation already exists
                fetch(`../messaging/get_messages.php?receiver_id=${activeReceiverId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            activeConversationId = data.conversation_id;
                            renderMessages(data.messages);
                        } else {
                            chatMessages.innerHTML = '<div class="welcome-chat"><p>Start a new conversation with ' + user.name + '</p></div>';
                        }
                    });

                document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
                div.classList.add('active');
            });
            conversationsList.appendChild(div);
        });
    }

    function renderConversations(conversations, selectInitial) {
        conversationsList.innerHTML = '';
        let initialConversationFound = false;
        conversations.forEach(conv => {
            const div = document.createElement('div');
            div.className = 'conversation-item';
            div.dataset.conversationId = conv.conversation_id;
            div.dataset.receiverId = conv.other_participant_id;

            if (activeConversationId === conv.conversation_id) div.classList.add('active');

            const profilePic = conv.other_participant_profile_pic ? `../${conv.other_participant_profile_pic.replace(/^\//, '')}` : '../assets/images/default-avatar.png';

            const isAdmin = conv.other_participant_role === 'admin';
            const badgeHtml = isAdmin ? '<span style="background: var(--gradient-primary); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.65rem; font-weight: 700; margin-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Admin</span>' : '';

            div.innerHTML = `
                <img src="${profilePic}" alt="Avatar">
                <div class="conv-meta">
                    <h4>${conv.other_participant_name}${badgeHtml}</h4>
                    <p>${conv.last_message || 'Start a conversation...'}</p>
                </div>
                ${conv.unread_count > 0 ? `<span class="unread-dot"></span>` : ''}
            `;
            div.addEventListener('click', () => {
                activeConversationId = conv.conversation_id;
                activeReceiverId = conv.other_participant_id;
                activeReceiverProfilePic = conv.other_participant_profile_pic;

                chatHeader.innerHTML = `
                    <div class="active-chat-user">
                        <img src="${profilePic}" alt="Avatar">
                        <div>
                            <h3 style="margin:0; font-size:1rem; font-weight:700;">${conv.other_participant_name}${badgeHtml}</h3>
                            <small style="color:var(--success); font-weight:500;">Online</small>
                        </div>
                    </div>
                `;

                document.getElementById('chatInputArea').style.display = 'block';
                fetchMessages(activeConversationId);
                document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
                div.classList.add('active');

                if (activeConversationId) {
                    fetch('../messaging/mark_as_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ conversation_id: activeConversationId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const unreadDot = div.querySelector('.unread-dot');
                                if (unreadDot) unreadDot.remove();
                            }
                        });
                }
            });
            conversationsList.appendChild(div);

            if (selectInitial && otherParticipantUserId && conv.other_participant_id == otherParticipantUserId) {
                div.click();
                initialConversationFound = true;
            }
        });

        if (selectInitial && otherParticipantUserId && !initialConversationFound) {
            activeConversationId = null;
            activeReceiverId = otherParticipantUserId;
            chatHeader.innerHTML = `<div class="active-chat-user"><h3>${window.otherParticipantName}</h3></div>`;
            document.getElementById('chatInputArea').style.display = 'block';
            chatMessages.innerHTML = '<div class="welcome-chat"><p>No messages yet. Send a message to start chatting!</p></div>';
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
        if (messages.length === 0) {
            chatMessages.innerHTML = '<div class="welcome-chat"><p>Start your conversation...</p></div>';
            return;
        }

        messages.forEach(msg => {
            const messageWrapper = document.createElement('div');
            messageWrapper.classList.add('message-wrapper');

            const messageElement = document.createElement('div');
            messageElement.classList.add('message');

            if (msg.sender_id == currentUserId) {
                messageWrapper.classList.add('sent');
            } else {
                messageWrapper.classList.add('received');
            }

            let messageContent = '';
            if (msg.message_type === 'image') {
                const imgPath = `../${msg.message_content.replace(/^\//, '')}`;
                messageContent = `<img src="${imgPath}" class="message-image">`;
            } else {
                messageContent = `<p>${msg.message_content}</p>`;
            }

            messageElement.innerHTML = messageContent;

            const adminLabel = (msg.sender_role === 'admin' && msg.sender_id != currentUserId) ? '<span style="color:var(--primary-color); font-weight:800; font-size: 0.65rem; margin-right: 6px; letter-spacing: 0.5px;">Admin</span>' : '';

            const timestampElement = document.createElement('span');
            timestampElement.classList.add('timestamp');
            timestampElement.innerHTML = adminLabel + new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            messageWrapper.appendChild(messageElement);
            messageWrapper.appendChild(timestampElement);
            chatMessages.appendChild(messageWrapper);
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
        const messageInput = document.getElementById('messageInput');
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
                    if (viewMode === 'conversations') fetchConversations();
                }
            });
    }

    if (uploadImageBtn) uploadImageBtn.addEventListener('click', () => imageInput.click());

    if (imageInput) imageInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.src = '#';
            imagePreviewContainer.style.display = 'none';
        }
    });

    if (clearImagePreviewBtn) clearImagePreviewBtn.addEventListener('click', function () {
        imageInput.value = '';
        imagePreview.src = '#';
        imagePreviewContainer.style.display = 'none';
    });

    if (sendMessageBtn) sendMessageBtn.addEventListener('click', sendMessage);
    if (messageInput) messageInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Search Filtering
    if (convSearch) {
        convSearch.addEventListener('input', () => {
            const q = convSearch.value.toLowerCase();
            document.querySelectorAll('.conversation-item').forEach(item => {
                const name = item.querySelector('h4').textContent.toLowerCase();
                item.style.display = name.includes(q) ? 'flex' : 'none';
            });
        });
    }

    if (viewMode === 'directory') {
        fetchDirectory();
    } else {
        fetchConversations(true);
    }

    const refreshInterval = setInterval(() => {
        if (!document.getElementById('conversationItems')) {
            clearInterval(refreshInterval);
            return;
        }
        if (activeConversationId) {
            fetchMessages(activeConversationId);
        }
        if (viewMode === 'conversations') fetchConversations();
    }, 5000);
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initMessaging();
} else {
    document.addEventListener('DOMContentLoaded', initMessaging);
}
