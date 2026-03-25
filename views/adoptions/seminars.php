<?php if ($canRegisterSeminar): ?>
    <form class="adoption-form-grid" id="adoption-seminar-register-form">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label class="field adoption-form-span-2">
            <span class="field-label">Register To Seminar</span>
            <select class="select" name="seminar_id">
                <option value="">Select seminar</option>
                <?php foreach ($seminars as $seminar): ?>
                    <option value="<?= (int) $seminar['id'] ?>">
                        <?= htmlspecialchars($seminar['title'] . ' - ' . date('M d, Y h:i A', strtotime((string) $seminar['scheduled_date'])), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn-secondary" type="submit">Register</button>
    </form>
<?php else: ?>
    <div class="adoption-empty-state">Seminar registration becomes available after the interview is completed.</div>
<?php endif; ?>

<?php if ($canUpdateAttendance): ?>
    <form class="adoption-form-grid" id="adoption-attendance-form">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label class="field">
            <span class="field-label">Registered Seminar</span>
            <select class="select" name="seminar_id">
                <option value="">Select seminar</option>
                <?php foreach ($application['seminars'] as $seminar): ?>
                    <option value="<?= (int) $seminar['id'] ?>">
                        <?= htmlspecialchars($seminar['title'] . ' - ' . ($seminar['attendance_status'] ?? 'registered'), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Attendance</span>
            <select class="select" name="attendance_status">
                <option value="registered">Registered</option>
                <option value="attended">Attended</option>
                <option value="no_show">No Show</option>
            </select>
        </label>
        <button class="btn-secondary" type="submit">Update Attendance</button>
    </form>
<?php elseif ($application['seminars'] === []): ?>
    <div class="adoption-empty-state">Attendance updates appear after this application has been registered for a seminar.</div>
<?php else: ?>
    <div class="adoption-empty-state">Attendance updates are no longer available at the current application stage.</div>
<?php endif; ?>

<div class="adoption-related-list" id="adoption-seminar-registrations">
    <?php foreach ($application['seminars'] as $seminar): ?>
        <article class="adoption-related-item">
            <strong><?= htmlspecialchars($seminar['title'], ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars(date('M d, Y h:i A', strtotime((string) $seminar['scheduled_date'])) . ' | ' . ($seminar['attendance_status'] ?? 'registered'), ENT_QUOTES, 'UTF-8') ?></span>
        </article>
    <?php endforeach; ?>
    <?php if ($application['seminars'] === []): ?>
        <div class="adoption-empty-state">This application is not yet registered for a seminar.</div>
    <?php endif; ?>
</div>
