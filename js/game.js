// AutoPoint — ЛР5, бонус: міні-гра «Нічна траса»
'use strict';

const canvas = document.getElementById('game');
const ctx = canvas.getContext('2d');
const scoreEl = document.getElementById('score');
const recordEl = document.getElementById('record');
const speedEl = document.getElementById('gameSpeed');

const W = canvas.width;
const H = canvas.height;
const LANES = 3;
const LANE_W = W / LANES;
const RECORD_KEY = 'autopoint-record';

const player = { lane: 1, x: LANE_W * 1.5, y: H - 100, w: 46, h: 78 };

let obstacles = [];
let score = 0;
let record = Number(localStorage.getItem(RECORD_KEY)) || 0;
let speed = 1;
let roadOffset = 0;
let spawnTimer = 0;
let lastTime = 0;
let frameId = null;
let mode = 'ready'; // ready | play | pause | over

recordEl.textContent = record;

// ----- Малювання -----
function drawRoad() {
  ctx.fillStyle = '#15191e';
  ctx.fillRect(0, 0, W, H);
  ctx.fillStyle = '#d7a35a';
  ctx.fillRect(0, 0, 4, H);
  ctx.fillRect(W - 4, 0, 4, H);
  ctx.fillStyle = 'rgba(244, 240, 232, 0.55)';
  for (let i = 1; i < LANES; i++) {
    for (let y = -40 + roadOffset; y < H; y += 60) {
      ctx.fillRect(i * LANE_W - 2, y, 4, 30);
    }
  }
}

function drawCar(x, y, w, h, body, lightsColor) {
  ctx.fillStyle = '#050607';                       // колеса
  ctx.fillRect(x - w / 2 - 4, y + 10, 6, 18);
  ctx.fillRect(x + w / 2 - 2, y + 10, 6, 18);
  ctx.fillRect(x - w / 2 - 4, y + h - 28, 6, 18);
  ctx.fillRect(x + w / 2 - 2, y + h - 28, 6, 18);
  ctx.fillStyle = body;                            // кузов
  ctx.beginPath();
  ctx.roundRect(x - w / 2, y, w, h, 10);
  ctx.fill();
  ctx.fillStyle = 'rgba(10, 12, 15, 0.75)';        // скло
  ctx.fillRect(x - w / 2 + 7, y + 16, w - 14, 16);
  ctx.fillRect(x - w / 2 + 7, y + h - 26, w - 14, 12);
  ctx.fillStyle = lightsColor;                     // фари
  ctx.fillRect(x - w / 2 + 5, y + 2, 9, 5);
  ctx.fillRect(x + w / 2 - 14, y + 2, 9, 5);
}

function drawCone(x, y, size) {
  ctx.fillStyle = '#ff7a1a';
  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.lineTo(x - size / 2, y + size);
  ctx.lineTo(x + size / 2, y + size);
  ctx.closePath();
  ctx.fill();
  ctx.fillStyle = '#f4f0e8';
  ctx.fillRect(x - size / 4, y + size * 0.45, size / 2, size * 0.14);
}

function drawMessage(title, subtitle) {
  ctx.fillStyle = 'rgba(8, 9, 11, 0.72)';
  ctx.fillRect(0, 0, W, H);
  ctx.textAlign = 'center';
  ctx.fillStyle = '#f0d7ad';
  ctx.font = '32px Georgia, serif';
  ctx.fillText(title, W / 2, H / 2 - 10);
  ctx.fillStyle = '#a39b90';
  ctx.font = '15px "Segoe UI", sans-serif';
  ctx.fillText(subtitle, W / 2, H / 2 + 24);
}

function draw() {
  drawRoad();
  for (const o of obstacles) {
    if (o.type === 'cone') drawCone(o.x, o.y, o.h);
    else drawCar(o.x, o.y, o.w, o.h, o.color, '#ff4d4d');
  }
  drawCar(player.x, player.y, player.w, player.h, '#d7a35a', '#fff6d5');

  if (mode === 'ready') drawMessage('Нічна траса', 'Натисніть «Почати» або Enter');
  if (mode === 'pause') drawMessage('Пауза', 'Натисніть P або «Пауза», щоб продовжити');
  if (mode === 'over') drawMessage('Аварія!', 'Рахунок: ' + score + ' · Enter — нова гра');
}

