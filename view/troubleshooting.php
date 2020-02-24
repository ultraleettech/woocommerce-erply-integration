<?php
/**
 * @var \Ultraleet\WcErply\Troubleshooters\AbstractTroubleshooter[] $troubleshooters
 * @var Router $router
 */
$pageUrl = admin_url('admin-ajax.php');

use Ultraleet\WcErply\Components\Router\Router; ?>
<div class="wrap">
    <h1><?= __('Erply Integration Troubleshooting', 'wcerply') ?></h1>

    <table class="wcerply-troubleshooters" border="0" cellpadding="0" cellspacing="3">
        <?php foreach ($troubleshooters as $worker): ?>
            <tr>
                <td>
                    <a href="" data-id="<?= $worker->getName() ?>" class="troubleshoot"><?= $worker->getTitle() ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p class="wcerply-message"></p>
</div>

<script>
    jQuery(function ($) {
        var ajaxUrl = '<?= admin_url('admin-ajax.php') ?>';
        var starting = false;
        $('.troubleshoot').on('click', function(e) {
            var $message = $('.wcerply-message');
            var $link = $(this);
            e.preventDefault();
            if (starting) {
                return false;
            }
            $message.text('');
            $.get(ajaxUrl, {action: 'wcerply/admin/ajax/troubleshoot/' + $link.data('id')}, function () {
                $message.text('<?= __('Troubleshooting has been started on the background.', 'wcerply') ?>');
                starting = false;
            });
        });
    });
</script>
