document.addEventListener('DOMContentLoaded', function () {
    const miniMessenger = document.getElementById('miniMessenger');
    if (!miniMessenger) return;

    const minimizedProfilePic = document.getElementById('minimizedProfilePic');
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

    // --- UTILITY FUNCTIONS ---

    function showLoadingIndicator() {
        if (!miniMessengerMessages) return;
        if (!document.getElementById('mini-messenger-loading')) {
            miniMessengerMessages.innerHTML = '';
            miniMessengerMessages.innerHTML = `
                <div id="mini-messenger-loading" style="text-align: center; padding: 20px; color: #999;">
                    <svg style="animation: spin 1s linear infinite; display: inline-block; margin-right: 8px;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                    Loading messages...
                </div>
            `;
            const style = document.createElement('style');
            style.type = 'text/css';
            style.innerHTML = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.getElementsByTagName('head')[0].appendChild(style);
        }
    }

    function hideLoadingIndicator() {
        const loader = document.getElementById('mini-messenger-loading');
        if (loader) loader.remove();
    }

    // Make the mini-messenger draggable
    let isDragging = false;
    let offsetX, offsetY;

    if (miniMessengerHeader) {
        miniMessengerHeader.addEventListener('mousedown', (e) => {
            if (miniMessenger.classList.contains('minimized')) return;
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
        if (miniMessengerHeader) miniMessengerHeader.style.cursor = 'grab';
    });

    if (miniMessengerMinimizeBtn) {
        miniMessengerMinimizeBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            miniMessenger.classList.toggle('minimized');
            if (miniMessenger.classList.contains('minimized')) {
                miniMessengerMinimizeBtn.textContent = '□';
                if (minimizedProfilePic) {
                    minimizedProfilePic.src = `../${currentMiniChatReceiverProfilePic || 'assets/images/default-avatar.png'}`;
                    minimizedProfilePic.style.display = 'block';
                }
                if (miniMessengerHeader) miniMessengerHeader.style.display = 'none';
                if (miniMessengerCloseBtn) miniMessengerCloseBtn.style.display = 'none';
                if (miniMessengerMessages) miniMessengerMessages.style.display = 'none';
                if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'none';
                if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'none';
                if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'none';
                if (miniMessengerImageInput) miniMessengerImageInput.style.display = 'none';
                if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'none';
            } else {
                miniMessengerMinimizeBtn.textContent = '—';
                if (minimizedProfilePic) minimizedProfilePic.style.display = 'none';
                if (miniMessengerHeader) miniMessengerHeader.style.display = 'flex';
                if (miniMessengerCloseBtn) miniMessengerCloseBtn.style.display = 'flex';
                if (miniMessengerMessages) miniMessengerMessages.style.display = 'flex';
                if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'flex';
                if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'flex';
                if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'flex';
                if (currentMiniChatConversationId) {
                    showLoadingIndicator();
                    fetchMiniChatMessages(currentMiniChatConversationId, true);
                }
            }
        });
    }

    if (miniMessenger) {
        miniMessenger.addEventListener('click', () => {
            if (miniMessenger.classList.contains('minimized')) {
                miniMessenger.classList.remove('minimized');
                if (minimizedProfilePic) minimizedProfilePic.style.display = 'none';
                if (miniMessengerHeader) miniMessengerHeader.style.display = 'flex';
                if (miniMessengerCloseBtn) miniMessengerCloseBtn.style.display = 'flex';
                if (miniMessengerMessages) miniMessengerMessages.style.display = 'flex';
                if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'flex';
                if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'flex';
                if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'flex';
                if (miniMessengerMinimizeBtn) miniMessengerMinimizeBtn.textContent = '—';
                if (currentMiniChatConversationId) {
                    showLoadingIndicator();
                    fetchMiniChatMessages(currentMiniChatConversationId, true);
                }
            }
        });
    }

    if (miniMessengerCloseBtn) {
        miniMessengerCloseBtn.addEventListener('click', (event) => {
            event.stopPropagation();
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
            if (minimizedProfilePic) minimizedProfilePic.style.display = 'none';
        });
    }

    window.openMiniMessenger = function (receiverId, receiverName, receiverProfilePic, conversationId = null) {
        currentMiniChatReceiverId = receiverId;
        currentMiniChatReceiverName = receiverName;
        currentMiniChatReceiverProfilePic = receiverProfilePic;
        currentMiniChatConversationId = conversationId;

        if (miniMessengerContactName) miniMessengerContactName.textContent = receiverName;
        if (miniMessenger) {
            miniMessenger.style.display = 'flex';
            miniMessenger.classList.remove('minimized');
            if (minimizedProfilePic) minimizedProfilePic.style.display = 'none';
            if (miniMessengerHeader) miniMessengerHeader.style.display = 'flex';
            if (miniMessengerMessages) miniMessengerMessages.style.display = 'flex';
            if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'flex';
            if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'flex';
            if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'flex';
        }
        if (miniMessengerMinimizeBtn) miniMessengerMinimizeBtn.textContent = '—';

        if (minimizedProfilePic) {
            minimizedProfilePic.src = `../${currentMiniChatReceiverProfilePic || 'assets/images/default-avatar.png'}`;
        }

        showLoadingIndicator();
        fetchMiniChatMessages(currentMiniChatConversationId, true, currentMiniChatReceiverId);
        if (miniMessengerMessageInput) miniMessengerMessageInput.focus();
    };

    function fetchMiniChatMessages(conversationId, manualAction = false, receiverId = null) {
        if (miniMessenger.style.display !== 'flex') return;

        let url = `../messaging/get_messages.php?`;
        if (conversationId) {
            url += `conversation_id=${conversationId}`;
        } else if (receiverId) {
            url += `receiver_id=${receiverId}`;
        }
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (manualAction) hideLoadingIndicator();
                if (data.success) {
                    renderMiniChatMessages(data.messages);
                    if (data.conversation_id && data.conversation_id !== currentMiniChatConversationId) {
                        currentMiniChatConversationId = data.conversation_id;
                    }
                } else if (miniMessengerMessages && manualAction) {
                    miniMessengerMessages.innerHTML = '<div style="text-align: center; padding: 10px; color: red;">Failed to load messages.</div>';
                }
            })
            .catch(error => {
                console.error('Fetch messages error:', error);
                if (manualAction) {
                    hideLoadingIndicator();
                    if (miniMessengerMessages) {
                        miniMessengerMessages.innerHTML = '<div style="text-align: center; padding: 10px; color: red;">Network error.</div>';
                    }
                }
            });
    }

    function renderMiniChatMessages(messages) {
        if (!miniMessengerMessages) return;
        hideLoadingIndicator();
        miniMessengerMessages.innerHTML = '';

        const myId = (typeof currentUserId !== 'undefined') ? currentUserId : null;

        messages.forEach(msg => {
            const messageWrapper = document.createElement('div');
            messageWrapper.classList.add('message-wrapper');

            const messageElement = document.createElement('div');
            messageElement.classList.add('mini-messenger-message');

            if (myId && msg.sender_id === myId) {
                messageWrapper.classList.add('sent');
                messageElement.classList.add('sent');
            } else {
                messageWrapper.classList.add('received');
                messageElement.classList.add('received');
            }

            let messageContent = '';
            if (msg.message_type === 'image') {
                messageContent = `<img src="../${msg.message_content.replace(/^\//, '')}" class="message-image">`;
            } else {
                messageContent = `<p>${msg.message_content}</p>`;
            }

            messageElement.innerHTML = messageContent;

            const timestampElement = document.createElement('span');
            timestampElement.classList.add('timestamp');
            timestampElement.textContent = new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            messageWrapper.appendChild(messageElement);
            messageWrapper.appendChild(timestampElement);
            miniMessengerMessages.appendChild(messageWrapper);
        });
        miniMessengerMessages.scrollTop = miniMessengerMessages.scrollHeight;
    }

    function sendMiniChatMessage() {
        const messageContent = miniMessengerMessageInput.value.trim();
        const imageFile = miniMessengerImageInput.files[0];

        if (messageContent === '' && !imageFile) return;
        if (!currentMiniChatReceiverId) return;

        const formData = new FormData();
        formData.append('receiver_id', currentMiniChatReceiverId);
        formData.append('message_content', messageContent);
        if (imageFile) formData.append('image', imageFile);

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

                    if (data.conversation_id) currentMiniChatConversationId = data.conversation_id;

                    if (currentMiniChatConversationId) {
                        showLoadingIndicator();
                        fetchMiniChatMessages(currentMiniChatConversationId, true);
                    }
                } else {
                    hideLoadingIndicator();
                    console.error('Failed to send message:', data.message);
                }
            })
            .catch(error => {
                console.error('Error sending mini chat message:', error);
                hideLoadingIndicator();
            });
    }

    if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.addEventListener('click', sendMiniChatMessage);
    if (miniMessengerMessageInput) {
        miniMessengerMessageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') sendMiniChatMessage();
        });
    }

    if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.addEventListener('click', () => miniMessengerImageInput.click());

    if (miniMessengerImageInput) {
        miniMessengerImageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (miniMessengerImagePreview) miniMessengerImagePreview.src = e.target.result;
                    if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'flex';
                };
                reader.readAsDataURL(file);
            } else {
                if (miniMessengerImagePreview) miniMessengerImagePreview.src = '#';
                if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'none';
            }
        });
    }

    if (miniMessengerClearImagePreview) {
        miniMessengerClearImagePreview.addEventListener('click', function () {
            if (miniMessengerImageInput) miniMessengerImageInput.value = '';
            if (miniMessengerImagePreview) miniMessengerImagePreview.src = '#';
            if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'none';
        });
    }

    setInterval(() => {
        if (miniMessenger && miniMessenger.style.display === 'flex' && currentMiniChatConversationId && !miniMessenger.classList.contains('minimized')) {
            fetchMiniChatMessages(currentMiniChatConversationId, false);
        }
    }, 3000);
});
