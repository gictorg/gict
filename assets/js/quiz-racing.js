(function () {
  'use strict';

  // O Level / NIELIT syllabus-aligned categories and terms
  var OBJECTS_BY_CATEGORY = {
    input: [
      { label: 'Keyboard', category: 'input' },
      { label: 'Mouse', category: 'input' },
      { label: 'Scanner', category: 'input' },
      { label: 'Microphone', category: 'input' },
      { label: 'Webcam', category: 'input' },
      { label: 'Touchscreen', category: 'input' },
      { label: 'Joystick', category: 'input' },
      { label: 'Light Pen', category: 'input' }
    ],
    output: [
      { label: 'Monitor', category: 'output' },
      { label: 'Printer', category: 'output' },
      { label: 'Speaker', category: 'output' },
      { label: 'Headphones', category: 'output' },
      { label: 'Projector', category: 'output' },
      { label: 'Plotter', category: 'output' }
    ],
    storage: [
      { label: 'HDD', category: 'storage' },
      { label: 'SSD', category: 'storage' },
      { label: 'Pen Drive', category: 'storage' },
      { label: 'CD', category: 'storage' },
      { label: 'DVD', category: 'storage' },
      { label: 'RAM', category: 'storage' },
      { label: 'ROM', category: 'storage' },
      { label: 'Flash Drive', category: 'storage' }
    ],
    hardware: [
      { label: 'CPU', category: 'hardware' },
      { label: 'ALU', category: 'hardware' },
      { label: 'CU', category: 'hardware' },
      { label: 'Motherboard', category: 'hardware' },
      { label: 'Port', category: 'hardware' },
      { label: 'Bus', category: 'hardware' },
      { label: 'Chipset', category: 'hardware' },
      { label: 'Cache', category: 'hardware' }
    ],
    system_software: [
      { label: 'OS', category: 'system_software' },
      { label: 'Compiler', category: 'system_software' },
      { label: 'Loader', category: 'system_software' },
      { label: 'Device Driver', category: 'system_software' },
      { label: 'Assembler', category: 'system_software' },
      { label: 'Utility', category: 'system_software' }
    ],
    application_software: [
      { label: 'Word', category: 'application_software' },
      { label: 'Excel', category: 'application_software' },
      { label: 'Browser', category: 'application_software' },
      { label: 'PowerPoint', category: 'application_software' },
      { label: 'Paint', category: 'application_software' },
      { label: 'PDF Reader', category: 'application_software' }
    ],
    memory_units: [
      { label: 'Bit', category: 'memory_units' },
      { label: 'Byte', category: 'memory_units' },
      { label: 'KB', category: 'memory_units' },
      { label: 'MB', category: 'memory_units' },
      { label: 'GB', category: 'memory_units' },
      { label: 'TB', category: 'memory_units' }
    ],
    internet: [
      { label: 'URL', category: 'internet' },
      { label: 'HTTP', category: 'internet' },
      { label: 'HTML', category: 'internet' },
      { label: 'Browser', category: 'internet' },
      { label: 'DNS', category: 'internet' },
      { label: 'IP', category: 'internet' },
      { label: 'Email', category: 'internet' }
    ],
    number_systems: [
      { label: 'Binary', category: 'number_systems' },
      { label: 'Decimal', category: 'number_systems' },
      { label: 'Octal', category: 'number_systems' },
      { label: 'Hexadecimal', category: 'number_systems' }
    ],
    programming: [
      { label: 'Variable', category: 'programming' },
      { label: 'Loop', category: 'programming' },
      { label: 'Algorithm', category: 'programming' },
      { label: 'Function', category: 'programming' },
      { label: 'Array', category: 'programming' },
      { label: 'String', category: 'programming' },
      { label: 'Condition', category: 'programming' }
    ],
    network: [
      { label: 'LAN', category: 'network' },
      { label: 'WAN', category: 'network' },
      { label: 'Router', category: 'network' },
      { label: 'Modem', category: 'network' },
      { label: 'Ethernet', category: 'network' },
      { label: 'WiFi', category: 'network' },
      { label: 'Switch', category: 'network' },
      { label: 'Hub', category: 'network' }
    ],
    logic_gates: [
      { label: 'AND', category: 'logic_gates' },
      { label: 'OR', category: 'logic_gates' },
      { label: 'NOT', category: 'logic_gates' },
      { label: 'NAND', category: 'logic_gates' },
      { label: 'NOR', category: 'logic_gates' },
      { label: 'XOR', category: 'logic_gates' }
    ],
    database: [
      { label: 'Table', category: 'database' },
      { label: 'Query', category: 'database' },
      { label: 'SQL', category: 'database' },
      { label: 'Record', category: 'database' },
      { label: 'Field', category: 'database' },
      { label: 'Primary Key', category: 'database' }
    ],
    security: [
      { label: 'Password', category: 'security' },
      { label: 'Firewall', category: 'security' },
      { label: 'Encryption', category: 'security' },
      { label: 'Virus', category: 'security' },
      { label: 'Backup', category: 'security' },
      { label: 'Antivirus', category: 'security' }
    ]
  };

  var QUESTION_LABELS = {
    input: 'Touch all Input Devices',
    output: 'Touch all Output Devices',
    storage: 'Touch all Storage Devices',
    hardware: 'Touch all Hardware Components',
    system_software: 'Touch all System Software',
    application_software: 'Touch all Application Software',
    memory_units: 'Touch all Units of Memory',
    internet: 'Touch all Internet & Web Terms',
    number_systems: 'Touch all Number Systems',
    programming: 'Touch all Programming Terms',
    network: 'Touch all Network Terms',
    logic_gates: 'Touch all Logic Gates',
    database: 'Touch all Database Terms',
    security: 'Touch all Security Terms'
  };

  var ALL_CATEGORIES = ['input', 'output', 'storage', 'hardware', 'system_software', 'application_software', 'memory_units', 'internet', 'number_systems', 'programming', 'network', 'logic_gates', 'database', 'security'];

  var LANES = 4;
  var GAME_DURATION_SEC = 60;
  var SPAWN_MIN = 1500;
  var SPAWN_MAX = 2200;
  var OBJECT_SPEED = 2.5;
  var CORRECT_SCORE = 10;
  var WRONG_SCORE = -5;
  var CAR_WIDTH_RATIO = 0.22;
  var CAR_HEIGHT_RATIO = 0.12;
  var FLAG_HEIGHT_RATIO = 0.06;
  var OBJECT_HEIGHT_RATIO = 0.07;

  var allObjects = [];
  function getAllObjects() {
    if (allObjects.length) return allObjects;
    ALL_CATEGORIES.forEach(function (cat) {
      if (OBJECTS_BY_CATEGORY[cat]) {
        OBJECTS_BY_CATEGORY[cat].forEach(function (o) { allObjects.push(o); });
      }
    });
    return allObjects;
  }

  var canvas = document.getElementById('quizRacingCanvas');
  if (!canvas) return;

  var ctx = canvas.getContext('2d');
  var cw = canvas.width;
  var ch = canvas.height;
  var laneW = cw / LANES;

  var state = {
    screen: 'start',
    questionCategory: 'input',
    score: 0,
    timeLeft: GAME_DURATION_SEC,
    carLane: 1,
    carX: 0,
    falling: [],
    sparks: [],
    lastSpawnAt: 0,
    lastFrameAt: 0,
    gameOver: false,
    survivedSec: 0,
    animId: null
  };

  var startScreen = document.getElementById('quizRacingStart');
  var gameScreen = document.getElementById('quizRacingGame');
  var endScreen = document.getElementById('quizRacingEnd');
  var questionSelect = document.getElementById('quizQuestion');
  var startBtn = document.getElementById('quizStartBtn');
  var scoreEl = document.getElementById('quizScore');
  var timerEl = document.getElementById('quizTimer');
  var questionTextEl = document.getElementById('quizQuestionText');
  var endScoreEl = document.getElementById('quizEndScore');
  var endTimeEl = document.getElementById('quizEndTime');
  var restartBtn = document.getElementById('quizRestartBtn');

  function showScreen(name) {
    state.screen = name;
    startScreen.classList.toggle('hidden', name !== 'start');
    gameScreen.classList.toggle('hidden', name !== 'game');
    endScreen.classList.toggle('hidden', name !== 'end');
  }

  function carXForLane(lane) {
    return lane * laneW + (laneW / 2) - (laneW * CAR_WIDTH_RATIO) / 2;
  }

  function spawnObject() {
    var obj;
    var correctPool = OBJECTS_BY_CATEGORY[state.questionCategory];
    if (correctPool && correctPool.length && Math.random() < 0.7) {
      obj = correctPool[Math.floor(Math.random() * correctPool.length)];
    } else {
      var pool = getAllObjects();
      obj = pool[Math.floor(Math.random() * pool.length)];
    }
    var lane = Math.floor(Math.random() * LANES);
    state.falling.push({
      label: obj.label,
      category: obj.category,
      lane: lane,
      y: -25,
      w: laneW * 0.85,
      h: ch * OBJECT_HEIGHT_RATIO
    });
  }

  function drawRoad() {
    ctx.fillStyle = '#1e3a5f';
    ctx.fillRect(0, 0, cw, ch);
    ctx.strokeStyle = 'rgba(255,255,255,0.4)';
    ctx.lineWidth = 2;
    for (var i = 1; i < LANES; i++) {
      ctx.beginPath();
      ctx.moveTo(i * laneW, 0);
      ctx.lineTo(i * laneW, ch);
      ctx.stroke();
    }
  }

  function drawCar() {
    var carW = laneW * CAR_WIDTH_RATIO;
    var carH = ch * CAR_HEIGHT_RATIO;
    var x = state.carX;
    var y = ch - carH - 20;

    // Car body (top-down view: main body)
    var bodyY = y;
    var bodyH = carH - 6;
    ctx.fillStyle = '#c5a059';
    ctx.strokeStyle = '#1a2a6c';
    ctx.lineWidth = 2;
    ctx.fillRect(x, bodyY, carW, bodyH);
    ctx.strokeRect(x, bodyY, carW, bodyH);

    // Cabin / windscreen (darker strip in the middle)
    var cabinW = carW * 0.5;
    var cabinX = x + (carW - cabinW) / 2;
    var cabinY = bodyY + 4;
    var cabinH = bodyH * 0.4;
    ctx.fillStyle = '#a68540';
    ctx.fillRect(cabinX, cabinY, cabinW, cabinH);
    ctx.strokeStyle = '#1a2a6c';
    ctx.lineWidth = 1;
    ctx.strokeRect(cabinX, cabinY, cabinW, cabinH);

    // Wheels (two circles at bottom)
    var wheelR = 8;
    var wheelY = y + bodyH - 4;
    ctx.fillStyle = '#333';
    ctx.strokeStyle = '#1a2a6c';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.arc(x + 12, wheelY, wheelR, 0, Math.PI * 2);
    ctx.fill();
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(x + carW - 12, wheelY, wheelR, 0, Math.PI * 2);
    ctx.fill();
    ctx.stroke();

    // Headlights (small rectangles at front)
    ctx.fillStyle = '#fff9e6';
    ctx.fillRect(x + 4, bodyY + 2, 6, 5);
    ctx.fillRect(x + carW - 10, bodyY + 2, 6, 5);
  }

  function drawFalling() {
    var neutral = '#4a6fa5';
    state.falling.forEach(function (o) {
      ctx.fillStyle = neutral;
      ctx.fillRect(o.lane * laneW + (laneW - o.w) / 2, o.y, o.w, o.h);
      ctx.strokeStyle = 'rgba(255,255,255,0.5)';
      ctx.lineWidth = 1;
      ctx.strokeRect(o.lane * laneW + (laneW - o.w) / 2, o.y, o.w, o.h);
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 11px Arial';
      ctx.textAlign = 'center';
      ctx.fillText(o.label, o.lane * laneW + laneW / 2, o.y + o.h / 2 + 4);
    });
  }

  function addSpark(x, y, type, now) {
    state.sparks.push({ x: x, y: y, type: type, createdAt: now });
  }

  function drawSparks(now) {
    var maxAge = 350;
    state.sparks = state.sparks.filter(function (s) {
      var age = now - s.createdAt;
      if (age > maxAge) return false;
      var radius = 8 + (age / maxAge) * 25;
      var alpha = 1 - age / maxAge;
      ctx.globalAlpha = alpha;
      ctx.beginPath();
      ctx.arc(s.x, s.y, radius, 0, Math.PI * 2);
      ctx.fillStyle = s.type === 'correct' ? '#28a745' : '#dc3545';
      ctx.fill();
      ctx.globalAlpha = 1;
      return true;
    });
  }

  function endGame() {
    state.gameOver = true;
    if (state.animId) cancelAnimationFrame(state.animId);
    state.animId = null;
    endScoreEl.textContent = state.score;
    endTimeEl.textContent = Math.round(state.survivedSec);
    showScreen('end');
  }

  function gameLoop(now) {
    state.animId = requestAnimationFrame(gameLoop);
    if (state.gameOver) return;

    var dt = (now - state.lastFrameAt) / 1000;
    if (state.lastFrameAt === 0) dt = 0;
    state.lastFrameAt = now;

    state.timeLeft -= dt;
    if (state.timeLeft <= 0) {
      state.timeLeft = 0;
      state.survivedSec = GAME_DURATION_SEC;
      endGame();
      return;
    }
    state.survivedSec = GAME_DURATION_SEC - state.timeLeft;

    if (now - state.lastSpawnAt >= SPAWN_MIN + Math.random() * (SPAWN_MAX - SPAWN_MIN)) {
      spawnObject();
      state.lastSpawnAt = now;
    }

    var carW = laneW * CAR_WIDTH_RATIO;
    var carH = ch * CAR_HEIGHT_RATIO;
    var carY = ch - carH - 20;
    var carLaneIdx = Math.min(LANES - 1, Math.max(0, Math.floor((state.carX + carW / 2) / laneW)));

    state.falling.forEach(function (o) {
      o.y += OBJECT_SPEED * 60 * dt;
    });

    state.falling = state.falling.filter(function (o) {
      if (o.y > ch) return false;
      if (o.y + o.h >= carY && o.y <= carY + carH && o.lane === carLaneIdx) {
        var correct = o.category === state.questionCategory;
        state.score += correct ? CORRECT_SCORE : WRONG_SCORE;
        addSpark(o.lane * laneW + laneW / 2, o.y + o.h / 2, correct ? 'correct' : 'wrong', now);
        return false;
      }
      return true;
    });

    timerEl.textContent = Math.max(0, Math.ceil(state.timeLeft));
    scoreEl.textContent = state.score;

    drawRoad();
    drawFalling();
    drawCar();
    drawSparks(now);
  }

  function startGame() {
    var selected = questionSelect ? questionSelect.value : 'input';
    state.questionCategory = selected === 'random'
      ? ALL_CATEGORIES[Math.floor(Math.random() * ALL_CATEGORIES.length)]
      : selected;
    state.score = 0;
    state.timeLeft = GAME_DURATION_SEC;
    state.carLane = 1;
    state.carX = carXForLane(1);
    state.falling = [];
    state.sparks = [];
    state.lastSpawnAt = 0;
    state.lastFrameAt = 0;
    state.gameOver = false;
    state.survivedSec = 0;
    questionTextEl.textContent = QUESTION_LABELS[state.questionCategory] || state.questionCategory;
    scoreEl.textContent = '0';
    timerEl.textContent = '60';
    showScreen('game');
    state.animId = requestAnimationFrame(gameLoop);
  }

  function restart() {
    if (state.animId) cancelAnimationFrame(state.animId);
    state.animId = null;
    showScreen('start');
  }

  document.addEventListener('keydown', function (e) {
    if (state.screen !== 'game' || state.gameOver) return;
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      state.carLane = Math.max(0, state.carLane - 1);
      state.carX = carXForLane(state.carLane);
    }
    if (e.key === 'ArrowRight') {
      e.preventDefault();
      state.carLane = Math.min(LANES - 1, state.carLane + 1);
      state.carX = carXForLane(state.carLane);
    }
  });

  if (startBtn) startBtn.addEventListener('click', startGame);
  if (restartBtn) restartBtn.addEventListener('click', restart);

  var gameResetBtn = document.getElementById('quizGameResetBtn');
  if (gameResetBtn) gameResetBtn.addEventListener('click', restart);

  if (questionSelect) {
    questionSelect.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') startGame();
    });
  }
})();
