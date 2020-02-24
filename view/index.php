<?php
/**
 * @var \Ultraleet\WcErply\Components\Router\Router $router
 */
if (!defined('ULTRALEET_WCERPLY_PATH')) {
    exit;
}

use Ultraleet\WcErply\Components\Router\Router;

?>
<div class="wrap">
    <h1><?= __('Erply Integration', 'wcerply') ?></h1>
    <p>
        <?= __('Normally, data synchronization is handled automatically, based on the schedule set in settings.', 'wcerply') ?>
    </p>
    <p>
        <?= __('However, you can initiate synchronization manually by clicking on the button below.', 'wcerply') ?>
    </p>
    <button id="wcerply-queue"><?= __('Synchronize now', 'wcerply') ?></button>
    <p class="wcerply-message"></p>
</div>

<script>
jQuery(function ($) {
    $('#wcerply-queue').on('click', function() {
        var $message = $('.wcerply-message');
        var $button = $(this);
        $message.text('');
        $button.prop('disabled', true);
        $.get('<?= admin_url('admin-ajax.php') ?>', {action: '<?= $router->generatePath('admin_ajax_synchronize') ?>'}, function () {
            $message.text('<?= __('Synchronization has been started.', 'wcerply') ?>');
            $button.prop('disabled', false);
        });
    });
});
</script>
