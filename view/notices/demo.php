<?php
/**
 * @var int $daysLeft
 */
$url = admin_url('admin.php?page=wcerply-settings');
$caption = __('Plugin settings', 'wcerply');
$link = "<a href=\"$url\">$caption</a>";
?>

<div class="notice notice-warning is-dismissible">
    <p>
        <?= sprintf(
            __(
                'Your Woocommerce Erply integration demo period ends in %d days. If you have purchased the full version, please enter your license key in %s.',
                'wcerply'
            ),
            $daysLeft,
            $link
        ) ?>
    </p>
</div>
