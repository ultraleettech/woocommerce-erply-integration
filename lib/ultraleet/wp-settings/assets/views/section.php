<?php
/**
 * @var Section $this
 * @var array $fieldContent
 */

use Ultraleet\WP\Settings\Components\Section;

?>
<table class="form-table">
    <tbody>
        <?php foreach ($fieldContent as $content): ?>
            <?= $content ?>
        <?php endforeach; ?>
    </tbody>
</table>
