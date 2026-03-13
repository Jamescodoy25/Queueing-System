<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Load data
$queues = json_decode(file_get_contents('data/queues.json'), true);
$settings = json_decode(file_get_contents('data/settings.json'), true);
$cashiers = json_decode(file_get_contents('data/cashiers.json'), true);

// Get all windows (1-5)
$allWindows = ['Window 1', 'Window 2', 'Window 3', 'Window 4', 'Window 5'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title>Fiesta Appliance - Live Queues</title> -->
    <link rel="stylesheet" href="stylesaqueues.css">
</head>
<body>

<p></p>
<div class="images">
    <img src="images/logo1.png" alt="nawala ang code" class="logopic">
    <div class="other-pics">
        <img src="images/fiesta.png" alt="nawala ang code" class="fiestapic">
        <img src="images/appliance.png" alt="nawala ang code" class="appliancepic">
    </div>
</div>
    
    <div class="container-live" id="live-queues-container">
        
        
    <div class="queue-display">
    <?php foreach ($allWindows as $window): ?>
        <?php 
        $windowData = $queues['current_queues'][$window] ?? ['now_serving' => null, 'next_number' => null];
        $cashier = array_filter($cashiers, function($c) use ($window) {
            return $c['window'] === $window;
        });
        $cashierName = !empty($cashier) ? reset($cashier)['username'] : 'Not assigned';
        ?>
        <div class="window">
            <h3><?php echo htmlspecialchars($window); ?></h3>
            <div class="number-display">
                <div class="number-row">
                    <span class="number-label">NOW SERVING: </span>
                    <span class="now-serving" id="now-serving-<?php echo substr($window, -1); ?>">
                        <?php echo $windowData['now_serving'] ?? '--'; ?>
                    </span>
                </div>
                <div class="number-row">
                    <span class="number-label">NEXT NUMBER: </span>
                    <span class="next-number" id="next-number-<?php echo substr($window, -1); ?>">
                        <?php echo $windowData['next_number'] ?? '--'; ?>
                    </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<!-- Running Text/Marquee -->
<div class="running-text-container">
    <div class="running-text" id="runningText"></div>
</div>

<!-- Add this near the bottom of your body, before the scripts -->
<audio id="notificationSound" preload="auto">
    <source src="sounds/notification.mp3" type="audio/mpeg">
    <source src="sounds/notification.ogg" type="audio/ogg">
</audio>

<script>
    // Function to fetch running text from JSON
    function fetchRunningText() {
        fetch('data/settings.json')
            .then(response => response.json())
            .then(data => {
                // Update the div with the running text from JSON
                const runningTextDiv = document.getElementById("runningText");
                runningTextDiv.textContent = data.running_text;
            })
            .catch(error => console.error('Error fetching running text:', error));
    }

    document.addEventListener("DOMContentLoaded", function () {
        // Fetch the settings JSON data initially
        fetchRunningText();

        // Set interval to fetch running text every 5 seconds
        setInterval(fetchRunningText, 5000); // Adjusted to 5000 milliseconds (5 seconds)
    });

    // Audio element
const notificationSound = document.getElementById('notificationSound');


// Store current queue numbers
let currentQueues = {};

// Function to play notification sound
function playNotificationSound() {
    notificationSound.currentTime = 0;
    notificationSound.play().catch(e => console.log("Audio play failed:", e));
}

// Show notification at bottom of window
function showNumberNotification(windowNum, number) {
    const windowElement = document.querySelector(`.window:nth-child(${windowNum})`);
    if (windowElement) {
        let container = windowElement.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            windowElement.appendChild(container);
        }
        
        container.innerHTML = '';
        const notification = document.createElement('div');
        notification.className = 'window-notification';
        notification.textContent = `Now serving ${number}`;
        container.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'fadeInOut 3s ease-in-out';
        }, 10);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Function to check for queue updates
function checkQueueUpdates() {
    fetch('get-queues.php')
        .then(response => response.json())
        .then(data => {
            for (let windowNum = 1; windowNum <= 5; windowNum++) {
                const windowKey = `Window ${windowNum}`;
                const windowData = data.current_queues[windowKey] || {};
                const currentNumber = windowData.now_serving;
                
                if (currentNumber && currentNumber !== (currentQueues[windowKey] || {}).now_serving) {
                    playNotificationSound();
                    showNumberNotification(windowNum, currentNumber);
                }
                
                // Update display
                const nowServingElement = document.getElementById(`now-serving-${windowNum}`);
                const nextNumberElement = document.getElementById(`next-number-${windowNum}`);
                
                if (nowServingElement) nowServingElement.textContent = currentNumber || '--';
                if (nextNumberElement) nextNumberElement.textContent = windowData.next_number || '--';
            }
            
            currentQueues = data.current_queues;
            setTimeout(checkQueueUpdates, 2000);
        })
        .catch(error => {
            console.error('Error checking queue updates:', error);
            setTimeout(checkQueueUpdates, 5000);
        });
}

