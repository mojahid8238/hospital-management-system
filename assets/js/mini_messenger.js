    const miniMessenger = document.getElementById('miniMessenger');
    const minimizedProfilePic = document.getElementById('minimizedProfilePic'); // New element
    const miniMessengerHeader = document.getElementById('miniMessengerHeader');
    const miniMessengerContactName = document.getElementById('miniMessengerContactName');
    const miniMessengerMinimizeBtn = document.getElementById('miniMessengerMinimizeBtn');
    const miniMessengerCloseBtn = document.getElementById('miniMessengerCloseBtn');
    const miniMessengerMessages = document.getElementById('miniMessengerMessages');
    const miniMessengerMessageInput = document.getElementById('miniMessengerMessageInput');
    const miniMessengerSendMessageBtn = document.getElementById('miniMessengerSendMessageBtn');
    const miniMessengerUploadImageBtn = document.getElementById('miniMessengerUploadImageBtn');
    const miniMessengerImageInput = document.getElementById('miniMessengerImageInput');
    const miniMessengerImagePreviewContainer = document.getElementById('miniMessengerImagePreviewContainer');
    const miniMessengerImagePreview = document.getElementById('miniMessengerImagePreview');
    const miniMessengerClearImagePreview = document.getElementById('miniMessengerClearImagePreview');

    let currentMiniChatReceiverId = null;
    let currentMiniChatConversationId = null;
    let currentMiniChatReceiverName = '';
    let currentMiniChatReceiverProfilePic = '';

    // Make the mini-messenger draggable
    let isDragging = false;
    let offsetX, offsetY;

    if (miniMessengerHeader) {
        miniMessengerHeader.addEventListener('mousedown', (e) => {
            if (miniMessenger.classList.contains('minimized')) return; // Don't drag if minimized
            isDragging = true;
            offsetX = e.clientX - miniMessenger.getBoundingClientRect().left;
            offsetY = e.clientY - miniMessenger.getBoundingClientRect().top;
            miniMessengerHeader.style.cursor = 'grabbing';
        });
    }

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        miniMessenger.style.left = (e.clientX - offsetX) + 'px';
        miniMessenger.style.top = (e.clientY - offsetY) + 'px';
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        if (miniMessengerHeader) {
            miniMessengerHeader.style.cursor = 'grab';
        }
    });

    // Minimize/Maximize functionality
    if (miniMessengerMinimizeBtn) {
        miniMessengerMinimizeBtn.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent click from propagating to miniMessenger and opening it
            miniMessenger.classList.toggle('minimized');
            if (miniMessenger.classList.contains('minimized')) {
                console.log('Mini-messenger minimized.');
                miniMessengerMinimizeBtn.textContent = '□'; // Change to maximize icon
                if (minimizedProfilePic) {
                    minimizedProfilePic.src = `/hospital-management-system/${currentMiniChatReceiverProfilePic || 'assets/images/default-avatar.png'}`;
                    minimizedProfilePic.style.display = 'block';
                }
                if (miniMessengerHeader) miniMessengerHeader.style.display = 'none';
                if (miniMessengerMessages) miniMessengerMessages.style.display = 'none';
                if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'none';
                if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'none';
                if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'none';
                if (miniMessengerImageInput) miniMessengerImageInput.style.display = 'none';
                if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'none';
            } else {
                console.log('Mini-messenger maximized.');
                miniMessengerMinimizeBtn.textContent = '—'; // Change to minimize icon
                if (minimizedProfilePic) minimizedProfilePic.style.display = 'none';
                if (miniMessengerHeader) miniMessengerHeader.style.display = 'flex'; // Show header
                if (miniMessengerMessages) miniMessengerMessages.style.display = 'flex'; // Show messages
                if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'flex';
                if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'flex';
                if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'flex';
                // Image input and preview container visibility depends on whether an image is selected
                if (miniMessengerImageInput && miniMessengerImageInput.files[0]) {
                    if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'flex';
                }
            }
        });
    }

    // Click listener for the minimized messenger to open it
    if (miniMessenger) {
        miniMessenger.addEventListener('click', () => {
            if (miniMessenger.classList.contains('minimized')) {
                miniMessenger.classList.remove('minimized');
                if (minimizedProfilePic) minimizedProfilePic.style.display = 'none';
                if (miniMessengerHeader) miniMessengerHeader.style.display = 'flex';
                if (miniMessengerMessages) miniMessengerMessages.style.display = 'flex';
                if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'flex';
                if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'flex';
                if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'flex';
                if (miniMessengerImageInput && miniMessengerImageInput.files[0]) {
                    if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'flex';
                }
                if (miniMessengerMinimizeBtn) miniMessengerMinimizeBtn.textContent = '—';
            }
        });
    }

    // Close functionality
    if (miniMessengerCloseBtn) {
        miniMessengerCloseBtn.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent click from propagating to miniMessenger and opening it
            miniMessenger.style.display = 'none';
            currentMiniChatReceiverId = null;
            currentMiniChatConversationId = null;
            currentMiniChatReceiverName = '';
            currentMiniChatReceiverProfilePic = '';
            miniMessengerMessages.innerHTML = '';
            miniMessengerMessageInput.value = '';
            miniMessengerImageInput.value = '';
            miniMessengerImagePreviewContainer.style.display = 'none';
            miniMessengerImagePreview.src = '#';
            if (minimizedProfilePic) minimizedProfilePic.style.display = 'none'; // Hide minimized pic on close
        });
    }

    // Function to open the mini-messenger
    window.openMiniMessenger = function(receiverId, receiverName, receiverProfilePic, conversationId = null) {
        console.log('openMiniMessenger called with:', { receiverId, receiverName, receiverProfilePic, conversationId });
        currentMiniChatReceiverId = receiverId;
        currentMiniChatReceiverName = receiverName;
        currentMiniChatReceiverProfilePic = receiverProfilePic;
        currentMiniChatConversationId = conversationId;

        if (miniMessengerContactName) {
            miniMessengerContactName.textContent = receiverName;
        }
        if (miniMessenger) {
            miniMessenger.style.display = 'flex';
            miniMessenger.classList.remove('minimized'); // Ensure it's not minimized when opened
            // Ensure full messenger elements are visible when opened
            if (minimizedProfilePic) minimizedProfilePic.style.display = 'none';
            if (miniMessengerHeader) miniMessengerHeader.style.display = 'flex';
            if (miniMessengerMessages) miniMessengerMessages.style.display = 'flex';
            if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'flex';
            if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'flex';
            if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'flex';
            if (miniMessengerImageInput && miniMessengerImageInput.files[0]) {
                if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'flex';
            }
        }
        if (miniMessengerMinimizeBtn) {
            miniMessengerMinimizeBtn.textContent = '—';
        }

        // Set the profile pic for the minimized state
        if (minimizedProfilePic) {
            minimizedProfilePic.src = `/hospital-management-system/${currentMiniChatReceiverProfilePic || 'assets/images/default-avatar.png'}`;
        }

        // Fetch messages for the conversation
        if (currentMiniChatConversationId) {
            fetchMiniChatMessages(currentMiniChatConversationId);
        } else {
            if (miniMessengerMessages) {
                miniMessengerMessages.innerHTML = '<div style="text-align: center; padding: 10px; color: #666;">Start a new conversation.</div>';
            }
        }
        if (miniMessengerMessageInput) {
            miniMessengerMessageInput.focus();
        }
    };

    function fetchMiniChatMessages(conversationId) {
        if (!conversationId) return;
        fetch(`../messaging/get_messages.php?conversation_id=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMiniChatMessages(data.messages);
                }
            });
    }

    function renderMiniChatMessages(messages) {
        if (!miniMessengerMessages) return;
        miniMessengerMessages.innerHTML = '';
        messages.forEach(msg => {
            const messageElement = document.createElement('div');
            messageElement.classList.add('mini-messenger-message');
            if (msg.sender_id === currentUserId) { // currentUserId should be globally available or passed
                messageElement.classList.add('sent');
            } else {
                messageElement.classList.add('received');
            }

            let messageContent = '';
            if (msg.message_type === 'image') {
                messageContent = `<img src="/hospital-management-system/${msg.message_content}" class="message-image">`;
            } else {
                messageContent = `<p>${msg.message_content}</p>`;
            }

            messageElement.innerHTML = `
                ${messageContent}
                <span class="timestamp">${new Date(msg.timestamp).toLocaleTimeString()}</span>
            `;
            miniMessengerMessages.appendChild(messageElement);
        });
        miniMessengerMessages.scrollTop = miniMessengerMessages.scrollHeight;
    }

    function sendMiniChatMessage() {
        const messageContent = miniMessengerMessageInput.value.trim();
        const imageFile = miniMessengerImageInput.files[0];

        if (messageContent === '' && !imageFile) {
            return;
        }
        if (!currentMiniChatReceiverId) {
            console.error('No receiver selected for mini chat.');
            return;
        }

        const formData = new FormData();
        formData.append('receiver_id', currentMiniChatReceiverId);
        formData.append('message_content', messageContent);
        if (imageFile) {
            formData.append('image', imageFile);
        }
        // No appointment_id for mini messenger for now, can be added if needed

        fetch('../messaging/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (miniMessengerMessageInput) miniMessengerMessageInput.value = '';
                if (miniMessengerImageInput) miniMessengerImageInput.value = '';
                if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'none';
                if (miniMessengerImagePreview) miniMessengerImagePreview.src = '#';
                if (data.conversation_id) {
                    currentMiniChatConversationId = data.conversation_id;
                }
                fetchMiniChatMessages(currentMiniChatConversationId);
                // Optionally, refresh the main conversations list if it's open
            }
        })
        .catch(error => console.error('Error sending mini chat message:', error));
    }

    if (miniMessengerSendMessageBtn) {
        miniMessengerSendMessageBtn.addEventListener('click', sendMiniChatMessage);
    }
    if (miniMessengerMessageInput) {
        miniMessengerMessageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMiniChatMessage();
            }
        });
    }

    // Image upload and preview for mini messenger
    if (miniMessengerUploadImageBtn) {
        miniMessengerUploadImageBtn.addEventListener('click', () => miniMessengerImageInput.click());
    }

    if (miniMessengerImageInput) {
        miniMessengerImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (miniMessengerImagePreview) miniMessengerImagePreview.src = e.target.result;
                    if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'flex'; // Use flex for centering
                };
                reader.readAsDataURL(file);
            } else {
                if (miniMessengerImagePreview) miniMessengerImagePreview.src = '#';
                if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'none';
            }
        });
    }

    if (miniMessengerClearImagePreview) {
        miniMessengerClearImagePreview.addEventListener('click', function() {
            if (miniMessengerImageInput) miniMessengerImageInput.value = '';
            if (miniMessengerImagePreview) miniMessengerImagePreview.src = '#';
            if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'none';
        });
    }

    // Polling for new messages in the active mini chat
    setInterval(() => {
        if (miniMessenger && miniMessenger.style.display === 'flex' && currentMiniChatConversationId && (!miniMessenger.classList || !miniMessenger.classList.contains('minimized'))) {
            fetchMiniChatMessages(currentMiniChatConversationId);
        }
    }, 3000); // Poll every 3 seconds
