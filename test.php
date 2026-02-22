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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Test — The Fluency Factor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/touch-fixes.css">
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: var(--surface-1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .test-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            box-shadow: var(--shadow-md);
            background: var(--surface-0);
            border-radius: var(--radius-lg);
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
            color: var(--text-primary);
            margin-bottom: 1.5rem;
        }

        .btn {
            background: var(--primary);
            color: #fff;
            border: 1px solid var(--primary-dark);
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition);
            margin: 10px 5px;
            box-shadow: inset 0 1px rgba(255,255,255,0.15), 0 1px 3px rgba(0,0,0,0.1);
        }

        .btn:hover {
            background: var(--primary-dark);
            box-shadow: inset 0 1px rgba(255,255,255,0.1), 0 4px 8px rgba(79,70,229,0.25);
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.15);
        }

        .btn:disabled {
            background: var(--text-muted);
            border-color: var(--text-muted);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        
        /* Test Header */
        .test-header {
            margin-bottom: 2rem;
        }
        
        .progress-text {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }
        
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: var(--surface-2);
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            width: 0%;
            background: var(--primary-gradient);
            transition: width 0.3s ease;
            border-radius: 9999px;
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
            -webkit-user-select: none;
            position: relative;
            overflow: hidden;
            padding: 20px;
            touch-action: manipulation;
            -webkit-touch-callout: none;
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
            pointer-events: none;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
            touch-action: none;
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
            touch-action: none;
            -webkit-touch-callout: none;
            pointer-events: none;
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
            min-height: 40px; /* Minimum height for colored squares */
            /* Removed fixed aspect-ratio to allow dynamic sizing */
        }
        
        .score-indicator {
            position: relative;
            width: 100%;
            min-height: 38px; /* Minimum height for colored squares */
            background-color: rgba(52, 152, 219, 0.1); /* Light blue for empty state */
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            /* Removed padding-bottom to allow dynamic height */
        }

        .score-indicator img {
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
            pointer-events: none;
        }
        
        /* Ensure square aspect ratio for colored indicators without images */
        .score-indicator.square {
            aspect-ratio: 1;
            height: auto;
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
        .results-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .results-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.25rem 0;
        }
        .results-header .results-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .test-result {
            background: var(--surface-0);
            border-radius: var(--radius-lg);
            padding: 0;
            margin: 0 0 1rem 0;
            box-shadow: var(--shadow-sm);
            text-align: left;
            overflow: hidden;
            transition: box-shadow var(--transition);
        }
        .test-result:hover {
            box-shadow: var(--shadow-md);
        }
        .test-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid var(--border-light);
        }
        .test-result-header h4 {
            color: var(--text-primary);
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        .test-result-body {
            padding: 1rem 1.25rem;
        }
        .result-stats {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        .result-stat {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }
        .result-stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
            color: var(--text-muted);
        }
        .result-stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .result-score-bar {
            display: flex;
            gap: 3px;
            flex-wrap: wrap;
        }
        .result-score-dot {
            width: 28px;
            height: 28px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            transition: transform var(--transition);
        }
        .result-score-dot:hover {
            transform: scale(1.15);
        }
        .result-score-dot.correct { background: var(--success); }
        .result-score-dot.incorrect { background: var(--danger); }
        .result-score-dot.empty { background: var(--surface-2); color: var(--text-muted); }

        .summary {
            background: var(--surface-0);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin: 1.5rem 0 0 0;
            box-shadow: var(--shadow-sm);
            border-top: 3px solid var(--primary);
        }
        .summary h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1rem 0;
        }
        .summary-stats {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            margin-bottom: 1.25rem;
        }
        .summary-stat {
            text-align: center;
        }
        .summary-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .summary-stat-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }
        .summary-actions {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }
        .summary-actions .btn-outline {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-default);
            padding: 10px 24px;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition);
            margin: 0;
            box-shadow: none;
        }
        .summary-actions .btn-outline:hover {
            background: var(--surface-1);
            color: var(--text-primary);
            border-color: var(--border-default);
            transform: translateY(-1px);
        }
        #saveStatus {
            text-align: center;
            font-size: 0.85rem;
            font-weight: 500;
            min-height: 1.5em;
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
        
        /* Pre-start buttons (shown in score-container before test begins) */
        .score-container.pre-start-mode {
            pointer-events: auto;
            touch-action: auto;
        }
        .pre-start-area {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
        }
        .btn-start {
            font-size: 1.25rem;
            padding: 16px 48px;
            background: var(--success, #28a745);
            border-color: #218838;
        }
        .btn-start:hover {
            background: #218838;
        }
        .btn-hear-sounds {
            font-size: 1.25rem;
            padding: 16px 36px;
            background: var(--primary);
            border-color: var(--primary-dark);
        }
        .btn-hear-sounds:hover {
            background: var(--primary-dark);
        }
        .btn-hear-sounds:disabled {
            background: var(--text-muted);
            border-color: var(--text-muted);
        }
        @media (max-width: 768px) {
            .pre-start-area {
                flex-direction: column;
                gap: 0.75rem;
            }
            .btn-start, .btn-hear-sounds {
                width: 100%;
                font-size: 1.1rem;
                padding: 14px 24px;
            }
        }

        /* Navbar overrides for test page */
        .navbar-admin {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 900;
        }
        body.has-navbar {
            padding-top: 56px;
        }
        #restartTestBtn {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition);
        }
        #restartTestBtn:hover {
            background: rgba(255,255,255,0.22);
            border-color: rgba(255,255,255,0.4);
        }
    </style>