// Initial setup
document.addEventListener('DOMContentLoaded', function() {
    // Running text updates
    fetchRunningText();
    setInterval(fetchRunningText, 5000);
    
    // Queue updates
    fetch('get-queues.php')
        .then(response => response.json())
        .then(data => {
            currentQueues = data.current_queues;
            checkQueueUpdates();
        });
    
    // Recall checks
    checkForRecalls();
    setInterval(checkForRecalls, 2000);
});
</script>
    
    <script src="scripts.js"></script>
    
    <script>
// Function to show recalled number
function showRecalledNumber(windowId, number) {
    const windowElement = document.querySelector(`.window:nth-child(${windowId})`);
    if (windowElement) {
        // Create recalled number display
        const recallDisplay = document.createElement('div');
        recallDisplay.className = 'recall-display';
        recallDisplay.textContent = `Recalling: ${number}`;
        windowElement.appendChild(recallDisplay);
        
        // Remove after 5 seconds
        setTimeout(() => {
            recallDisplay.remove();
        }, 5000);
    }
}

// Check for recalled numbers from server
function checkForRecalls() {
    fetch('get-recent-recalls.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(recall => {
                const windowNum = recall.window.replace('Window ', '');
                showRecalledNumber(windowNum, recall.number);
            });
        });
}

// Check every 2 seconds
setInterval(checkForRecalls, 2000);
</script>

<style>
    /* Number Display Styles */
.number-display {
    margin-top: 15px;
}

.number-row {
    display: flex;
    align-items: center;
    margin: 10px 0;
}

.number-label {
    font-size: 1.2rem;
    font-weight: bold;
    margin-right: 10px;
    width: 120px;
    text-align: right;
}

.now-serving {
    font-size: 3rem;
    font-weight: bold;
    color: #4CAF50;
    min-width: 60px;
    display: inline-block;
    text-align: center;
}

.next-number {
    font-size: 2.0rem;
    font-weight: bold;
    color: #F44336;
    min-width: 60px;
    display: inline-block;
    text-align: center;
}

/* Notification Styles */
.notification-container {
    position: absolute;
    bottom: 10px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
}

.window-notification {
    background-color: #ff9800;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    font-size: 0.9rem;
    animation: fadeInOut 3s ease-in-out;
    opacity: 0;
}

@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(10px); }
    20% { opacity: 1; transform: translateY(0); }
    80% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(10px); }
}
</style>

</body>
</html>

* {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #4CAF50, #FFEB3B);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #333;
            overflow-x: auto;
        }

        .header {
            width: 100%;
            padding: 10px 0;
            text-align: center;
        }

        .images {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .logopic {
            width: 90px;
            height: auto;
        }

        .fiestapic, .appliancepic {
            width: 300px;
            height: auto;
        }

        .container-live {
            width: 100%;
            max-width: 100%;
            padding: 10px;
            overflow-x: auto;
        }

        .horizontal-queue {
            display: flex;
            flex-wrap: nowrap;
            gap: 15px;
            padding: 10px;
            min-width: fit-content;
        }

        .window {
            background: white;
            border-radius: 8px;
            padding: 15px;
            min-width: 250px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .window h5 {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .number-display {
            width: 100%;
            text-align: center;
        }

        .number-row {
            margin: 10px 0;
        }

        .number-label {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .now-serving {
            font-size: 3rem;
            font-weight: bold;
            color: #4CAF50;
        }

        .next-number {
            font-size: 2rem;
            font-weight: bold;
            color: #F44336;
        }

        .running-text-container {
            width: 100%;
            padding: 10px;
            background-color: #333;
            color: #fff;
            margin-top: 20px;
            border-radius: 5px;
            overflow: hidden;
        }

        .running-text {
            white-space: nowrap;
            animation: scrollText 20s linear infinite;
        }

        @keyframes scrollText {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* Notification Styles */
        .notification-container {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
        }

        .window-notification {
            background-color: #ff9800;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 1.2rem;
            animation: fadeInOut 3s ease-in-out;
            opacity: 0;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(10px); }
            20% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(10px); }
        }

        /* Recall Display Styles */
        .recall-display {
            position: absolute;
            bottom: -25px;
            left: 0;
            width: 100%;
            background-color: #ff9800;
            color: white;
            padding: 5px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            animation: pulseRecall 1s infinite alternate;
            font-weight: bold;
            z-index: 10;
            font-size: 1rem;
        }

        @keyframes pulseRecall {
            from { background-color: #ff9800; }
            to { background-color: #ff5722; }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .window {
                min-width: 220px;
                padding: 10px;
            }
            .window h5 {
                font-size: 1.3rem;
            }
            .now-serving {
                font-size: 2.5rem;
            }
            .next-number {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 576px) {
            .window {
                min-width: 200px;
            }
            .window h5 {
                font-size: 1.2rem;
            }
            .now-serving {
                font-size: 2.2rem;
            }
            .next-number {
                font-size: 1.6rem;
            }
        }