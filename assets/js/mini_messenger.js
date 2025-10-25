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

    // --- UTILITY FUNCTIONS ---

    function showLoadingIndicator() {
        if (!miniMessengerMessages) return;
        // Check if loading indicator already exists to prevent duplication
        if (!document.getElementById('mini-messenger-loading')) {
            // Clear existing messages before showing loader for a clean transition
            miniMessengerMessages.innerHTML = '';
            
            miniMessengerMessages.innerHTML = `
                <div id="mini-messenger-loading" style="text-align: center; padding: 20px; color: #999;">
                    <svg style="animation: spin 1s linear infinite; display: inline-block; margin-right: 8px;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                    Loading messages...
                </div>
            `;
            // Add a basic CSS animation for spinning (assumes a general style context)
            const style = document.createElement('style');
            style.type = 'text/css';
            style.innerHTML = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.getElementsByTagName('head')[0].appendChild(style);
        }
    }

    function hideLoadingIndicator() {
        const loader = document.getElementById('mini-messenger-loading');
        if (loader) {
            loader.remove();
        }
    }

    // --- CORE MESSENGER LOGIC ---

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
                if (miniMessengerCloseBtn) miniMessengerCloseBtn.style.display = 'none'; // Explicitly hide close button
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
                if (miniMessengerCloseBtn) miniMessengerCloseBtn.style.display = 'flex'; // Explicitly show close button
                if (miniMessengerMessages) miniMessengerMessages.style.display = 'flex'; // Show messages
                if (miniMessengerMessageInput) miniMessengerMessageInput.style.display = 'flex';
                if (miniMessengerSendMessageBtn) miniMessengerSendMessageBtn.style.display = 'flex';
                if (miniMessengerUploadImageBtn) miniMessengerUploadImageBtn.style.display = 'flex';
                // Image input and preview container visibility depends on whether an image is selected
                if (miniMessengerImageInput && miniMessengerImageInput.files[0]) {
                    if (miniMessengerImagePreviewContainer) miniMessengerImagePreviewContainer.style.display = 'flex';
                }
                // When maximizing, re-fetch messages to ensure latest state is shown
                if (currentMiniChatConversationId) {
                     // Show loader explicitly here as the minimize/maximize action is manual
                     showLoadingIndicator();
                     fetchMiniChatMessages(currentMiniChatConversationId, true);
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
                 // When maximizing via click, re-fetch messages
                if (currentMiniChatConversationId) {
                     // Show loader explicitly here as the click action is manual
                     showLoadingIndicator();
                     fetchMiniChatMessages(currentMiniChatConversationId, true);
                }
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

    // Function to open the mini-messenger (Initial Load/Open)
    window.openMiniMessenger = function(receiverId, receiverName, receiverProfilePic, conversationId = null) {
        console.log('openMiniMessenger called with:', { receiverId, receiverName, receiverProfilePic, conversationId });
        currentMiniChatReceiverId = receiverId;
        currentMiniChatReceiverName = receiverName;
        currentMiniChatReceiverProfilePic = receiverProfilePic;
        currentMiniChatConversationId = conversationId;
        console.log('Assigned currentMiniChatConversationId:', currentMiniChatConversationId); // Added log

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

        // --- CORE LOGIC: Fetch messages for the conversation on OPEN ---
        // Always attempt to fetch, passing receiverId if conversationId is not present
        showLoadingIndicator();
        fetchMiniChatMessages(currentMiniChatConversationId, true, currentMiniChatReceiverId);
        if (miniMessengerMessageInput) {
            miniMessengerMessageInput.focus();
        }
    };

    function fetchMiniChatMessages(conversationId, manualAction = false, receiverId = null) {
        // If the chat is closed, abort.
        // We allow fetching even if conversationId is null/empty, assuming backend handles it.
        if (miniMessenger.style.display !== 'flex') {
            if (manualAction) {
                hideLoadingIndicator(); // Clean up if manual action aborted
            }
            return;
        }

        // --- NOTE: showLoadingIndicator() is handled manually in openMiniMessenger, sendMiniChatMessage, and click/minimize events. ---
        // This function assumes the loader is visible if manualAction is true.
        // If it's polling (manualAction === false), we skip the loader steps entirely.

        // This is the call to get_messages.php
        let url = `../messaging/get_messages.php?`;
        if (conversationId) {
            url += `conversation_id=${conversationId}`;
        } else if (receiverId) {
            url += `receiver_id=${receiverId}`;
        }
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (manualAction) {
                    hideLoadingIndicator(); // Hide loader after manual action completes
                }
                if (data.success) {
                    renderMiniChatMessages(data.messages);
                    // Update conversation ID if returned by the backend
                    if (data.conversation_id && data.conversation_id !== currentMiniChatConversationId) {
                        currentMiniChatConversationId = data.conversation_id;
                        console.log('Updated currentMiniChatConversationId:', currentMiniChatConversationId);
                    }
                } else {
                    if (miniMessengerMessages && manualAction) {
                         miniMessengerMessages.innerHTML = '<div style="text-align: center; padding: 10px; color: red;">Failed to load messages.</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Fetch messages error:', error);
                 if (manualAction) {
                    hideLoadingIndicator(); // Hide loader on network error
                    if (miniMessengerMessages) {
                        miniMessengerMessages.innerHTML = '<div style="text-align: center; padding: 10px; color: red;">Network error. Could not load messages.</div>';
                    }
                }
            });
    }

    function renderMiniChatMessages(messages) {
        if (!miniMessengerMessages) return;
        
        // Check if the loading indicator is visible. If so, clear it.
        hideLoadingIndicator(); 

        // If the current content is just the "start conversation" message, clear it.
        const startMessage = miniMessengerMessages.querySelector('div[style*="Start a new conversation"]');
        if (startMessage) {
            miniMessengerMessages.innerHTML = '';
        }

        // Optimization: Only clear and redraw if there are new messages or content changed
        // For simplicity in this fix, we will keep the full redraw, but ensure loader is hidden.
        
        miniMessengerMessages.innerHTML = '';
        messages.forEach(msg => {
            const messageWrapper = document.createElement('div'); // New wrapper for message and timestamp
            messageWrapper.classList.add('message-wrapper'); // Add a class for styling

            const messageElement = document.createElement('div');
            messageElement.classList.add('mini-messenger-message');
            // Assuming currentUserId is globally available from the dashboard/session data
            if (msg.sender_id === currentUserId) {
                messageWrapper.classList.add('sent'); // Apply alignment to wrapper
                messageElement.classList.add('sent');
            } else {
                messageWrapper.classList.add('received'); // Apply alignment to wrapper
                messageElement.classList.add('received');
            }

            let messageContent = '';
            if (msg.message_type === 'image') {
                messageContent = `<img src="/hospital-management-system/${msg.message_content}" class="message-image">`;
            } else {
                messageContent = `<p>${msg.message_content}</p>`;
            }

            messageElement.innerHTML = messageContent; // Message content only in the bubble

            const timestampElement = document.createElement('span');
            timestampElement.classList.add('timestamp');
            timestampElement.textContent = new Date(msg.timestamp).toLocaleTimeString();

            messageWrapper.appendChild(messageElement);
            messageWrapper.appendChild(timestampElement);
            miniMessengerMessages.appendChild(messageWrapper);
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

        // --- Send process starts here ---

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
                
                // --- FIX: Ensure conversation ID is updated if provided ---
                if (data.conversation_id) {
                    currentMiniChatConversationId = data.conversation_id;
                }
                
                // --- CRITICAL: After successful send, trigger fetch with loader ---
                if (currentMiniChatConversationId) {
                    // Show loader to indicate messages are fetching (as it was removed at the start)
                    showLoadingIndicator(); 
                    fetchMiniChatMessages(currentMiniChatConversationId, true);
                } else {
                    // This is only if send was successful but returned no ID (a server-side error)
                    hideLoadingIndicator(); 
                    console.error('Message sent successfully but no conversation ID available for fetching messages.');
                }
                
                // Optionally, refresh the main conversations list if it's open
            } else {
                // If send failed, make sure the input is clear of any pending loading state/message
                hideLoadingIndicator(); 
                console.error('Failed to send message:', data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error sending mini chat message:', error);
            hideLoadingIndicator(); // Hide loader on network error
        });
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
            // Pass false for polling so it doesn't show/hide the loading indicator
            fetchMiniChatMessages(currentMiniChatConversationId, false);
        }
    }, 3000); // Poll every 3 seconds
