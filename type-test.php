<?php
require_once 'header.php';
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <link rel="stylesheet" href="assets/css/type-test.css">

    <div class="container student-corner-container">
        <div class="student-corner-card type-test-card">
            <h2 class="student-corner-title">Type Test</h2>

            <div class="type-test-mobile-message">
                <p>This test is available on computer only.</p>
            </div>

            <p class="type-test-intro">Choose a mode, then click below and start typing. Test begins on your first keypress.</p>

            <main class="type-test-app app">
                <section class="type-test-modes">
                    <div class="type-test-mode-group">
                        <span class="type-test-mode-label">Time</span>
                        <div class="type-test-mode-options" data-mode="time">
                            <button type="button" class="type-test-mode-btn active" data-value="15">15</button>
                            <button type="button" class="type-test-mode-btn" data-value="30">30</button>
                            <button type="button" class="type-test-mode-btn" data-value="60">60</button>
                        </div>
                    </div>
                    <div class="type-test-mode-group">
                        <span class="type-test-mode-label">Words</span>
                        <div class="type-test-mode-options" data-mode="words">
                            <button type="button" class="type-test-mode-btn" data-value="10">10</button>
                            <button type="button" class="type-test-mode-btn" data-value="25">25</button>
                            <button type="button" class="type-test-mode-btn" data-value="50">50</button>
                        </div>
                    </div>
                </section>

                <section class="type-test-stats">
                    <div class="type-test-stat">
                        <div class="type-test-stat-label">WPM</div>
                        <div class="type-test-stat-value" id="wpm">0</div>
                    </div>
                    <div class="type-test-stat">
                        <div class="type-test-stat-label">Accuracy</div>
                        <div class="type-test-stat-value" id="accuracy">100%</div>
                    </div>
                    <div class="type-test-stat type-test-timer-stat" id="timerWrap">
                        <div class="type-test-stat-label" id="timerLabel">Time</div>
                        <div class="type-test-stat-value" id="timer">0:30</div>
                    </div>
                    <div class="type-test-stat type-test-progress-stat hidden" id="progressWrap">
                        <div class="type-test-stat-label">Words</div>
                        <div class="type-test-stat-value" id="progress">0/30</div>
                    </div>
                </section>

                <section class="type-test-panel">
                    <div class="type-test-focus-hint" id="focusHint">Click or press any key to focus and start</div>
                    <div class="type-test-words" id="words" aria-live="polite"></div>
                    <input
                        id="input"
                        class="type-test-input"
                        type="text"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        placeholder=" "
                        tabindex="0"
                    />
                </section>

                <section class="type-test-result" id="resultPanel">
                    <div class="type-test-result-title">Test complete</div>
                    <div class="type-test-result-stats">
                        <div class="type-test-result-stat"><span class="type-test-result-value" id="resultWpm">0</span> wpm</div>
                        <div class="type-test-result-stat"><span class="type-test-result-value" id="resultAcc">100</span>% accuracy</div>
                    </div>
                    <button type="button" class="type-test-button" id="restart">Restart</button>
                </section>

                <section class="type-test-actions" id="actionsBar">
                    <button type="button" class="type-test-button type-test-restart-btn" id="restartBtn">Restart</button>
                    <div class="type-test-hint">Space or Enter to submit word · Esc to restart</div>
                </section>
            </main>
        </div>
    </div>
</div>

<script src="type_test/app.js" defer></script>
<?php require_once 'footer.php'; ?>
