<?php
/**
 * Форма анкети. Очікує: $old (введені дані), $errors (помилки).
 * Підключається через require — без форми сторінка не має сенсу.
 */
?>
          <form class="survey-form" id="surveyForm" action="survey.php" method="post" data-ajax="survey_ajax.php" novalidate>
            <?= csrf_field() ?>
<?php $fieldErrors = count(array_diff_key($errors, ['form' => true])); ?>
            <div id="formAlerts">
<?php if (isset($errors['form'])): ?>
            <div class="alert alert-error"><?= h($errors['form']) ?></div>
<?php endif; ?>
<?php if ($fieldErrors > 0): ?>
            <div class="alert alert-error">Перевірте форму: неправильно заповнених полів — <?= $fieldErrors ?>.</div>
<?php endif; ?>
            </div>
            <div class="row">
              <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>" data-field="name">
                <label for="name">Ім’я *</label>
                <input id="name" name="name" type="text" maxlength="60" required value="<?= h($old['name'] ?? '') ?>" placeholder="Як до вас звертатись">
                <?php if (isset($errors['name'])): ?><p class="field-error"><?= h($errors['name']) ?></p><?php endif; ?>
              </div>
              <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>" data-field="email">
                <label for="email">Email *</label>
                <input id="email" name="email" type="email" maxlength="100" required value="<?= h($old['email'] ?? '') ?>" placeholder="name@example.com">
                <?php if (isset($errors['email'])): ?><p class="field-error"><?= h($errors['email']) ?></p><?php endif; ?>
              </div>
            </div>
<?php foreach (QUESTIONS as $key => $q): ?>
            <?= render_question($key, $q, $old, $errors) ?>

<?php endforeach; ?>
            <button class="btn" id="surveySubmit" type="submit">Надіслати анкету</button>
          </form>
