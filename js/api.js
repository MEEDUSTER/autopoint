/*
 * AutoPoint — ЛР7 (бонус): динамічна таблиця з даними API lab.vntu.org за допомогою jQuery.
 * - кнопка «Оновити» повторно завантажує дані;
 * - два сортування: за іменем (name) і за фракцією (affiliation), повторний клік змінює напрям;
 * - стовпці будуються автоматично за полями отриманих об’єктів.
 * На PHP-сервері запит іде через api_proxy.php (обхід CORS), інакше — напряму до API.
 */
$(function () {
  'use strict';

  var API_URL = 'http://lab.vntu.org/api-server/lab7.php';
  var PROXY_URL = 'api_proxy.php';

  var rows = [];          // отримані записи
  var columns = [];       // назви стовпців
  var sortKey = null;     // поле сортування
  var sortDir = 1;        // 1 — А→Я, -1 — Я→А
  var loading = false;

  var $table = $('#apiTable');
  var $info = $('#apiInfo');
  var $refresh = $('#btnRefresh');

  // Куди звертатися: спочатку локальний проксі (якщо сайт працює на PHP-сервері), потім напряму.
  // Сторінка HTTPS (GitHub Pages) не може звертатися до HTTP-ресурсу, тому там пробуємо https://.
  function sources() {
    var direct = location.protocol === 'https:' ? API_URL.replace(/^http:/, 'https:') : API_URL;
    var list = [];
    if (location.protocol === 'http:' || location.protocol === 'https:') {
      list.push({ url: PROXY_URL, label: 'через api_proxy.php', proxy: true });
    }
    list.push({ url: direct, label: 'напряму (' + direct + ')', proxy: false });
    return list;
  }

  // Приводимо відповідь API до масиву об’єктів (масив, {data: [...]}, або об’єкт з об’єктами)
  function normalize(data) {
    var list = null;
    if ($.isArray(data)) {
      list = data;
    } else if (data && typeof data === 'object') {
      $.each(data, function (key, value) {
        if (list === null && $.isArray(value)) { list = value; }
      });
      if (list === null) {
        list = $.map(data, function (value) { return value && typeof value === 'object' ? [value] : null; });
        if (list.length === 0) { list = [data]; }
      }
    }
    if (list === null) { return null; }
    return $.map(list, function (item) {
      return [item && typeof item === 'object' && !$.isArray(item) ? item : { value: item }];
    });
  }

  // Усі поля всіх об’єктів; name і affiliation — першими
  function collectColumns(list) {
    var cols = [];
    $.each(list, function (i, item) {
      $.each(item, function (key) {
        if ($.inArray(key, cols) === -1) { cols.push(key); }
      });
    });
    $.each(['affiliation', 'name'], function (i, key) {
      var pos = $.inArray(key, cols);
      if (pos > 0) { cols.splice(pos, 1); cols.unshift(key); }
    });
    return cols;
  }

  function cellText(value) {
    if (value === null || value === undefined || value === '') { return '—'; }
    return typeof value === 'object' ? JSON.stringify(value) : String(value);
  }

  function compare(a, b) {
    var x = a[sortKey] === undefined || a[sortKey] === null ? '' : cellText(a[sortKey]);
    var y = b[sortKey] === undefined || b[sortKey] === null ? '' : cellText(b[sortKey]);
    return x.localeCompare(y, 'uk', { sensitivity: 'base', numeric: true }) * sortDir;
  }

  function renderHead(cols) {
    var $tr = $('<tr>');
    $.each(cols, function (i, key) {
      var $th = $('<th>').addClass('sortable').attr('data-sort', key).attr('title', 'Сортувати за полем ' + key).text(key);
      if (key === sortKey) { $th.append($('<span class="arrow">').text(sortDir === 1 ? '▲' : '▼')); }
      $tr.append($th);
    });
    $table.find('thead').empty().append($tr);
  }

  function renderTable() {
    var cols = columns.length ? columns : ['name', 'affiliation'];
    var data = rows.slice();
    if (sortKey) { data.sort(compare); }
    renderHead(cols);
    var $tbody = $table.find('tbody').empty();
    $.each(data, function (i, item) {
      var $tr = $('<tr>');
      $.each(cols, function (j, key) { $tr.append($('<td>').text(cellText(item[key]))); });
      $tbody.append($tr);
    });
    $('.api-toolbar [data-sort]').each(function () {
      var key = $(this).data('sort');
      $(this).toggleClass('is-active', key === sortKey)
        .find('.arrow').remove();
      if (key === sortKey) { $(this).append($('<span class="arrow">').text(sortDir === 1 ? ' ▲' : ' ▼')); }
    });
  }

  function showMessage(text, cssClass) {
    var cols = columns.length ? columns : ['name', 'affiliation'];
    renderHead(cols);
    $table.find('tbody').empty().append(
      $('<tr>').append($('<td>').addClass(cssClass).attr('colspan', cols.length).text(text))
    );
  }

  function now() { return new Date().toLocaleTimeString('uk-UA'); }

  function fail(xhr, textStatus, src) {
    var code = xhr.status;
    if (xhr.responseJSON && xhr.responseJSON.upstream_status !== undefined) {
      code = xhr.responseJSON.upstream_status;      // код, який повернув сам lab.vntu.org (через проксі)
    }
    var detail = textStatus === 'timeout' ? 'тайм-аут' : (code === 0 ? '0 — немає з’єднання або запит заблоковано браузером' : code);
    rows = [];
    showMessage('Сервер lab.vntu.org не відповідає (код ' + detail + ').', 'error-cell');
    var extra = xhr.responseJSON && xhr.responseJSON.error ? ' ' + xhr.responseJSON.error : '';
    $info.text('Помилка о ' + now() + ', запит ' + src.label + '.' + extra + ' Натисніть «Оновити», щоб спробувати ще раз.');
  }

  function tryLoad(list, index) {
    var src = list[index];
    $.ajax({ url: src.url, dataType: 'json', cache: false, timeout: 15000 })
      .done(function (data) {
        var list2 = normalize(data);
        if (list2 === null) {
          rows = [];
          showMessage('Сервер lab.vntu.org повернув дані в невідомому форматі.', 'error-cell');
          $info.text('Помилка формату о ' + now() + ', запит ' + src.label + '.');
        } else {
          rows = list2;
          columns = collectColumns(rows);
          renderTable();
          $info.text('Отримано записів: ' + rows.length + ' · запит ' + src.label + ' · оновлено о ' + now());
        }
      })
      .fail(function (xhr, textStatus) {
        // Проксі немає (статичний хостинг: 404 або PHP-файл не виконується) — пробуємо напряму
        var noProxy = src.proxy && !xhr.responseJSON && (xhr.status === 404 || xhr.status === 405 || textStatus === 'parseerror');
        if (noProxy && index + 1 < list.length) {
          tryLoad(list, index + 1);
          return;
        }
        fail(xhr, textStatus, src);
      });
  }

  function load() {
    if (loading) { return; }
    loading = true;
    $refresh.prop('disabled', true);
    showMessage('Завантаження…', 'loading-cell');
    $info.text('Запит до API…');
    $(document).one('ajaxStop', function () {
      loading = false;
      $refresh.prop('disabled', false);
    });
    tryLoad(sources(), 0);
  }

  function sortBy(key) {
    if (sortKey === key) { sortDir = -sortDir; } else { sortKey = key; sortDir = 1; }
    if (rows.length) { renderTable(); } else { $('.api-toolbar [data-sort]').removeClass('is-active').filter('[data-sort="' + key + '"]').addClass('is-active'); }
  }

  $refresh.on('click', load);
  $('.api-toolbar').on('click', '[data-sort]', function () { sortBy($(this).data('sort')); });
  $table.on('click', 'th.sortable', function () { sortBy($(this).attr('data-sort')); });

  load();
});
