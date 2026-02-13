<?php
require_once 'header.php';
?>
<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <style>
        .srd-container { padding: 40px 20px; max-width: 900px; margin: 0 auto; }
        .srd-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .srd-title { color: #333; margin-bottom: 8px; padding-left: 15px; border-left: 5px solid #1a2a6c; font-size: 24px; font-weight: 600; }
        .srd-subtitle { color: #555; font-size: 15px; margin-bottom: 28px; }
        .srd-section { margin-bottom: 28px; }
        .srd-section h2 { font-size: 18px; color: #1a2a6c; margin: 0 0 10px; font-weight: 600; }
        .srd-section h3 { font-size: 15px; color: #333; margin: 14px 0 6px; font-weight: 600; }
        .srd-section p, .srd-section li { font-size: 14px; color: #444; line-height: 1.6; margin: 0 0 8px; }
        .srd-section ul { margin: 6px 0 12px; padding-left: 24px; }
        .srd-section code, .srd-section pre { background: #f0f2f5; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        .srd-section pre { padding: 12px; overflow-x: auto; white-space: pre-wrap; }
    </style>
    <div class="container srd-container">
        <div class="srd-card">
            <h1 class="srd-title">📄 Software Requirement Document</h1>
            <p class="srd-subtitle">Project Title: Quiz Racing Game (Desktop Browser Based)</p>

            <div class="srd-section">
                <h2>1. Project Overview</h2>
                <p>The Quiz Racing Game is an educational browser-based interactive game designed for desktop users.</p>
                <p>The player controls a car on a 4-lane road. The goal is to select (touch) only the correct options based on a given question (e.g., "Touch all Software Components").</p>
                <p>The game combines:</p>
                <ul>
                    <li>Education</li>
                    <li>Reflex-based interaction</li>
                    <li>Visual engagement</li>
                </ul>
                <p>The application must run in modern desktop browsers (Chrome, Edge, Firefox).</p>
            </div>

            <div class="srd-section">
                <h2>2. Target Platform</h2>
                <ul>
                    <li>Desktop Web Browser</li>
                    <li>Responsive for minimum screen width: 1024px</li>
                    <li>No mobile support required (optional enhancement)</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>3. Technology Stack (Preferred)</h2>
                <h3>Frontend:</h3>
                <ul>
                    <li>HTML5</li>
                    <li>CSS3</li>
                    <li>JavaScript (ES6+)</li>
                    <li>Canvas API or Phaser.js (recommended)</li>
                </ul>
                <h3>Backend (Optional but recommended):</h3>
                <ul>
                    <li>PHP</li>
                    <li>MySQL (for storing scores and leaderboard)</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>4. Game Flow</h2>
                <h3>4.1 Start Screen</h3>
                <p><strong>Features:</strong></p>
                <ul>
                    <li>Input field: Student Name (Required)</li>
                    <li>Question selection (Dropdown OR random selection)</li>
                    <li>Start Game Button</li>
                </ul>
                <p><strong>Validation:</strong> Student name cannot be empty.</p>
                <h3>4.2 Game Screen</h3>
                <p><strong>Layout:</strong></p>
                <ul>
                    <li>Vertical 4-lane road</li>
                    <li>Car positioned at bottom center</li>
                    <li>Student name displayed on flag above car</li>
                    <li>Score display at top</li>
                    <li>Timer display at top</li>
                    <li>Current question displayed at top</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>5. Core Game Mechanics</h2>
                <h3>5.1 Car Control</h3>
                <ul>
                    <li>Arrow Left → Move to left lane</li>
                    <li>Arrow Right → Move to right lane</li>
                    <li>Movement restricted to 4 fixed lanes</li>
                    <li>Smooth animation between lanes</li>
                </ul>
                <h3>5.2 Question Logic</h3>
                <p>At the start of each game, example questions:</p>
                <ul>
                    <li>Touch all Software Components</li>
                    <li>Touch all Hardware Components</li>
                    <li>Touch all Input Devices</li>
                    <li>Touch all Output Devices</li>
                </ul>
                <p>The question remains visible during gameplay.</p>
                <h3>5.3 Object Spawning</h3>
                <ul>
                    <li>Objects appear at top of screen</li>
                    <li>Move downward toward the player</li>
                    <li>Spawn every 1.5–2 seconds</li>
                    <li>Each object randomly assigned: Lane (1–4), Label text, Category type</li>
                </ul>
                <p>Example object structure:</p>
                <pre>{
  label: "Keyboard",
  category: "hardware"
}</pre>
                <h3>5.4 Collision Detection</h3>
                <p>When car lane == object lane and object vertical position reaches car level:</p>
                <ul>
                    <li>If object category == question category: Increase score (+10), Play correct sound, Visual effect (green flash)</li>
                    <li>Else: Decrease score (-5) or reduce life, Red flash effect</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>6. Game Rules</h2>
                <ul>
                    <li>Game duration: 60 seconds (configurable) OR 3 lives system (configurable)</li>
                    <li>Game ends when: Timer reaches zero OR Lives become zero</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>7. Scoring System</h2>
                <ul>
                    <li>Correct Answer: +10</li>
                    <li>Wrong Answer: -5</li>
                </ul>
                <p>Final Score displayed on Game Over screen.</p>
            </div>

            <div class="srd-section">
                <h2>8. End Screen</h2>
                <p><strong>Display:</strong></p>
                <ul>
                    <li>Student Name</li>
                    <li>Final Score</li>
                    <li>Time survived</li>
                    <li>Restart Button</li>
                    <li>Save Score Button (if backend enabled)</li>
                </ul>
                <p>Optional: Leaderboard button.</p>
            </div>

            <div class="srd-section">
                <h2>9. Visual Requirements</h2>
                <ul>
                    <li>2D top-down racing style</li>
                    <li>Smooth animation</li>
                    <li>Clean educational theme</li>
                    <li>Bright but not distracting colors</li>
                    <li>Car must have: Small flag above it, Student name written on flag</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>10. Audio Requirements</h2>
                <p>Optional but recommended:</p>
                <ul>
                    <li>Background music (loop)</li>
                    <li>Correct answer sound</li>
                    <li>Wrong answer sound</li>
                    <li>Game over sound</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>11. Performance Requirements</h2>
                <ul>
                    <li>Must run at minimum 60 FPS</li>
                    <li>No lag during object spawning</li>
                    <li>Efficient collision detection</li>
                </ul>
            </div>

            <div class="srd-section">
                <h2>12. Data Storage (Optional Backend)</h2>
                <p>If backend implemented:</p>
                <p><strong>Database Table: scores</strong></p>
                <p>Fields: id (INT, PK), student_name (VARCHAR), score (INT), date_time (DATETIME)</p>
                <p><strong>Features:</strong></p>
                <ul>
                    <li>Save score after game</li>
                    <li>Display Top 10 leaderboard</li>
                    <li>Sort by highest score</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require_once 'footer.php'; ?>
