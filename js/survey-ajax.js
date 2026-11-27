/*
 * AutoPoint — ЛР7: надсилання анкети ЛР6 за допомогою XMLHttpRequest (AJAX).
 * Перехоплюємо submit, надсилаємо FormData методом POST у survey_ajax.php,
 * отримуємо JSON і показуємо результат або помилки без перезавантаження сторінки.
 * Якщо JavaScript вимкнено, форма працює як раніше — звичайним POST на survey.php.
 */
(function () {
  'use strict';

  var form = document.getElementById('surveyForm');
  if (!form || !window.FormData) { return; }          // немає форми або дуже старий браузер — звичайний POST

  var result = document.getElementById('surveyResult');
  var alerts = document.getElementById('formAlerts');
  var button = document.getElementById('surveySubmit');
  var counter = document.getElementById('respCount');
  var buttonText = button.textContent;

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) { node.className = className; }
    if (text !== undefined) { node.textContent = text; }   // textContent — захист від XSS
    return node;
  }

  function clearErrors() {
    alerts.innerHTML = '';
    var marked = form.querySelectorAll('.has-error');
    for (var i = 0; i < marked.length; i++) { marked[i].classList.remove('has-error'); }
    var old = form.querySelectorAll('.field-error');
    for (var j = 0; j < old.length; j++) { old[j].parentNode.removeChild(old[j]); }
  }

  function showAlert(text) {
    alerts.appendChild(el('div', 'alert alert-error', text));
  }

  // Помилки біля відповідних полів (контейнери мають атрибут data-field)
  function showErrors(errors) {
    var count = 0, first = null;
    for (var key in errors) {
      if (!Object.prototype.hasOwnProperty.call(errors, key) || key === 'form') { continue; }
      var box = form.querySelector('[data-field="' + key + '"]');
      if (!box) { continue; }
      box.classList.add('has-error');
      box.appendChild(el('p', 'field-error', errors[key]));
      first = first || box;
      count++;
    }
    if (errors.form) { showAlert(errors.form); }
    if (count > 0) { showAlert('Перевірте форму: неправильно заповнених полів — ' + count + '.'); }
    (count > 0 ? first : alerts).scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  // Панель успіху: дата і час заповнення та відповіді — без перезавантаження сторінки
  function showSuccess(data) {
    result.innerHTML = '';
    var box = el('div', 'alert alert-success');
    box.appendChild(el('p', 'done-title', 'Дякуємо, ' + data.name + '! Анкету прийнято.'));
    var time = el('p', 'done-time', 'Дата і час заповнення: ');
    time.appendChild(el('b', '', data.filled_at_text));
    box.appendChild(time);
    var file = el('p', 'done-file', 'Створено файл: ');
    file.appendChild(el('code', '', data.file));
    file.appendChild(document.createTextNode(data.db ? ' · запис додано в базу даних' : ' · база даних тимчасово недоступна'));
    box.appendChild(file);
    box.appendChild(el('p', 'done-file', 'Надіслано через AJAX (XMLHttpRequest) — сторінка не перезавантажувалась.'));
    result.appendChild(box);

    var table = el('table', 'data-table answers-table');
    var head = el('tr');
    head.appendChild(el('th', '', 'Питання'));
    head.appendChild(el('th', '', 'Ваша відповідь'));
    table.appendChild(el('thead')).appendChild(head);
    var body = table.appendChild(el('tbody'));
    for (var i = 0; i < data.answers.length; i++) {
      var tr = body.appendChild(el('tr'));
      tr.appendChild(el('td', '', data.answers[i].question));
      tr.appendChild(el('td', '', data.answers[i].answer));
    }
    result.appendChild(table);

    var again = el('button', 'btn', 'Заповнити ще одну анкету');
    again.type = 'button';
    again.addEventListener('click', function () {
      form.reset();
      clearErrors();
      result.hidden = true;
      form.hidden = false;
      form.querySelector('input[name="name"]').focus();
    });
    var p = el('p');
    p.appendChild(again);
    result.appendChild(p);

    form.hidden = true;
    result.hidden = false;
    if (counter && data.count !== undefined) { counter.textContent = data.count; }
    result.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();                               // зупиняємо звичайне надсилання форми
    clearErrors();

    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
      if (this.readyState !== 4) { return; }
      button.disabled = false;
      button.textContent = buttonText;

      var data = null;
      try { data = JSON.parse(this.responseText); } catch (e) { data = null; }

      if (this.status == 200 && data && data.ok) {
        showSuccess(data);
      } else if (data && data.errors) {                   // 422 — помилки валідації, 403 — CSRF
        if (data.csrf) { form.elements.csrf.value = data.csrf; }
        showErrors(data.errors);
      } else {
        showAlert('Не вдалося надіслати анкету (код ' + this.status + '). Перевірте з’єднання і спробуйте ще раз.');
        alerts.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    };
    xhr.open('POST', form.getAttribute('data-ajax'), true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    button.disabled = true;
    button.textContent = 'Надсилаємо…';
    xhr.send(new FormData(form));                         // дані форми разом із CSRF-токеном
  });
})();
