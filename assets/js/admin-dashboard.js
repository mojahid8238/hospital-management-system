
document.addEventListener('DOMContentLoaded', function () {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const mainContent = document.getElementById('mainContent');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            const targetPage = this.getAttribute('data-target');

            if (!targetPage) return; // Allow normal link navigation if no data-target

            event.preventDefault();
            fetch(targetPage)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.querySelector('.content-area');

                    if (newContent) {
                        // Handle padding if specified
                        if (this.getAttribute('data-no-padding') === 'true') {
                            mainContent.style.padding = '0';
                        } else {
                            mainContent.style.padding = ''; // Reset to CSS default
                        }

                        mainContent.innerHTML = newContent.innerHTML;

                        // Execute scripts found in the new content
                        const scripts = newContent.querySelectorAll('script');
                        scripts.forEach(oldScript => {
                            const newScript = document.createElement('script');
                            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                            if (oldScript.src) {
                                newScript.src = oldScript.src;
                            } else {
                                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                            }
                            document.body.appendChild(newScript);
                            // Remove after execution if it's external to prevent duplicates, 
                            // though browser usually handles this. Inline stays in body.
                        });

                        // Re-initialize sidebar active states - remove from ALL links
                        document.querySelectorAll('.sidebar a').forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                    } else {
                        // If the specific content area isn't found, maybe the whole page is the content
                        const bodyContent = doc.body.innerHTML;
                        if (bodyContent) {
                            mainContent.innerHTML = bodyContent;
                        } else {
                            mainContent.innerHTML = '<p>Error: Could not find content to load.</p>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching page:', error);
                    mainContent.innerHTML = `<p>Error loading content. Please check the console for details. Is the file path correct? (${targetPage})</p>`;
                });
        });
    });

    // Handle AJAX form submissions for admin actions within mainContent
    mainContent.addEventListener('click', function (event) {
        const target = event.target;

        // Handle Approve Admin button click
        if (target.tagName === 'BUTTON' && target.name === 'approve_admin') {
            event.preventDefault();
            const form = target.closest('form');
            const adminId = form.querySelector('input[name="admin_id"]').value;

            fetch('manage-admins.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Custom header to identify AJAX requests
                },
                body: `approve_admin=true&admin_id=${adminId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Remove the row from the table
                        const row = target.closest('tr');
                        if (row) {
                            row.remove();
                        }
                        // If no more pending admins, display a message
                        const tbody = row.closest('tbody');
                        if (tbody && !tbody.children.length) {
                            const tableResponsive = tbody.closest('.table-responsive');
                            if (tableResponsive) {
                                tableResponsive.innerHTML = '<p>No pending admin requests.</p>';
                            }
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error approving admin:', error);
                    alert('An error occurred while approving the admin.');
                });
        }

        // Handle Cancel Admin button click (similar AJAX logic can be added here if needed)
        if (target.tagName === 'BUTTON' && target.name === 'cancel_admin') {
            // For now, keep the default behavior with confirm dialog
            // If you want to convert this to AJAX, you'd add similar fetch logic here
        }
    });

    // Set initial active state based on current page
    const currentPage = window.location.pathname.split('/').pop();
    const allSidebarLinks = document.querySelectorAll('.sidebar a');

    allSidebarLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        const linkTarget = link.getAttribute('data-target');

        // Check if this is the current page
        if (linkHref === currentPage || linkTarget === currentPage) {
            link.classList.add('active');
        } else if (currentPage === 'dashboard.php' && linkHref === 'dashboard.php') {
            link.classList.add('active');
        }
    });
});
