
document.addEventListener('DOMContentLoaded', function () {
    // Profile overlay functionality
    const profileToggle = document.getElementById('profileToggle');
    const profileOverlay = document.getElementById('profileOverlay');
    const mainContent = document.getElementById('mainContent'); // Assuming mainContent is a common ID

    if (profileToggle && profileOverlay) {
        profileToggle.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent this click from immediately closing the overlay
            profileOverlay.classList.add('open');
        });

        // Close overlay when clicking directly on the overlay background
        profileOverlay.addEventListener('click', function(event) {
            if (event.target === profileOverlay) {
                profileOverlay.classList.remove('open');
            }
        });
    }

    // Close overlay when clicking on the main content area (if it exists)
    if (mainContent) {
        mainContent.addEventListener('click', () => {
            if (profileOverlay && profileOverlay.classList.contains('open')) {
                profileOverlay.classList.remove('open');
            }
        });
    }

    // Profile picture upload functionality (if these elements exist)
    const profilePicInput = document.getElementById('profilePicInput');
    const profilePicUploadForm = document.getElementById('profilePicUploadForm');
    const profileImageDisplay = document.getElementById('profileImageDisplay');
    const uploadMessage = document.getElementById('uploadMessage');
    const base_url = (typeof BASE_URL !== 'undefined') ? BASE_URL : '/hospital-management-system/';


    if (profileImageDisplay && profilePicInput) {
        profileImageDisplay.addEventListener('click', function() {
            profilePicInput.click();
        });
    }

    if (profilePicInput && profilePicUploadForm) {
        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData(profilePicUploadForm);
                fetch(profilePicUploadForm.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const newImagePath = base_url + data.profile_pic_path + '?t=' + new Date().getTime();
                        
                        if(profileImageDisplay) {
                            profileImageDisplay.src = newImagePath;
                        }
                        // Also update the small icon in the header
                        if(profileToggle) {
                            profileToggle.src = newImagePath;
                        }

                        if(uploadMessage) {
                            uploadMessage.textContent = 'Profile picture updated successfully!';
                            uploadMessage.style.color = 'green';
                            setTimeout(() => {
                                uploadMessage.textContent = '';
                            }, 3000);
                        }
                    } else {
                        if(uploadMessage) {
                            uploadMessage.textContent = data.message || 'Error uploading profile picture.';
                            uploadMessage.style.color = 'red';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if(uploadMessage) {
                        uploadMessage.textContent = 'An error occurred during upload.';
                        uploadMessage.style.color = 'red';
                    }
                });
            }
        });
    }
});
