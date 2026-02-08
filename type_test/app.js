(function () {
  const WORD_BANK = [
    "about", "above", "actor", "agree", "allow", "among", "answer", "apple", "arrive",
    "basic", "become", "before", "bring", "build", "buyer", "cable", "camera", "catch",
    "change", "clean", "climb", "coffee", "color", "dance", "decide", "dream", "drive",
    "early", "earth", "email", "empty", "enter", "event", "faith", "field", "focus",
    "fresh", "fruit", "giant", "globe", "grace", "happy", "heart", "human", "image",
    "issue", "judge", "knife", "later", "learn", "light", "match", "metal", "model",
    "movie", "music", "north", "offer", "paper", "peace", "plant", "power", "quiet",
    "radio", "reach", "river", "scale", "score", "sharp", "smile", "sound", "south",
    "space", "sport", "stand", "table", "teach", "thank", "title", "today", "union",
    "value", "visit", "voice", "water", "woman", "world", "write", "yield", "young"
  ];

  const MIN_WORDS_FOR_TIME = 40;
  const EXTRA_WORDS_POOL = 30;

  const wordsEl = document.getElementById("words");
  const inputEl = document.getElementById("input");
  const wpmEl = document.getElementById("wpm");
  const accuracyEl = document.getElementById("accuracy");
  const progressEl = document.getElementById("progress");
  const timerEl = document.getElementById("timer");
  const timerLabel = document.getElementById("timerLabel");
  const timerWrap = document.getElementById("timerWrap");
  const progressWrap = document.getElementById("progressWrap");
  const restartEl = document.getElementById("restart");
  const restartBtn = document.getElementById("restartBtn");
  const appEl = document.querySelector(".app");
  const resultPanel = document.getElementById("resultPanel");
  const resultWpm = document.getElementById("resultWpm");
  const resultAcc = document.getElementById("resultAcc");
  const focusHint = document.getElementById("focusHint");
  const actionsBar = document.getElementById("actionsBar");

  let words = [];
  let currentWordIndex = 0;
  let startedAt = null;
  let completedTypedChars = 0;
  let completedCorrectChars = 0;
  let finished = false;
  let timerInterval = null;
  let testMode = "time";
  let testValue = 30;
  let testDurationSec = 30;

  function pickWords(n) {
    const pool = [...WORD_BANK];
    const chosen = [];
    for (let i = 0; i < n; i++) {
      chosen.push(pool[Math.floor(Math.random() * pool.length)]);
    }
    return chosen;
  }

  function addMoreWords() {
    const extra = pickWords(EXTRA_WORDS_POOL);
    words.push(...extra);
    appendWordsToDOM(extra, words.length - extra.length);
  }

  function getModeFromUI() {
    const timeActive = document.querySelector('.type-test-mode-options[data-mode="time"] .type-test-mode-btn.active');
    const wordsActive = document.querySelector('.type-test-mode-options[data-mode="words"] .type-test-mode-btn.active');
    if (wordsActive) {
      return { mode: "words", value: parseInt(wordsActive.dataset.value, 10) };
    }
    if (timeActive) {
      return { mode: "time", value: parseInt(timeActive.dataset.value, 10) };
    }
    return { mode: "time", value: 30 };
  }

  function countCorrectChars(target, input) {
    let correct = 0;
    for (let i = 0; i < Math.min(target.length, input.length); i++) {
      if (target[i] === input[i]) correct++;
    }
    return correct;
  }

  function buildWordsDOM(wordList, startIndex) {
    wordList.forEach((word, i) => {
      const wordSpan = document.createElement("span");
      wordSpan.className = "word" + (i === 0 ? " current" : "");
      word.split("").forEach((char) => {
        const charSpan = document.createElement("span");
        charSpan.className = "char";
        charSpan.textContent = char;
        wordSpan.appendChild(charSpan);
      });
      wordsEl.appendChild(wordSpan);
    });
  }

  function appendWordsToDOM(wordList, startIndex) {
    wordList.forEach((word) => {
      const wordSpan = document.createElement("span");
      wordSpan.className = "word";
      word.split("").forEach((char) => {
        const charSpan = document.createElement("span");
        charSpan.className = "char";
        charSpan.textContent = char;
        wordSpan.appendChild(charSpan);
      });
      wordsEl.appendChild(wordSpan);
    });
  }

  function buildWords() {
    wordsEl.innerHTML = "";
    const n = testMode === "time" ? Math.max(MIN_WORDS_FOR_TIME, testValue * 2) : testValue;
    words = pickWords(n);
    buildWordsDOM(words, 0);
  }

  function updateProgress() {
    if (testMode === "words") {
      progressEl.textContent = `${Math.min(currentWordIndex, words.length)}/${words.length}`;
    }
  }

  function getLiveStats() {
    if (!startedAt) return { wpm: 0, accuracy: 100 };
    const elapsedMinutes = (Date.now() - startedAt) / 60000;
    const liveInput = inputEl.value;
    const currentTarget = words[currentWordIndex] || "";
    const liveCorrect = countCorrectChars(currentTarget, liveInput);
    const totalCorrect = completedCorrectChars + liveCorrect;
    const totalTyped = completedTypedChars + liveInput.length;
    const wpm = elapsedMinutes > 0 ? Math.round((totalCorrect / 5) / elapsedMinutes) : 0;
    const accuracy = totalTyped > 0 ? Math.round((totalCorrect / totalTyped) * 100) : 100;
    return { wpm: Number.isFinite(wpm) ? wpm : 0, accuracy };
  }

  function updateStats() {
    const { wpm, accuracy } = getLiveStats();
    wpmEl.textContent = wpm;
    accuracyEl.textContent = accuracy + "%";
    updateProgress();
  }

  function updateTimer() {
    if (testMode !== "time" || !startedAt) return;
    const elapsed = Math.floor((Date.now() - startedAt) / 1000);
    const left = Math.max(0, testDurationSec - elapsed);
    const m = Math.floor(left / 60);
    const s = left % 60;
    timerEl.textContent = m + ":" + (s < 10 ? "0" : "") + s;
    if (left <= 0) {
      clearInterval(timerInterval);
      timerInterval = null;
      finishTest();
    }
  }

  function updateCurrentWordDisplay() {
    const wordSpans = wordsEl.querySelectorAll(".word");
    wordSpans.forEach((span, index) => span.classList.toggle("current", index === currentWordIndex));
    const currentWordSpan = wordSpans[currentWordIndex];
    if (!currentWordSpan) return;
    const inputValue = inputEl.value;
    const charSpans = currentWordSpan.querySelectorAll(".char");
    charSpans.forEach((charSpan, index) => {
      charSpan.classList.remove("correct", "incorrect");
      if (index < inputValue.length) {
        charSpan.classList.add(inputValue[index] === charSpan.textContent ? "correct" : "incorrect");
      }
    });
  }

  function finishTest() {
    if (finished) return;
    finished = true;
    if (timerInterval) {
      clearInterval(timerInterval);
      timerInterval = null;
    }
    inputEl.disabled = true;
    inputEl.blur();
    const { wpm, accuracy } = getLiveStats();
    resultWpm.textContent = wpm;
    resultAcc.textContent = accuracy;
    resultPanel.classList.add("visible");
    actionsBar.classList.add("hidden");
    appEl.classList.add("done");
  }

  function submitWord() {
    if (finished) return;
    const inputValue = inputEl.value.trim();
    const target = words[currentWordIndex];
    const correctInWord = countCorrectChars(target, inputValue);
    completedTypedChars += inputValue.length;
    completedCorrectChars += correctInWord;
    currentWordIndex++;
    inputEl.value = "";
    updateCurrentWordDisplay();
    updateStats();

    if (testMode === "time") {
      if (currentWordIndex >= words.length - 2) addMoreWords();
    } else {
      if (currentWordIndex >= words.length) finishTest();
    }
  }

  function startTestIfNeeded() {
    if (startedAt) return;
    const cfg = getModeFromUI();
    testMode = cfg.mode;
    testValue = cfg.value;
    testDurationSec = testMode === "time" ? testValue : 0;
    startedAt = Date.now();
    focusHint.classList.add("hidden");
    if (testMode === "time") {
      timerWrap.classList.remove("hidden");
      progressWrap.classList.add("hidden");
      timerInterval = setInterval(updateTimer, 100);
    } else {
      timerWrap.classList.add("hidden");
      progressWrap.classList.remove("hidden");
    }
    updateTimer();
    updateStats();
  }

  function resetTest() {
    if (timerInterval) {
      clearInterval(timerInterval);
      timerInterval = null;
    }
    const cfg = getModeFromUI();
    testMode = cfg.mode;
    testValue = cfg.value;
    testDurationSec = testMode === "time" ? testValue : 0;
    words = [];
    currentWordIndex = 0;
    startedAt = null;
    completedTypedChars = 0;
    completedCorrectChars = 0;
    finished = false;
    appEl.classList.remove("done");
    resultPanel.classList.remove("visible");
    actionsBar.classList.remove("hidden");
    inputEl.disabled = false;
    inputEl.value = "";
    focusHint.classList.remove("hidden");
    timerWrap.classList.remove("hidden");
    progressWrap.classList.remove("hidden");
    if (testMode === "time") {
      timerEl.textContent = (Math.floor(testDurationSec / 60)) + ":" + (testDurationSec % 60 < 10 ? "0" : "") + (testDurationSec % 60);
      progressWrap.classList.add("hidden");
    } else {
      timerWrap.classList.add("hidden");
      progressEl.textContent = "0/" + testValue;
    }
    buildWords();
    updateStats();
    inputEl.focus();
  }

  function setMode(mode, value) {
    document.querySelectorAll(".type-test-mode-btn").forEach((btn) => btn.classList.remove("active"));
    const selector = '.type-test-mode-options[data-mode="' + mode + '"] .type-test-mode-btn[data-value="' + value + '"]';
    const btn = document.querySelector(selector);
    if (btn) btn.classList.add("active");
  }

  inputEl.addEventListener("input", function () {
    startTestIfNeeded();
    updateCurrentWordDisplay();
    updateStats();
  });

  inputEl.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      e.preventDefault();
      if (startedAt && !finished) resetTest();
      return;
    }
    if (e.key === " " || e.key === "Enter") {
      e.preventDefault();
      if (inputEl.value.length > 0) submitWord();
    }
  });

  inputEl.addEventListener("focus", function () {
    focusHint.classList.add("hidden");
  });

  inputEl.addEventListener("blur", function () {
    if (!startedAt) focusHint.classList.remove("hidden");
  });

  restartEl.addEventListener("click", resetTest);
  restartBtn.addEventListener("click", resetTest);

  document.querySelectorAll(".type-test-mode-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (startedAt && !finished) return;
      const mode = this.closest(".type-test-mode-options").dataset.mode;
      const value = parseInt(this.dataset.value, 10);
      setMode(mode, value);
      resetTest();
    });
  });

  document.querySelector(".type-test-panel").addEventListener("click", function () {
    inputEl.focus();
  });

  setMode("time", 30);
  resetTest();
})();
