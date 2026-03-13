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

// Load window visibility settings
$visibilityFile = 'data/window_visibility.json';
$defaultVisibility = [
    'Window 1' => true,
    'Window 2' => true,
    'Window 3' => true,
    'Window 4' => true,
    'Window 5' => true
];

if (file_exists($visibilityFile)) {
    $visibility = json_decode(file_get_contents($visibilityFile), true) ?: $defaultVisibility;
} else {
    $visibility = $defaultVisibility;
}

// Filter windows based on visibility
$allWindows = ['Window 1', 'Window 2', 'Window 3', 'Window 4', 'Window 5'];
$visibleWindows = array_filter($allWindows, function($window) use ($visibility) {
    return $visibility[$window] ?? true;
});

// Get all windows (1-5)
$allWindows = ['Window 1', 'Window 2', 'Window 3', 'Window 4', 'Window 5'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fiesta Appliance - Live Queues</title>
    <link rel="stylesheet" href="styles-live.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #4CAF50, #FFEB3B);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #333;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .header {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 10px 0;
            position: relative;
            z-index: 10;
        }

        .images {
            margin: 0,0,0,0;
            display: flex;               /* Use flexbox for layout */
            align-items: center;        /* Center items vertically */
        }

        .other-pics {
            display: flex;              /* Use flexbox for the other images */
            flex-direction: column;     /* Stack fiesta and appliance vertically */
        }

        .logopic {
            width: 90px;  
            height: auto;  
            margin-right: 20px;
        }

        .fiestapic {
            width: 300px;  
            height: auto;  
        }

        .appliancepic {
            width: 300px;  
            height: auto;  
        }

        .container-live {
            width: 100%;
            max-width: 100%;
            padding: 10px;
            overflow-x: auto;
        }

        .queue-display {
            display: flex;
            flex-wrap: nowrap;
            gap: 15px;
            padding: 110px;
            min-width: fit-content;
            justify-content: center;
        }

