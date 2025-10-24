
document.addEventListener('DOMContentLoaded', function () {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const mainContent = document.getElementById('mainContent');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            const targetPage = this.getAttribute('data-target');
            
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
                        mainContent.innerHTML = newContent.innerHTML;
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
});
