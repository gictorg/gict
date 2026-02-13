<?php
require_once 'header.php';
?>
<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <link rel="stylesheet" href="assets/css/quiz-racing.css">

    <div class="container student-corner-container">
        <div class="student-corner-card quiz-racing-card">
            <h2 class="student-corner-title">O Level Practice — Quiz Racing Game</h2>
            <p class="quiz-racing-olevel-badge">Based on NIELIT O Level / Computer Fundamentals syllabus</p>

            <div class="quiz-racing-mobile-msg">
                <p>This game is best played on a desktop (min width 1024px).</p>
            </div>

            <!-- Start Screen -->
            <div id="quizRacingStart" class="quiz-racing-screen">
                <p class="quiz-racing-intro">Practice O Level topics: select a topic and use <strong>Arrow Left / Right</strong> to move the car. Collect only the items that match the question—avoid wrong ones to maximise your score.</p>
                <div class="quiz-racing-max-marks">
                    <strong>Scoring:</strong> +10 per correct, −5 per wrong &nbsp;|&nbsp; <strong>Max approachable: 300+</strong> in 60 seconds &nbsp;|&nbsp; <span class="quiz-racing-target">Aim for 200+ to do well!</span>
                </div>
                <div class="quiz-racing-form">
                    <div class="quiz-racing-field">
                        <label for="quizQuestion">Select Topic (O Level)</label>
                        <select id="quizQuestion">
                            <optgroup label="Hardware &amp; Devices">
                                <option value="input">Input Devices</option>
                                <option value="output">Output Devices</option>
                                <option value="storage">Storage Devices</option>
                                <option value="hardware">Hardware Components</option>
                            </optgroup>
                            <optgroup label="Software">
                                <option value="system_software">System Software</option>
                                <option value="application_software">Application Software</option>
                            </optgroup>
                            <optgroup label="Memory &amp; Internet">
                                <option value="memory_units">Units of Memory</option>
                                <option value="internet">Internet &amp; Web Terms</option>
                            </optgroup>
                            <optgroup label="Number Systems &amp; Logic">
                                <option value="number_systems">Number Systems</option>
                                <option value="logic_gates">Logic Gates</option>
                            </optgroup>
                            <optgroup label="Programming &amp; Data">
                                <option value="programming">Programming Terms</option>
                                <option value="database">Database Terms</option>
                            </optgroup>
                            <optgroup label="Network &amp; Security">
                                <option value="network">Network Terms</option>
                                <option value="security">Security Terms</option>
                            </optgroup>
                            <option value="random">Random (one topic chosen per test)</option>
                        </select>
                    </div>
                    <button type="button" id="quizStartBtn" class="quiz-racing-btn">Start Test</button>
                </div>
            </div>

            <!-- Game Screen -->
            <div id="quizRacingGame" class="quiz-racing-screen hidden">
                <div class="quiz-racing-hud">
                    <span class="quiz-racing-hud-item">Marks: <strong id="quizScore">0</strong> <span class="quiz-racing-hud-max">/ 300+ max</span></span>
                    <span class="quiz-racing-hud-item">Time: <strong id="quizTimer">60</strong>s</span>
                    <span class="quiz-racing-hud-item quiz-racing-question-text" id="quizQuestionText">Select topic to start</span>
                    <button type="button" id="quizGameResetBtn" class="quiz-racing-btn quiz-racing-btn-reset">Reset</button>
                </div>
                <div class="quiz-racing-canvas-wrap">
                    <canvas id="quizRacingCanvas" width="600" height="480"></canvas>
                </div>
            </div>

            <!-- End Screen -->
            <div id="quizRacingEnd" class="quiz-racing-screen hidden">
                <h3 class="quiz-racing-end-title">Test Over</h3>
                <p class="quiz-racing-end-stat">Marks: <strong id="quizEndScore">0</strong></p>
                <p class="quiz-racing-end-stat">Time: <strong id="quizEndTime">0</strong>s</p>
                <button type="button" id="quizRestartBtn" class="quiz-racing-btn">Try Again</button>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/quiz-racing.js" defer></script>
<?php require_once 'footer.php'; ?>
