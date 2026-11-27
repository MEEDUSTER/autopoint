/*
 * AutoPoint — ЛР7: випадковий жарт із текстового файлу jokes.txt.
 * Файл завантажується за допомогою XMLHttpRequest (AJAX) без перезавантаження сторінки.
 * Формат jokes.txt: один жарт — один рядок.
 */
(function () {
  'use strict';

  var JOKES_URL = 'jokes.txt';
  var jokeBox = document.getElementById('joke');
  var statusBox = document.getElementById('jokeStatus');
  var counterBox = document.getElementById('jokeCounter');
  var button = document.getElementById('btnJoke');
  var lastIndex = -1;   // номер попереднього жарту — щоб не показати той самий двічі поспіль
  var shown = 0;        // скільки жартів уже показано

  function setStatus(text, isError) {
    statusBox.textContent = text;
    statusBox.className = 'ajax-status' + (isError ? ' is-error' : '');
  }

  // Випадковий номер, відмінний від попереднього (якщо жартів більше одного)
  function randomIndex(count) {
    var i;
    do {
      i = Math.floor(Math.random() * count);
    } while (count > 1 && i === lastIndex);
    return i;
  }

  function showJoke(text) {
    var lines = text.split(/\r?\n/)
      .map(function (s) { return s.trim(); })
      .filter(function (s) { return s !== ''; });
    if (lines.length === 0) {
      setStatus('Файл jokes.txt порожній.', true);
      return;
    }
    lastIndex = randomIndex(lines.length);
    shown++;
    jokeBox.textContent = lines[lastIndex];
    jokeBox.classList.remove('joke-in');
    void jokeBox.offsetWidth;            // перезапуск CSS-анімації появи
    jokeBox.classList.add('joke-in');
    counterBox.textContent = 'Жарт №' + (lastIndex + 1) + ' з ' + lines.length + ' · показано: ' + shown;
    setStatus('Завантажено з файлу ' + JOKES_URL + ' о ' + new Date().toLocaleTimeString('uk-UA'), false);
  }

  function loadJoke() {
    // створюємо запит до сервера
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        // дія, що виконується при успішному завантаженні даних
        showJoke(xhttp.responseText);
        button.disabled = false;
      } else if (this.readyState == 4) {
        // запит завершився, але з помилкою (404, 500 або сервер недоступний — status 0)
        setStatus('Не вдалося завантажити жарти (код ' + this.status + '). Відкрийте сторінку через веб-сервер, а не як файл.', true);
        button.disabled = false;
      }
    };
    xhttp.open('GET', JOKES_URL + '?t=' + Date.now(), true);   // ?t=… — щоб браузер не брав файл із кешу
    button.disabled = true;
    setStatus('Завантаження…', false);
    xhttp.send();
  }

  button.addEventListener('click', loadJoke);
  loadJoke();   // перший жарт — одразу після відкриття сторінки
})();
