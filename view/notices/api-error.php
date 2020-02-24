<?php
/**
 * @var int $code
 * @var string $message
 */
$url = admin_url('admin.php?page=wcerply-settings');
$caption = __('Plugin settings', 'wcerply');
$link = "<a href=\"$url\">$caption</a>";
?>

<div class="notice notice-error">
    <p>
        <?= sprintf(__('Erply API setup has failed. Please verify API settings on the %s page.', 'wcerply'), $link) ?>
    </p>
    <p>
        <?= $code ? "$code: " : '' ?><?= $message ?>
    </p>
</div>
