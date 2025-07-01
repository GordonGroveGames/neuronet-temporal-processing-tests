<?php
require_once __DIR__ . '/check_test_session.php';
require_test_user_login();

$userInfo = get_test_user_info();
if (!$userInfo) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroNet Temporal Processing Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #333;
            line-height: 1.6;
        }
        
        .test-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: white;
            border-radius: 10px;
        }
        
        .screen {
            display: none;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        h2 {
            color: #3498db;
            margin: 1.5rem 0;
        }
        
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 10px 5px;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        
        /* Test Header */
        .test-header {
            margin-bottom: 2rem;
        }
        
        .progress-text {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #7f8c8d;
        }
        
        .progress-container {
            width: 100%;
            height: 10px;
            background-color: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            width: 0%;
            background-color: #3498db;
            transition: width 0.3s ease;
        }
        
        /* Test Area */
        .test-area {
            display: flex;
            width: 100%;
            min-height: 300px;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .test-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-right: 1px solid #f0f0f0;
            cursor: pointer;
            font-size: 24px;
            font-weight: 600;
            user-select: none;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }
        
        .test-image {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .test-section:last-child {
            border-right: none;
        }
        
        
        .test-section.correct {
            background-color: rgba(46, 213, 115, 0.15);
            border: 2px solid #2ed573;
        }
        
        .test-section.incorrect {
            background-color: rgba(255, 71, 87, 0.15);
            border: 2px solid #ff4757;
        }
        
        .test-section.disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .test-label {
            padding: 10px 20px;
            border-radius: 20px;
            background-color: rgba(52, 152, 219, 0.9);
            color: white;
            position: relative;
            z-index: 2;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        /* Score Bar */
        .score-container {
            width: 100%;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .score-bar {
            display: grid;
            grid-template-columns: repeat(15, 1fr);
            width: 100%;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            gap: 1px;
            aspect-ratio: 15/1; /* Maintain aspect ratio for the grid */
        }
        
        .score-indicator {
            position: relative;
            width: 100%;
            padding-bottom: 100%; /* Makes the height equal to the width */
            background-color: rgba(52, 152, 219, 0.1); /* Light blue for empty state */
            transition: background-color 0.3s ease;
        }
        
        .score-indicator.correct {
            background-color: #28a745; /* Green for correct */
        }
        
        .score-indicator.incorrect {
            background-color: #dc3545; /* Red for incorrect */
        }
        
        .score-indicator:last-child {
            border-right: none;
        }
        
        .score-indicator.correct {
            background-color: #2ecc71;
        }
        
        .score-indicator.incorrect {
            background-color: #e74c3c;
        }
        
        /* Results Screen */
        .test-result {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: left;
        }
        
        .test-result h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .test-container {
                padding: 10px;
                margin: 10px;
            }
            
            .test-section {
                font-size: 18px;
            }
            
            .score-indicator {
                flex: 0 0 24px;
                height: 24px;
                min-width: 24px;
                font-size: 14px;
            }
            
            .btn {
                width: 100%;
                margin: 5px 0;
            }
        }

        
        .score-indicator:hover {
            transform: scale(1.05);
        }
        
        .score-indicator.correct {
            background-color: #28a745;
        }
        
        .score-indicator.incorrect {
            background-color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .test-section {
                font-size: 18px;
                padding: 10px;
            }
        }
        
        /* Click to Start Overlay */
        .countdown-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .countdown-content {
            text-align: center;
        }
        
        .countdown-number {
            font-size: 80px;
            font-weight: bold;
            color: #28a745;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        
        .countdown-number:hover {
            transform: scale(1.05);
            color: #34ce57;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <!-- Click to Start Overlay -->
        <div id="countdownOverlay" class="countdown-overlay" style="display: none;">
            <div class="countdown-content">
                <div id="countdownNumber" class="countdown-number">Click to Start</div>
            </div>
        </div>

        <!-- Test Screen -->
        <div id="testScreen" class="screen">
            <div class="test-header">
                <div id="progressText" class="progress-text">Test 1 of 1: Loading...</div>
            </div>
            
            <div class="test-area">
                <div class="test-section" data-zone="Left">
                    <img class="test-image" src="" alt="Left option" style="display: none;">
                    <span class="test-label">Left</span>
                </div>
                <div class="test-section" data-zone="Center">
                    <img class="test-image" src="" alt="Center option" style="display: none;">
                    <span class="test-label">Center</span>
                </div>
                <div class="test-section" data-zone="Right">
                    <img class="test-image" src="" alt="Right option" style="display: none;">
                    <span class="test-label">Right</span>
                </div>
            </div>
            
            <div class="score-container">
                <div class="score-bar" id="scoreBar">
                    <!-- Score indicators will be added here -->
                </div>
            </div>
        </div>
        
        <!-- Results Screen -->
        <div id="resultsScreen" class="screen" style="display: none;">
            <div class="container">
                <div id="testResults"></div>
                <div id="finalResults"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Set user session data from PHP session
        const userInfo = {
            fullName: <?= json_encode($userInfo['username']) ?>,
            email: <?= json_encode($userInfo['email']) ?>,
            userID: <?= json_encode($userInfo['email']) ?> // Using email as userID
        };
        
        // Store in sessionStorage for the saveResults function
        sessionStorage.setItem('userInfo', JSON.stringify(userInfo));
        
        // Generate and store session ID if not exists
        if (!sessionStorage.getItem('sessionId')) {
            const sessionId = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
            sessionStorage.setItem('sessionId', sessionId);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const testScreen = document.getElementById('testScreen');
            const resultsScreen = document.getElementById('resultsScreen');
            const countdownOverlay = document.getElementById('countdownOverlay');
            const countdownNumber = document.getElementById('countdownNumber');
            const testSections = document.querySelectorAll('.test-section');
            const scoreBar = document.getElementById('scoreBar');
            const progressText = document.getElementById('progressText');
            const testResults = document.getElementById('testResults');
            const finalResults = document.getElementById('finalResults');
            
            // Test configuration - will be loaded from admin assessments
            let TESTS = [];
            
            // Test state
            let currentTestIndex = 0;
            let currentTest = null;
            let testResultsData = [];
            
            // Audio preloading and management
            let preloadedAudio = {};
            let currentAudio = null;
            
            // Preload audio files for faster playback
            function preloadTestAudio() {
                if (!currentTest || !currentTest.sounds) return;
                
                const sounds = [
                    currentTest.sounds.left,
                    currentTest.sounds.center,
                    currentTest.sounds.right
                ];
                
                sounds.forEach(soundUrl => {
                    if (soundUrl && !preloadedAudio[soundUrl]) {
                        const audio = new Audio();
                        audio.preload = 'auto';
                        audio.src = soundUrl;
                        audio.volume = 0.7;
                        
                        // Keep reference for reuse
                        preloadedAudio[soundUrl] = audio;
                        
                        // Start loading immediately
                        audio.load();
                    }
                });
            }
            
            // Load assessments from admin data
            async function loadAssessments() {
                try {
                    const response = await fetch('get_assessments.php');
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to load assessments');
                    }
                    
                    // Flatten all tests from all assessments into a single test array
                    TESTS = [];
                    data.assessments.forEach(assessment => {
                        TESTS.push(...assessment.tests);
                    });
                    
                    if (TESTS.length === 0) {
                        throw new Error('No tests found in assessments');
                    }
                    
                    console.log('Loaded', TESTS.length, 'tests from assessments');
                    return true;
                    
                } catch (error) {
                    console.error('Error loading assessments:', error);
                    // Fallback to a basic test if assessments fail to load
                    TESTS = [{
                        id: 'fallback',
                        name: 'Basic Test',
                        description: 'Fallback test - please contact administrator',
                        type: 'fallback',
                        entries: 5,
                        zones: ['Left', 'Center', 'Right']
                    }];
                    return false;
                }
            }
            
            // Initialize the application
            async function init() {
                // Set up event listeners
                testSections.forEach(section => {
                    section.addEventListener('click', handleSectionClick);
                });
                
                // Load assessments then start tests
                await loadAssessments();
                startTests();
            }
            
            // Show a specific screen
            function showScreen(screen) {
                testScreen.style.display = 'none';
                resultsScreen.style.display = 'none';
                
                switch(screen) {
                    case 'test':
                        testScreen.style.display = 'block';
                        break;
                    case 'results':
                        resultsScreen.style.display = 'block';
                        break;
                }
            }
            
            // Start the test sequence
            function startTests() {
                currentTestIndex = 0;
                testResultsData = [];
                startNextTest();
            }
            
            // Start the next test in the sequence
            function startNextTest() {
                if (currentTestIndex >= TESTS.length) {
                    // All tests completed
                    showResults();
                    return;
                }
                
                currentTest = {
                    ...TESTS[currentTestIndex],
                    startTime: Date.now(),
                    responses: [],
                    answerSequence: generateAnswerSequence(TESTS[currentTestIndex].entries, TESTS[currentTestIndex].zones)
                };
                
                // Update UI - format: Test 1 of 1: <test name>
                progressText.textContent = `Test ${currentTestIndex + 1} of ${TESTS.length}: ${currentTest.name}`;
                
                // Clear previous score indicators
                updateScoreBar([], currentTest.entries);
                
                // Setup test display (images, etc.)
                setupTestDisplay();
                
                // Preload audio for faster playback
                preloadTestAudio();
                
                // Show test screen
                showScreen('test');
                
                // Show click to start overlay
                showClickToStart();
            }
            
            // Show click to start overlay
            function showClickToStart() {
                countdownOverlay.style.display = 'flex';
                countdownNumber.textContent = 'Click to Start';
                countdownNumber.className = 'countdown-number';
                
                // Make the overlay clickable to start the trial
                countdownOverlay.onclick = function() {
                    countdownOverlay.style.display = 'none';
                    countdownOverlay.onclick = null; // Remove click handler
                    startTrial();
                };
            }
            
            // Setup the test display with images and prepare sounds
            function setupTestDisplay() {
                const testImages = document.querySelectorAll('.test-image');
                const testLabels = document.querySelectorAll('.test-label');
                
                if (currentTest.type === 'matching' && currentTest.images) {
                    // Show images for admin-created matching tests
                    testImages[0].src = currentTest.images.left;
                    testImages[1].src = currentTest.images.center;
                    testImages[2].src = currentTest.images.right;
                    
                    testImages.forEach(img => {
                        img.style.display = 'block';
                        img.onerror = function() {
                            console.warn('Failed to load image:', this.src);
                            this.style.display = 'none';
                        };
                    });
                    
                    // Hide text labels when showing images
                    testLabels.forEach(label => {
                        label.style.display = 'none';
                    });
                } else {
                    // Hide images for other test types
                    testImages.forEach(img => {
                        img.style.display = 'none';
                    });
                    
                    // Show text labels
                    testLabels.forEach(label => {
                        label.style.display = 'block';
                    });
                }
            }
            
            // Start a new trial
            function startTrial() {
                if (!currentTest) return;
                
                const currentTrial = currentTest.responses.length;
                
                // Check if test is complete
                if (currentTrial >= currentTest.entries) {
                    // Test completed
                    currentTest.endTime = Date.now();
                    testResultsData.push({
                        ...currentTest,
                        score: calculateScore(currentTest.responses)
                    });
                    
                    // Move to next test
                    currentTestIndex++;
                    startNextTest();
                    return;
                }
                
                // Update progress
                updateProgress(currentTrial + 1, currentTest.entries);
                
                // Get the correct zone for this trial
                const correctZone = currentTest.answerSequence[currentTrial];
                
                // Record trial start time
                currentTest.lastTrialStart = Date.now();
                
                // Play the corresponding sound for admin-created matching tests
                if (currentTest.type === 'matching' && currentTest.sounds) {
                    const soundUrl = getSoundForZone(correctZone);
                    if (soundUrl) {
                        console.log('Playing sound for zone:', correctZone, 'URL:', soundUrl);
                        playSound(soundUrl);
                    } else {
                        console.warn('No sound URL found for zone:', correctZone);
                    }
                } else {
                    console.warn('No sounds available or not a matching test');
                }
                
                console.log(`Test ${currentTest.name}, Trial ${currentTrial + 1}: ${correctZone}`);
                
                // Enable the test sections for user interaction
                enableTestSections();
            }
            
            // Get the sound URL for a specific zone
            function getSoundForZone(zone) {
                if (!currentTest.sounds) return null;
                
                switch (zone) {
                    case 'Left':
                        return currentTest.sounds.left;
                    case 'Center':
                        return currentTest.sounds.center;
                    case 'Right':
                        return currentTest.sounds.right;
                    default:
                        return null;
                }
            }
            
            // Play a sound file with minimal latency
            function playSound(soundUrl) {
                try {
                    console.log('Attempting to play sound:', soundUrl);
                    
                    // Stop any currently playing audio immediately
                    if (currentAudio) {
                        currentAudio.pause();
                        currentAudio.currentTime = 0;
                    }
                    
                    // Use preloaded audio if available, otherwise create new
                    let audio = preloadedAudio[soundUrl];
                    if (!audio) {
                        audio = new Audio(soundUrl);
                        audio.volume = 0.7;
                        audio.preload = 'auto';
                        preloadedAudio[soundUrl] = audio;
                    }
                    
                    // Reset to beginning for reuse
                    audio.currentTime = 0;
                    audio.volume = 0.7;
                    
                    // Set as current audio
                    currentAudio = audio;
                    
                    // Play immediately - no waiting for events
                    const playPromise = audio.play();
                    
                    if (playPromise !== undefined) {
                        playPromise.then(() => {
                            console.log('Audio playing successfully');
                        }).catch(error => {
                            console.error('Failed to play sound:', error);
                        });
                    }
                    
                } catch (error) {
                    console.error('Error in playSound function:', error);
                }
            }
            
            // Handle section click during test - optimized for minimal audio latency
            function handleSectionClick(event) {
                if (!currentTest) return;
                
                const selectedZone = event.currentTarget.dataset.zone;
                const currentTrial = currentTest.responses.length;
                
                // Check if test is complete first
                if (currentTrial >= currentTest.entries) {
                    return; // Ignore clicks after test is done
                }
                
                const correctZone = currentTest.answerSequence[currentTrial];
                const isCorrect = selectedZone === correctZone;
                const responseTime = Date.now() - (currentTest.lastTrialStart || currentTest.startTime);
                
                // PRIORITY 1: Start next audio immediately to minimize latency
                const nextTrial = currentTrial + 1;
                if (nextTrial < currentTest.entries) {
                    const nextCorrectZone = currentTest.answerSequence[nextTrial];
                    const nextSoundUrl = getSoundForZone(nextCorrectZone);
                    if (nextSoundUrl) {
                        playSound(nextSoundUrl); // Play next sound immediately
                        currentTest.lastTrialStart = Date.now(); // Update trial start time
                    }
                }
                
                // PRIORITY 2: Record response data
                currentTest.responses.push({
                    trial: currentTrial + 1,
                    correctZone,
                    selectedZone,
                    isCorrect,
                    responseTime
                });
                
                // PRIORITY 3: Update UI (less time-critical)
                requestAnimationFrame(() => {
                    updateScoreBar(
                        currentTest.responses.map(r => r.isCorrect),
                        currentTest.entries
                    );
                    
                    // Check if test is complete after this response
                    if (currentTest.responses.length >= currentTest.entries) {
                        setTimeout(() => {
                            currentTest.endTime = Date.now();
                            testResultsData.push({
                                ...currentTest,
                                score: calculateScore(currentTest.responses)
                            });
                            currentTestIndex++;
                            startNextTest();
                        }, 100); // Small delay to let final audio start
                    }
                });
            }
            
            
            // Enable test sections for interaction
            function enableTestSections() {
                testSections.forEach(section => {
                    section.classList.remove('disabled');
                });
            }
            
            // Disable test sections
            function disableTestSections() {
                testSections.forEach(section => {
                    section.classList.add('disabled');
                });
            }
            
            // Update the progress display
            function updateProgress(current, total) {
                // Keep the header text unchanged - just show test name
                progressText.textContent = `Test ${currentTestIndex + 1} of ${TESTS.length}: ${currentTest.name}`;
            }
            
            // Update the score bar
            function updateScoreBar(responses, total) {
                const scoreBar = document.getElementById('scoreBar');
                scoreBar.innerHTML = ''; // Clear existing indicators
                
                for (let i = 0; i < total; i++) {
                    const indicator = document.createElement('div');
                    indicator.className = 'score-indicator';
                    
                    if (i < responses.length) {
                        indicator.classList.add(responses[i] ? 'correct' : 'incorrect');
                    }
                    
                    scoreBar.appendChild(indicator);
                }
            }
            
            // Calculate score for a test
            function calculateScore(responses) {
                const correct = responses.filter(r => r.isCorrect).length;
                const total = responses.length;
                return {
                    correct,
                    total,
                    percentage: Math.round((correct / total) * 100),
                    avgResponseTime: Math.round(
                        responses.reduce((sum, r) => sum + r.responseTime, 0) / total
                    )
                };
            }
            
            // Save results to the server
            async function saveResults() {
                try {
                    // Get user info from session storage
                    const userInfo = JSON.parse(sessionStorage.getItem('userInfo'));
                    
                    if (!userInfo) {
                        throw new Error('User session not found. Please start the test from the beginning.');
                    }
                    
                    const { fullName, email, userID } = userInfo;
                    const sessionId = sessionStorage.getItem('sessionId');
                    
                    if (!sessionId) {
                        console.warn('No session ID found, creating a new one');
                        // Generate a new session ID if none exists
                        const newSessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
                        sessionStorage.setItem('sessionId', newSessionId);
                    }
                    
                    // Send each test result individually
                    const savePromises = [];
                    let totalRequests = 0;
                    
                    testResultsData.forEach(test => {
                        test.responses.forEach((response, index) => {
                            if (!response) return; // Skip if no response
                            
                            totalRequests++;
                            
                            const testData = {
                                fullName: fullName,
                                email: email,
                                userID: userID,
                                testName: test.name,
                                promptNumber: index + 1,
                                userAnswer: response.selectedZone || '',
                                correctAnswer: response.correctZone || '',
                                responseTime: response.responseTime || 0,
                                sessionId: sessionId || sessionStorage.getItem('sessionId')
                            };
                            
                            // Add timestamp to ensure unique requests
                            testData.timestamp = new Date().toISOString();
                            
                            // Send each test result as a separate request
                            savePromises.push(
                                fetch('submit.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify(testData),
                                    credentials: 'same-origin' // Include cookies for session
                                })
                                .then(async resp => {
                                    const data = await resp.json();
                                    if (!resp.ok) {
                                        console.error('Error saving result:', data);
                                        throw new Error(data.message || `HTTP error! status: ${resp.status}`);
                                    }
                                    return data;
                                })
                                .catch(error => {
                                    console.error('Error in save promise:', error);
                                    throw error; // Re-throw to be caught by Promise.all
                                })
                            );
                        });
                    });
                    
                    if (totalRequests === 0) {
                        console.warn('No test results to save');
                        return { success: true, message: 'No results to save' };
                    }
                    
                    // Wait for all saves to complete
                    const results = await Promise.all(savePromises);
                    console.log('All results saved successfully:', results);
                    
                    return { 
                        success: true, 
                        message: 'All test results saved successfully',
                        savedCount: results.length
                    };
                    
                } catch (error) {
                    console.error('Error saving results:', error);
                    // Even if saving fails, we'll still show the results to the user
                    return { 
                        success: false, 
                        message: error.message,
                        error: error.toString()
                    };
                }
            }

            // Show the final results
            async function showResults() {
                showScreen('results');
                
                // Generate results HTML
                let resultsHTML = '<h2>Test Results</h2>';
                
                testResultsData.forEach((test, index) => {
                    resultsHTML += `
                        <div class="test-result">
                            <h4>Test ${index + 1}: ${test.name}</h4>
                            <p>${test.description}</p>
                            <p>Score: ${test.score.correct} / ${test.score.total} (${test.score.percentage}%)</p>
                            <p>Average Response Time: ${test.score.avgResponseTime}ms</p>
                            <div class="score-bar">
                                ${Array(test.entries).fill().map((_, i) => {
                                    const response = test.responses[i];
                                    if (!response) return `<div class="score-indicator">${i+1}</div>`;
                                    return `<div class="score-indicator ${response.isCorrect ? 'correct' : 'incorrect'}">
                                        ${response.isCorrect ? '✓' : '✕'}
                                    </div>`;
                                }).join('')}
                            </div>
                        </div>
                    `;
                });
                
                // Calculate overall score
                const totalCorrect = testResultsData.reduce((sum, test) => sum + test.score.correct, 0);
                const totalTrials = testResultsData.reduce((sum, test) => sum + test.score.total, 0);
                const overallPercentage = Math.round((totalCorrect / totalTrials) * 100);
                const avgResponseTime = Math.round(
                    testResultsData.reduce((sum, test) => sum + test.score.avgResponseTime, 0) / testResultsData.length
                );
                
                const summaryHTML = `
                    <div class="summary">
                        <h3>Summary</h3>
                        <p>Overall Score: ${totalCorrect} / ${totalTrials} (${overallPercentage}%)</p>
                        <p>Average Response Time: ${avgResponseTime}ms</p>
                        <div id="saveStatus"></div>
                        <button id="restartButton" class="btn">Restart Test</button>
                        <button id="homeButton" class="btn" style="margin-left: 10px;">Home</button>
                    </div>
                `;
                
                testResults.innerHTML = resultsHTML;
                finalResults.innerHTML = summaryHTML;
                
                // Add event listener for restart button
                document.getElementById('restartButton').addEventListener('click', startTests);
                
                // Add event listener for home button
                document.getElementById('homeButton').addEventListener('click', function() {
                    window.location.href = 'index.php';
                });
                
                // Save results to the server
                const saveStatus = document.getElementById('saveStatus');
                saveStatus.textContent = 'Saving results...';
                
                try {
                    const result = await saveResults();
                    if (result && result.success) {
                        saveStatus.textContent = 'Results saved successfully!';
                        saveStatus.style.color = 'green';
                    } else {
                        saveStatus.textContent = 'Error saving results. Please try again.';
                        saveStatus.style.color = 'red';
                        console.error('Failed to save results:', result?.message);
                    }
                } catch (error) {
                    saveStatus.textContent = 'Error saving results. Please try again.';
                    saveStatus.style.color = 'red';
                    console.error('Error saving results:', error);
                }
            }
            
            // Generate a random sequence of answers with no more than 3 of the same answer in a row
            function generateAnswerSequence(length, zones) {
                const sequence = [];
                let lastZone = null;
                let repeatCount = 0;
                
                for (let i = 0; i < length; i++) {
                    // Filter out the last zone if it's been repeated 3 times
                    const availableZones = repeatCount >= 3 
                        ? zones.filter(zone => zone !== lastZone)
                        : [...zones];
                    
                    // Randomly select a zone from available zones
                    const randomIndex = Math.floor(Math.random() * availableZones.length);
                    const selectedZone = availableZones[randomIndex];
                    
                    // Update repeat count
                    if (selectedZone === lastZone) {
                        repeatCount++;
                    } else {
                        repeatCount = 1;
                        lastZone = selectedZone;
                    }
                    
                    sequence.push(selectedZone);
                }
                
                return sequence;
            }
            
            // Start the application
            init();
        });
    </script>
</body>
</html>
