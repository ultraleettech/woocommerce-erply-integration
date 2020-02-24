<?php
    $url = admin_url('admin.php?page=wcerply-settings');
    $caption = __('Plugin settings', 'wcerply');
    $link = "<a href=\"$url\">$caption</a>";
?>

<div class="notice">
    <p>
        <?= sprintf(__('In order to use Erply integration, please fill in API settings on the %s page.', 'wcerply'), $link) ?>
    </p>
</div>