.window {
    background: white;
    border-radius: 8px;
    padding: 50px;
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
            margin-top: 10px;
            border-radius: 5px;
            overflow: hidden;
        }

        .running-text {
            white-space: nowrap;
            animation: scrollText 20s linear infinite;
        }

        .running-text {
            display: inline-block;
            white-space: nowrap;
            padding-left: 100%;
            animation: scrollText 20s linear infinite;
            font-size: 1.2rem;
            font-weight: bold;
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
        @media (max-width: 1200px) {
            .now-serving {
                font-size: 3rem;
            }
            .next-number {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .queue-display {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .queue-display {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(3, 1fr);
            }
            
            .now-serving {
                font-size: 2.5rem;
            }
            
            .next-number {
                font-size: 1.8rem;
            }
            
            .window h5 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .queue-display {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(5, 1fr);
            }
            
            .now-serving {
                font-size: 3rem;
            }
            
            .next-number {
                font-size: 2.2rem;
            }
            
            .images {
                flex-direction: column;
                gap: 5px;
            }
            
            .logopic {
                height: 60px;
            }
            
            .fiestapic, .appliancepic {
                height: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="images">
            <img src="images/logo1.png" alt="Company Logo" class="logopic">
            <div class="other-pics">
                <img src="images/fiesta.png" alt="Fiesta" class="fiestapic">
                <img src="images/appliance.png" alt="Appliance" class="appliancepic">
            </div>
        </div>
    </div>
    
    <div class="container-live" id="live-queues-container">
        <div class="queue-display">
            <?php foreach ($visibleWindows as $window): ?>
                <?php 
                $windowData = $queues['current_queues'][$window] ?? ['now_serving' => null, 'next_number' => null];
                $cashier = array_filter($cashiers, function($c) use ($window) {
                    return $c['window'] === $window;
                });
                $cashierName = !empty($cashier) ? reset($cashier)['username'] : 'Not assigned';
                ?>
                <div class="window">
                    <h5><?php echo htmlspecialchars($window); ?></h5>
                    <div class="number-display">
                        <div class="number-row">
                            <span class="number-label">NOW SERVING</span>
                            <span class="now-serving" id="now-serving-<?php echo substr($window, -1); ?>">
                                <?php echo $windowData['now_serving'] ?? '--'; ?>
                            </span>
                        </div>
                        <div class="number-row">
                            <span class="number-label">NEXT NUMBER</span>
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
    </div>

    <!-- Audio elements for different sounds -->
    <audio id="notificationSound" preload="auto">
        <source src="sounds/notification.mp3" type="audio/mpeg">
        <source src="sounds/notification.ogg" type="audio/ogg">
    </audio>
    
    <audio id="recallSound" preload="auto">
        <source src="sounds/recall.mp3" type="audio/mpeg">
        <source src="sounds/recall.ogg" type="audio/ogg">
    </audio>
    
    <audio id="numberUpdateSound" preload="auto">
        <source src="sounds/number_update.mp3" type="audio/mpeg">
        <source src="sounds/number_update.ogg" type="audio/ogg">
    </audio>

    <script>
    // Initialize speech synthesis
    const speechSynth = window.speechSynthesis;
    let voicesLoaded = false;

    // Audio elements
    const notificationSound = document.getElementById('notificationSound');
    const recallSound = document.getElementById('recallSound');
    const numberUpdateSound = document.getElementById('numberUpdateSound');

    // Store current queue numbers
    let currentQueues = {};

    // Function to load voices (required for some browsers)
    function loadVoices() {
        return new Promise((resolve) => {
            const voices = speechSynth.getVoices();
            if (voices.length > 0) {
                voicesLoaded = true;
                resolve(voices);
            } else {
                speechSynth.onvoiceschanged = () => {
                    voicesLoaded = true;
                    resolve(speechSynth.getVoices());
                };
            }
        });
    }

    // Function to play a sound
    function playSound(soundElement) {
        if (soundElement) {
            soundElement.currentTime = 0;
            soundElement.play().catch(e => console.log("Audio play failed:", e));
        }
    }

    // Function to speak numbers in digit format (e.g., 123 → "one two three")
    function speakAnnouncement(windowNum, number, isRecall = false) {
        if (!number || number === '--' || !voicesLoaded) return;

        const digitWords = ['zero', 'one', 'two', 'three', 'four', 
                          'five', 'six', 'seven', 'eight', 'nine'];
        const digits = String(number).split('');
        const spokenText = digits.map(d => digitWords[d]).join(' ');

        // Cancel any ongoing speech
        speechSynth.cancel();

        const utterance = new SpeechSynthesisUtterance();
        
        if (isRecall) {
            utterance.text = `Window ${windowNum}, recalling number ${spokenText}`;
            playSound(recallSound);
        } else {
            utterance.text = `Window ${windowNum}, now serving number ${spokenText}`;
            playSound(numberUpdateSound);
        }
        
        utterance.rate = 0.9;  // Slightly slower than normal
        utterance.volume = 1;  // Max volume
        utterance.pitch = 1;  // Normal pitch

        // Try to select a pleasant voice if available
        const voices = speechSynth.getVoices();
        const preferredVoice = voices.find(v => 
            v.name.includes('Google') || 
            v.name.includes('Microsoft') || 
            v.name.includes('Samantha') ||  // Common macOS voice
            v.name.includes('Zira')        // Common Windows voice
        );
        
        if (preferredVoice) {
            utterance.voice = preferredVoice;
        }

        // Queue the announcement to play after the beep
        setTimeout(() => {
            speechSynth.speak(utterance);
        }, 300);  // Short delay after the beep
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
            
            // Play sound and speak the number
            speakAnnouncement(windowNum, number);
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
                    const nextNumber = windowData.next_number;
                    
                    // Check if this is a new number being served
                    if (currentNumber && currentNumber !== (currentQueues[windowKey] || {}).now_serving) {
                        showNumberNotification(windowNum, currentNumber);
                    }
                    
                    // Check if next number has changed
                    if (nextNumber && nextNumber !== (currentQueues[windowKey] || {}).next_number) {
                        playSound(notificationSound);
                    }
                    
                    // Update display
                    const nowServingElement = document.getElementById(`now-serving-${windowNum}`);
                    const nextNumberElement = document.getElementById(`next-number-${windowNum}`);
                    
                    if (nowServingElement) nowServingElement.textContent = currentNumber || '--';
                    if (nextNumberElement) nextNumberElement.textContent = nextNumber || '--';
                }
                
                currentQueues = data.current_queues;
                setTimeout(checkQueueUpdates, 2000);  // Check every 2 seconds
            })
            .catch(error => {
                console.error('Error checking queue updates:', error);
                setTimeout(checkQueueUpdates, 5000);  // Retry after 5 seconds on error
            });
    }

    // Function to fetch running text from JSON
    function fetchRunningText() {
        fetch('data/settings.json')
            .then(response => response.json())
            .then(data => {
                const runningTextDiv = document.getElementById("runningText");
                if (runningTextDiv && data.running_text) {
                    runningTextDiv.textContent = data.running_text;
                }
            })
            .catch(error => console.error('Error fetching running text:', error));
    }

    // Function to show recalled number
    function showRecalledNumber(windowId, number) {
        const windowElement = document.querySelector(`.window:nth-child(${windowId})`);
        if (windowElement) {
            // Create recalled number display
            const recallDisplay = document.createElement('div');
            recallDisplay.className = 'recall-display';
            recallDisplay.textContent = `Recalling: ${number}`;
            windowElement.appendChild(recallDisplay);
            
            // Play recall sound and announcement
            speakAnnouncement(windowId, number, true);
            
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

    // Initial setup when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Load voices first
        loadVoices().then(() => {
            console.log('Voices loaded successfully');
        });

        // Running text updates
        fetchRunningText();
        setInterval(fetchRunningText, 3000);  // Update every 3 seconds
        
        // Initial queue load
        fetch('get-queues.php')
            .then(response => response.json())
            .then(data => {
                currentQueues = data.current_queues;
                // Start periodic checking
                checkQueueUpdates();
            });
        
        // Recall checks
        checkForRecalls();
        setInterval(checkForRecalls, 2000);  // Check every 2 seconds
    });
    </script>
</body>
</html>