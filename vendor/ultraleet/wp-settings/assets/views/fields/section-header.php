<?php
/**
 * @var string $title
 * @var string $text
 */
?>
<tr>
    <td colspan="2" class="section-heading">
        <?php if ($title): ?>
            <h2><?= esc_html($title) ?></h2>
        <?php endif; ?>
        <?php if ($text): ?>
            <div>
                <?= wpautop($text) ?>
            </div>
        <?php endif; ?>
    </td>
</tr>