</head>
<body>
    <!-- Header Navbar -->
    <nav class="navbar-admin d-flex justify-content-between align-items-center" id="testNavbar" style="display:none;">
        <span class="navbar-brand">The Fluency Factor</span>
        <div class="d-flex align-items-center gap-3">
            <span style="color:rgba(255,255,255,0.7);font-size:0.85rem;"><?= htmlspecialchars($userInfo['username']) ?></span>
            <button type="button" class="btn-logout" id="restartTestBtn" style="display:none;">
                <i class="fa-solid fa-rotate-right me-1"></i> Restart Test
            </button>
            <a href="index.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket me-1"></i> Exit</a>
        </div>
    </nav>

    <div class="test-container">
        <!-- Test Screen -->
        <div id="testScreen" class="screen">
            <div class="test-header">
                <div id="progressText" class="progress-text">Test 1 of 1: Loading...</div>
            </div>
            
            <div class="test-area">
                <div class="test-section" data-zone="Left">
                    <img class="test-image" src="" alt="Left option" style="display: none;" draggable="false">
                    <span class="test-label">Left</span>
                </div>
                <div class="test-section" data-zone="Center">
                    <img class="test-image" src="" alt="Center option" style="display: none;" draggable="false">
                    <span class="test-label">Center</span>
                </div>
                <div class="test-section" data-zone="Right">
                    <img class="test-image" src="" alt="Right option" style="display: none;" draggable="false">
                    <span class="test-label">Right</span>
                </div>
            </div>
            
            <div class="score-container" id="scoreContainer">
                <div class="pre-start-area" id="preStartArea">
                    <button type="button" class="btn btn-start" id="btnStart">
                        <i class="fa-solid fa-play me-2"></i> Start
                    </button>
                    <button type="button" class="btn btn-hear-sounds" id="btnHearSounds">
                        <i class="fa-solid fa-volume-high me-2"></i> Hear the Sounds
                    </button>
                </div>
                <div class="score-bar" id="scoreBar" style="display:none;">
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
            const testSections = document.querySelectorAll('.test-section');
            const scoreBar = document.getElementById('scoreBar');
            const scoreContainer = document.getElementById('scoreContainer');
            const preStartArea = document.getElementById('preStartArea');
            const btnStart = document.getElementById('btnStart');
            const btnHearSounds = document.getElementById('btnHearSounds');
            const progressText = document.getElementById('progressText');
            const testResults = document.getElementById('testResults');
            const finalResults = document.getElementById('finalResults');
            const testNavbar = document.getElementById('testNavbar');
            const restartTestBtn = document.getElementById('restartTestBtn');
            
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
                    const params = new URLSearchParams(window.location.search);
                    const selectedIds = params.get('assessments') || '';
                    const singleTestId = params.get('test_id') || '';
                    let fetchUrl;
                    if (singleTestId) {
                        fetchUrl = 'get_assessments.php?test_id=' + encodeURIComponent(singleTestId);
                    } else if (selectedIds) {
                        fetchUrl = 'get_assessments.php?ids=' + encodeURIComponent(selectedIds);
                    } else {
                        fetchUrl = 'get_assessments.php';
                    }
                    const response = await fetch(fetchUrl);
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
            let touchHandled = false;

            // Show/hide the restart button based on whether a test is actively running
            function showRestartButton(show) {
                restartTestBtn.style.display = show ? '' : 'none';
            }

            // Restart the current assessment from the beginning without saving
            function restartAssessment() {
                // Stop any playing audio
                if (currentAudio) {
                    currentAudio.pause();
                    currentAudio.currentTime = 0;
                    currentAudio = null;
                }
                previewPlaying = false;

                // Reset all test state — discard partial results
                currentTestIndex = 0;
                currentTest = null;
                testResultsData = [];

                // Re-start from the first test
                startTests();
            }

            // Wire up the restart button
            restartTestBtn.addEventListener('click', function() {
                if (confirm('Restart? Your progress on the current test will not be saved.')) {
                    restartAssessment();
                }
            });

            async function init() {
                // Show the navbar
                testNavbar.style.display = '';
                document.body.classList.add('has-navbar');

                // Pre-start button handlers
                btnStart.addEventListener('click', function() {
                    if (previewPlaying) return;
                    // Stop any preview audio
                    if (currentAudio) {
                        currentAudio.pause();
                        currentAudio.currentTime = 0;
                        currentAudio = null;
                    }
                    hidePreStart();
                    startTrial();
                });

                btnHearSounds.addEventListener('click', function() {
                    playSoundsPreview();
                });

                // Set up event listeners — touch + click with double-fire guard
                testSections.forEach(section => {
                    section.addEventListener('touchstart', function(e) {
                        e.preventDefault();
                        touchHandled = true;
                        handleSectionClick({ currentTarget: this });
                        setTimeout(() => { touchHandled = false; }, 400);
                    }, { passive: false });

                    section.addEventListener('click', function(e) {
                        if (touchHandled) return;
                        handleSectionClick(e);
                    });
                });

                // Prevent page scrolling/bouncing during active testing
                document.body.addEventListener('touchmove', function(e) {
                    if (resultsScreen.style.display === 'block') return;
                    e.preventDefault();
                }, { passive: false });

                // Prevent context menu on long-press (image save dialog)
                document.addEventListener('contextmenu', function(e) {
                    if (e.target.closest('.test-area') || e.target.closest('.score-container')) {
                        e.preventDefault();
                    }
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
                showRestartButton(true);
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
                
                // Show test screen with pre-start buttons
                showScreen('test');
                showPreStart();
            }
            
            // Show pre-start buttons (Start + Hear the Sounds)
            function showPreStart() {
                // Show buttons area, hide score bar
                preStartArea.style.display = '';
                scoreBar.style.display = 'none';
                scoreContainer.classList.add('pre-start-mode');
                btnStart.disabled = false;
                btnHearSounds.disabled = false;
            }

            // Transition from pre-start to active test
            function hidePreStart() {
                preStartArea.style.display = 'none';
                scoreBar.style.display = '';
                scoreContainer.classList.remove('pre-start-mode');
            }

            // Play each sound in sequence: Left, Center, Right with 500ms gaps
            let previewPlaying = false;
            function playSoundsPreview() {
                if (!currentTest || !currentTest.sounds || previewPlaying) return;

                previewPlaying = true;
                btnHearSounds.disabled = true;
                btnStart.disabled = true;

                const sounds = [
                    currentTest.sounds.left,
                    currentTest.sounds.center,
                    currentTest.sounds.right
                ].filter(Boolean);

                let index = 0;

                function playNext() {
                    if (index >= sounds.length) {
                        previewPlaying = false;
                        btnHearSounds.disabled = false;
                        btnStart.disabled = false;
                        return;
                    }

                    const url = sounds[index];
                    index++;

                    // Stop any current audio
                    if (currentAudio) {
                        currentAudio.pause();
                        currentAudio.currentTime = 0;
                    }

                    let audio = preloadedAudio[url];
                    if (!audio) {
                        audio = new Audio(url);
                        audio.volume = 0.7;
                        audio.preload = 'auto';
                        preloadedAudio[url] = audio;
                    }
                    audio.currentTime = 0;
                    audio.volume = 0.7;
                    currentAudio = audio;

                    audio.play().then(() => {
                        // Wait for audio to end, then 500ms delay before next
                        audio.onended = function() {
                            setTimeout(playNext, 500);
                        };
                    }).catch(() => {
                        // If play fails, move to next after 500ms
                        setTimeout(playNext, 500);
                    });
                }

                playNext();
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
                
                // Check if feedback images are available
                const hasFeedbackImages = currentTest && 
                                        currentTest.feedback_images && 
                                        (currentTest.feedback_images.correct || currentTest.feedback_images.incorrect);
                
                // Set initial height based on whether we have feedback images
                if (!hasFeedbackImages) {
                    // For square indicators, calculate width of one grid cell to make it square
                    requestAnimationFrame(() => {
                        const scoreBarWidth = scoreBar.offsetWidth;
                        const indicatorWidth = Math.floor((scoreBarWidth - (15 * 1)) / 15); // 15 indicators with 1px gaps
                        const squareHeight = Math.max(38, indicatorWidth); // Minimum 38px or square dimension
                        
                        scoreBar.style.height = (squareHeight + 2) + 'px'; // +2 for border
                        
                        // Apply height to all indicators
                        const indicators = scoreBar.querySelectorAll('.score-indicator');
                        indicators.forEach(indicator => {
                            indicator.style.height = squareHeight + 'px';
                            indicator.style.minHeight = squareHeight + 'px';
                        });
                    });
                }
                
                for (let i = 0; i < total; i++) {
                    const indicator = document.createElement('div');
                    indicator.className = 'score-indicator';
                    
                    if (i < responses.length) {
                        const isCorrect = responses[i];
                        
                        if (hasFeedbackImages) {
                            // Use feedback images when available
                            const imageUrl = isCorrect ? currentTest.feedback_images.correct : currentTest.feedback_images.incorrect;
                            
                            if (imageUrl) {
                                // Create image element
                                const img = document.createElement('img');
                                img.src = imageUrl;
                                img.alt = isCorrect ? 'Correct' : 'Incorrect';
                                img.style.width = '100%';
                                img.style.height = 'auto';
                                img.style.maxHeight = '80px';
                                img.style.objectFit = 'contain';
                                img.style.borderRadius = '3px';
                                img.draggable = false;
                                img.style.webkitUserDrag = 'none';
                                img.style.webkitTouchCallout = 'none';
                                img.style.pointerEvents = 'none';
                                
                                // When image loads, adjust the score bar height
                                img.onload = function() {
                                    adjustScoreBarHeight();
                                };
                                
                                indicator.appendChild(img);
                            } else {
                                // Fallback to colored square if image URL is empty
                                indicator.classList.add(isCorrect ? 'correct' : 'incorrect', 'square');
                            }
                        } else {
                            // Use colored squares when no feedback images are defined
                            indicator.classList.add(isCorrect ? 'correct' : 'incorrect', 'square');
                        }
                    }
                    
                    scoreBar.appendChild(indicator);
                }
            }
            
            // Adjust score bar height to accommodate images
            function adjustScoreBarHeight() {
                const scoreBar = document.getElementById('scoreBar');
                const indicators = scoreBar.querySelectorAll('.score-indicator');
                let maxHeight = 38; // Minimum height for colored squares
                
                // Find the tallest indicator with an image
                indicators.forEach(indicator => {
                    const img = indicator.querySelector('img');
                    if (img && img.complete) {
                        const indicatorHeight = img.offsetHeight;
                        maxHeight = Math.max(maxHeight, indicatorHeight);
                    }
                });
                
                // Apply the max height to all indicators
                indicators.forEach(indicator => {
                    indicator.style.height = maxHeight + 'px';
                    indicator.style.minHeight = maxHeight + 'px';
                });
                
                // Update the score bar height
                scoreBar.style.height = (maxHeight + 2) + 'px'; // +2 for border
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
                showRestartButton(false);
                showScreen('results');

                // Calculate overall score
                const totalCorrect = testResultsData.reduce((sum, test) => sum + test.score.correct, 0);
                const totalTrials = testResultsData.reduce((sum, test) => sum + test.score.total, 0);
                const overallPercentage = Math.round((totalCorrect / totalTrials) * 100);
                const avgResponseTime = Math.round(
                    testResultsData.reduce((sum, test) => sum + test.score.avgResponseTime, 0) / testResultsData.length
                );

                const scoreColorClass = overallPercentage >= 80 ? 'score-high' : overallPercentage >= 50 ? 'score-medium' : 'score-low';

                // Generate results HTML
                let resultsHTML = `<div class="results-header">
                    <h2><i class="fa-solid fa-chart-column me-2"></i>Test Results</h2>
                    <div class="results-subtitle">${testResultsData.length} assessment${testResultsData.length !== 1 ? 's' : ''} completed</div>
                </div>`;

                testResultsData.forEach((test, index) => {
                    const pctClass = test.score.percentage >= 80 ? 'score-high' : test.score.percentage >= 50 ? 'score-medium' : 'score-low';
                    resultsHTML += `
                        <div class="test-result">
                            <div class="test-result-header">
                                <h4>${test.name}</h4>
                                <span class="${pctClass}" style="font-weight:700;font-size:0.95rem;">${test.score.percentage}%</span>
                            </div>
                            <div class="test-result-body">
                                <div class="result-stats">
                                    <div class="result-stat">
                                        <span class="result-stat-label">Score</span>
                                        <span class="result-stat-value">${test.score.correct}<span style="color:var(--text-muted);font-weight:400;font-size:0.85rem;">/${test.score.total}</span></span>
                                    </div>
                                    <div class="result-stat">
                                        <span class="result-stat-label">Avg Response</span>
                                        <span class="result-stat-value">${test.score.avgResponseTime}<span style="color:var(--text-muted);font-weight:400;font-size:0.85rem;"> ms</span></span>
                                    </div>
                                </div>
                                <div class="result-score-bar">
                                    ${Array(test.entries).fill().map((_, i) => {
                                        const response = test.responses[i];
                                        if (!response) return `<div class="result-score-dot empty">${i+1}</div>`;
                                        return `<div class="result-score-dot ${response.isCorrect ? 'correct' : 'incorrect'}" title="Trial ${i+1}: ${response.isCorrect ? 'Correct' : 'Incorrect'} — ${response.responseTime || 0}ms">
                                            ${response.isCorrect ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-xmark"></i>'}
                                        </div>`;
                                    }).join('')}
                                </div>
                            </div>
                        </div>`;
                });

                const summaryHTML = `
                    <div class="summary">
                        <h3><i class="fa-solid fa-clipboard-check me-2"></i>Summary</h3>
                        <div class="summary-stats">
                            <div class="summary-stat">
                                <div class="summary-stat-value ${scoreColorClass}">${overallPercentage}%</div>
                                <div class="summary-stat-label">Overall Score</div>
                            </div>
                            <div class="summary-stat">
                                <div class="summary-stat-value">${totalCorrect}<span style="color:var(--text-muted);font-weight:400;font-size:1rem;">/${totalTrials}</span></div>
                                <div class="summary-stat-label">Correct</div>
                            </div>
                            <div class="summary-stat">
                                <div class="summary-stat-value">${avgResponseTime}<span style="color:var(--text-muted);font-weight:400;font-size:1rem;"> ms</span></div>
                                <div class="summary-stat-label">Avg Response</div>
                            </div>
                        </div>
                        <div id="saveStatus"></div>
                        <div class="summary-actions">
                            <button id="restartButton" class="btn"><i class="fa-solid fa-rotate-right me-1"></i> Restart</button>
                            <button id="homeButton" class="btn-outline"><i class="fa-solid fa-house me-1"></i> Home</button>
                        </div>
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

                        // Mark assessment(s) as completed for today
                        const params = new URLSearchParams(window.location.search);
                        const assessmentIds = (params.get('assessments') || '').split(',').filter(Boolean);
                        const completed = JSON.parse(sessionStorage.getItem('completedAssessments') || '{}');
                        const today = new Date().toISOString().slice(0, 10);
                        assessmentIds.forEach(function(id) { completed[id] = today; });
                        sessionStorage.setItem('completedAssessments', JSON.stringify(completed));
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
                    // Filter out the last zone if it's been repeated 2 times
                    const availableZones = repeatCount >= 2
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