// ----- Логіка гри -----
function spawnObstacle() {
  const lane = Math.floor(Math.random() * LANES);
  const isCone = Math.random() < 0.35;
  const colors = ['#2f5fb3', '#2e7d5b', '#8e44ad', '#c0392b', '#7f8c8d'];
  obstacles.push({
    type: isCone ? 'cone' : 'car',
    x: LANE_W * lane + LANE_W / 2,
    y: -90,
    w: isCone ? 30 : 46,
    h: isCone ? 30 : 78,
    color: colors[Math.floor(Math.random() * colors.length)],
    passed: false
  });
}

function hits(a, b) {
  return Math.abs(a.x - b.x) < (a.w + b.w) / 2 - 6 &&
         a.y < b.y + b.h - 6 && a.y + a.h > b.y + 6;
}

function update(dt) {
  speed = 1 + score * 0.04;
  const pixels = 260 * speed * dt;
  roadOffset = (roadOffset + pixels) % 60;

  // плавний перехід авто гравця до центру обраної смуги
  const targetX = LANE_W * player.lane + LANE_W / 2;
  player.x += (targetX - player.x) * Math.min(1, dt * 14);

  spawnTimer -= dt;
  if (spawnTimer <= 0) {
    spawnObstacle();
    spawnTimer = Math.max(0.45, 1.1 - score * 0.02);
  }

  for (const o of obstacles) {
    o.y += pixels;
    if (!o.passed && o.y > player.y + player.h) {
      o.passed = true;
      score++;
    }
    if (hits(player, o)) {
      gameOver();
      return;
    }
  }
  obstacles = obstacles.filter(function (o) { return o.y < H + 100; });

  scoreEl.textContent = score;
  speedEl.textContent = speed.toFixed(1);
}

function loop(time) {
  const dt = Math.min(0.05, (time - lastTime) / 1000 || 0);
  lastTime = time;
  if (mode === 'play') update(dt);
  draw();
  if (mode === 'play') frameId = requestAnimationFrame(loop);
}

function newGame() {
  obstacles = [];
  score = 0;
  speed = 1;
  spawnTimer = 0.6;
  player.lane = 1;
  player.x = LANE_W * 1.5;
  scoreEl.textContent = '0';
  speedEl.textContent = '1.0';
  mode = 'play';
  lastTime = performance.now();
  cancelAnimationFrame(frameId); // не запускаємо другий цикл паралельно
  frameId = requestAnimationFrame(loop);
}

function togglePause() {
  if (mode === 'play') {
    mode = 'pause';
  } else if (mode === 'pause') {
    mode = 'play';
    lastTime = performance.now();
    cancelAnimationFrame(frameId);
    frameId = requestAnimationFrame(loop);
  }
  draw();
}

function gameOver() {
  mode = 'over';
  if (score > record) {
    record = score;
    localStorage.setItem(RECORD_KEY, String(record));
    recordEl.textContent = record;
  }
  scoreEl.textContent = score;
  draw();
}

function move(direction) {
  if (mode !== 'play') return;
  player.lane = Math.max(0, Math.min(LANES - 1, player.lane + direction));
}

// ----- Керування -----
document.addEventListener('keydown', function (e) {
  if (e.key === 'Enter' && e.target.tagName === 'BUTTON') return; // кнопка сама обробить Enter
  switch (e.key) {
    case 'ArrowLeft': case 'a': case 'A': case 'ф': case 'Ф':
      move(-1); e.preventDefault(); break;
    case 'ArrowRight': case 'd': case 'D': case 'в': case 'В':
      move(1); e.preventDefault(); break;
    case 'p': case 'P': case 'з': case 'З':
      togglePause(); break;
    case 'Enter':
      if (mode !== 'play') newGame();
      break;
  }
});

document.getElementById('btnPlay').addEventListener('click', function () {
  if (mode === 'pause') togglePause();
  else if (mode !== 'play') newGame();
});
document.getElementById('btnGamePause').addEventListener('click', togglePause);
document.getElementById('btnRestart').addEventListener('click', newGame);
document.getElementById('btnLeft').addEventListener('click', function () { move(-1); });
document.getElementById('btnRight').addEventListener('click', function () { move(1); });

draw();
