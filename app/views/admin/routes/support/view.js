
// Clean All Cache button handler
document.getElementById('cleanAllCache').addEventListener('click', function() {
    if (confirm("Are you sure you want to clean all cache? This will remove all cache and session files.")) {
        axios.post('/{{@SITE.uri_backend}}/support?clean-all-cache', { token: '{{@TOKEN}}' })
            .then(response => {
                if (response.data.status === 'success') {
                    alert('All cache cleaned successfully.');
                    window.location.reload();
                } else {
                    alert('Failed to clean all cache: ' + response.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cleaning all cache.');
            });
    }
});

// Clean Temporary Files button handler
document.getElementById('cleanTempFiles').addEventListener('click', function() {
    if (confirm("Are you sure you want to clean temporary files?")) {
        axios.post('/{{@SITE.uri_backend}}/support?clean-temp-files', { token: '{{@TOKEN}}' })
            .then(response => {
                if (response.data.status === 'success') {
                    alert('Temporary files cleaned successfully.');
                    window.location.reload();
                } else {
                    alert('Failed to clean temporary files: ' + response.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cleaning temporary files.');
            });
    }
});

// Clean User Sessions button handler
document.getElementById('cleanUserSessions').addEventListener('click', function() {
    if (confirm("Are you sure you want to clean user session files?")) {
        axios.post('/{{@SITE.uri_backend}}/support?clean-user-sessions', { token: '{{@TOKEN}}' })
            .then(response => {
                if (response.data.status === 'success') {
                    alert('User sessions cleaned successfully.');
                    window.location.reload();
                } else {
                    alert('Failed to clean user sessions: ' + response.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cleaning user sessions.');
            });
    }
});

// Download backup button handler
document.getElementById('downloadBackup').addEventListener('click', function() {
    window.location.href = '/{{@SITE.uri_backend}}/support?download-backup&tkn={{@TOKEN}}';
});

// Create new backup button handler
document.getElementById('createBackup').addEventListener('click', function() {
    const btn = this;
    btn.innerHTML = 'Creating Backup...'; // Change text or add a spinner here
    btn.disabled = true;

    axios.post('/{{@SITE.uri_backend}}/support?create-backup', { token: '{{@TOKEN}}' })
        .then(response => {
            if (response.data.status === 'success') {
                alert('New backup created successfully.');
                window.location.reload();
            } else {
                alert('Failed to create new backup: ' + response.data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating a new backup.');
        })
        .finally(() => {
            btn.innerHTML = 'Create New Backup'; // Revert button text
            btn.disabled = false;
        });
});

document.getElementById('clearLogsButton').addEventListener('click', function() {
    if (confirm("Are you sure you want to clear the logs? This action cannot be undone.")) {
        axios.post('/{{@SITE.uri_backend}}/support?clear-logs', {
            action: 'clearLogs',
            token: '{{@TOKEN}}' // Include CSRF token for security
        })
            .then(function (response) {
                if (response.data.status === 'success') {
                    alert("Logs cleared successfully.");
                    window.location.reload(); // Reload the page to reflect changes
                } else {
                    alert("Failed to clear logs: " + response.data.message);
                }
            })
            .catch(function (error) {
                alert("An error occurred while clearing logs. Please try again.");
                console.error('Error:', error);
            });
    }
});