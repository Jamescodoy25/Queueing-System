document.addEventListener('DOMContentLoaded', function() {
    // Enhanced back button functionality
    const backButtons = document.querySelectorAll('.back-btn');
    backButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // If coming from another page in the app, go back
            if (document.referrer.includes(window.location.hostname)) {
                e.preventDefault();
                window.history.back();
            }
            // Otherwise follow the link normally
        });
    });

    // Logout button functionality remains the same
    const logoutButtons = document.querySelectorAll('.logout-btn');
    logoutButtons.forEach(button => {
        button.addEventListener('click', function() {
            // In a real app, you would make an AJAX call to logout.php
            window.location.href = 'index.php';
        });
    });
    
    // Call Next functionality for cashier dashboard
    const callNextBtn = document.getElementById('call-next-btn');
    if (callNextBtn) {
        callNextBtn.addEventListener('click', function() {
            const windowNumber = this.getAttribute('data-window');
            // AJAX call to update queue
            fetch('update-queue.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'call_next',
                    window: windowNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }
});

// Function to refresh live queues (called periodically)
function refreshLiveQueues() {
    fetch('get-queues.php')
        .then(response => response.json())
        .then(data => {
            // Update the queue display for each window
            for (let windowNum = 1; windowNum <= 5; windowNum++) {
                const windowData = data.current_queues[`Window ${windowNum}`] || {};
                const nowServingElement = document.getElementById(`now-serving-${windowNum}`);
                const nextNumberElement = document.getElementById(`next-number-${windowNum}`);
                
                if (nowServingElement) nowServingElement.textContent = windowData.now_serving || '--';
                if (nextNumberElement) nextNumberElement.textContent = windowData.next_number || '--';
            }
        });
}

// Refresh queues every 5 seconds if on live queues page
if (document.getElementById('live-queues-container')) {
    refreshLiveQueues();
    setInterval(refreshLiveQueues, 5000);
}

function showAnnouncement(text) {
    const announcement = document.createElement('div');
    announcement.className = 'announcement';
    announcement.textContent = text;
    document.body.appendChild(announcement);
    
    setTimeout(() => {
        announcement.remove();
    }, 3000);
}

// Call it along with the speech
showAnnouncement("Next number is served");
speakAnnouncement("Next number is served");

// Handle recall any number announcement
document.querySelector('.recall-any-form').addEventListener('submit', function(e) {
    const prefix = document.getElementById('number_prefix').value;
    const suffix = document.getElementById('number_suffix').value;
    speakAnnouncement("Attention please, recalling number " + prefix + suffix);
});

// Enhanced reset confirmation
document.querySelector('form[action*="reset_history"]')?.addEventListener('submit', function(e) {
    if (!confirm('WARNING: This will permanently delete ALL queue history. Are you sure?')) {
        e.preventDefault();
    }
});