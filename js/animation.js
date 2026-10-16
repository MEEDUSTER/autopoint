// AutoPoint — ЛР5: анімовані елементи, керовані кнопками
'use strict';

// ----- Дані: масив об'єктів-автомобілів -----
const cars = [
  { name: 'Mazda 3', color: '#c0392b', lane: 0, speed: 140, start: 40 },
  { name: 'RAV4',    color: '#2e7d5b', lane: 1, speed: 95,  start: 300 },
  { name: 'BMW 3',   color: '#2f5fb3', lane: 2, speed: 180, start: 140 }
];

const lights = [
  { color: 'green',  name: 'Зелений',  time: 2400 },
  { color: 'yellow', name: 'Жовтий',   time: 800 },
  { color: 'red',    name: 'Червоний', time: 2000 }
];

// ----- Стан анімації -----
const state = {
  running: false,
  factor: 1,        // множник швидкості
  angle: 0,         // кут повороту колеса
  distance: 0,      // умовний пробіг, м
  lastTime: 0,
  frameId: null,
  lightIndex: 0,
  lightTimer: null
};

const MIN_FACTOR = 0.5;
const MAX_FACTOR = 3;

// ----- Пошук елементів на сторінці -----
const road = document.getElementById('road');
const wheel = document.getElementById('wheel');
const wheelBox = document.getElementById('wheelBox');
const statusEl = document.getElementById('status');
const speedEl = document.getElementById('speed');
const distanceEl = document.getElementById('distance');
const lightNameEl = document.getElementById('lightName');
const logEl = document.getElementById('log');
const lamps = document.querySelectorAll('.lamp');

// ----- Створення машин у DOM -----
function createCars() {
  for (const car of cars) {
    const el = document.createElement('div');
    el.className = 'auto';
    el.style.top = (car.lane * 33.3 + 6) + '%';
    el.innerHTML =
      '<span class="auto-body" style="background:' + car.color + '"></span>' +
      '<span class="auto-wheel front"></span><span class="auto-wheel rear"></span>' +
      '<span class="auto-label">' + car.name + '</span>';
    road.appendChild(el);
    car.el = el;
    car.x = car.start; // стартова позиція на дорозі
  }
}

// ----- Один кадр анімації -----
function step(time) {
  if (!state.lastTime) state.lastTime = time;
  const dt = (time - state.lastTime) / 1000; // секунди між кадрами
  state.lastTime = time;

  const roadWidth = road.clientWidth;
  const isRed = lights[state.lightIndex].color === 'red';

  cars.forEach(function (car) {
    // на червоний машини їдуть повільніше, але не зупиняються
    const k = isRed ? 0.35 : 1;
    car.x += car.speed * state.factor * k * dt;
    if (car.x > roadWidth) {
      car.x = -120; // виїхала праворуч — повертаємо ліворуч
    }
    car.el.style.transform = 'translateX(' + car.x + 'px)';
  });

  state.angle = (state.angle + 220 * state.factor * dt) % 360;
  wheel.style.transform = 'rotate(' + state.angle + 'deg)';

  state.distance += 25 * state.factor * dt;
  distanceEl.textContent = Math.round(state.distance) + ' м';

  if (state.running) {
    state.frameId = requestAnimationFrame(step);
  }
}

// ----- Світлофор на setTimeout -----
function showLight() {
  const light = lights[state.lightIndex];
  lamps.forEach(function (lamp) {
    lamp.classList.toggle('on', lamp.dataset.color === light.color);
  });
  lightNameEl.textContent = light.name;
}

function nextLight() {
  state.lightIndex = (state.lightIndex + 1) % lights.length;
  showLight();
  state.lightTimer = setTimeout(nextLight, lights[state.lightIndex].time / state.factor);
}

// ----- Допоміжні функції -----
function setStatus(text) {
  statusEl.textContent = text;
  document.body.classList.toggle('is-running', state.running);
}

function updateSpeed() {
  speedEl.textContent = '×' + state.factor.toFixed(1);
}

function log(message) {
  const time = new Date().toLocaleTimeString('uk-UA');
  const li = document.createElement('li');
  li.innerHTML = '<time>' + time + '</time> ' + message;
  logEl.prepend(li);
  while (logEl.children.length > 5) {
    logEl.lastElementChild.remove();
  }
}

// ----- Обробники кнопок -----
function start() {
  if (state.running) return;
  state.running = true;
  state.lastTime = 0;
  state.frameId = requestAnimationFrame(step);
  showLight();
  state.lightTimer = setTimeout(nextLight, lights[state.lightIndex].time / state.factor);
  setStatus('Їдемо');
  log('Старт анімації');
}

function pause() {
  if (!state.running) return;
  state.running = false;
  cancelAnimationFrame(state.frameId);
  clearTimeout(state.lightTimer);
  setStatus('Пауза');
  log('Пауза');
}

function reset() {
  pause();
  state.angle = 0;
  state.distance = 0;
  state.factor = 1;
  state.lightIndex = 0;
  cars.forEach(function (car) {
    car.x = car.start;
    car.el.style.transform = 'translateX(' + car.x + 'px)';
  });
  wheel.style.transform = 'rotate(0deg)';
  distanceEl.textContent = '0 м';
  lightNameEl.textContent = '—';
  lamps.forEach(function (lamp) { lamp.classList.remove('on'); });
  updateSpeed();
  setStatus('Зупинено');
  log('Скинуто до початкового стану');
}

function changeSpeed(delta) {
  const next = Math.min(MAX_FACTOR, Math.max(MIN_FACTOR, state.factor + delta));
  if (next === state.factor) {
    log(delta > 0 ? 'Це максимальна швидкість' : 'Це мінімальна швидкість');
    return;
  }
  state.factor = next;
  updateSpeed();
  log('Швидкість: ×' + state.factor.toFixed(1));
}

function toggleWheel(event) {
  const hidden = wheelBox.classList.toggle('hidden');
  event.target.textContent = hidden ? 'Показати колесо' : 'Сховати колесо';
  log(hidden ? 'Колесо сховано' : 'Колесо показано');
}

// ----- Підключення подій -----
document.getElementById('btnStart').addEventListener('click', start);
document.getElementById('btnPause').addEventListener('click', pause);
document.getElementById('btnReset').addEventListener('click', reset);
document.getElementById('btnFaster').addEventListener('click', function () { changeSpeed(0.5); });
document.getElementById('btnSlower').addEventListener('click', function () { changeSpeed(-0.5); });
document.getElementById('btnWheel').addEventListener('click', toggleWheel);

document.addEventListener('keydown', function (event) {
  if (event.code === 'Space') {
    event.preventDefault(); // щоб сторінка не прокручувалась і не «натискалась» кнопка у фокусі
    if (!event.repeat) {
      state.running ? pause() : start();
    }
  }
});
document.addEventListener('keyup', function (event) {
  if (event.code === 'Space') event.preventDefault();
});

createCars();
reset();
logEl.innerHTML = '';
log('Сторінку завантажено. Натисніть «Старт»');
