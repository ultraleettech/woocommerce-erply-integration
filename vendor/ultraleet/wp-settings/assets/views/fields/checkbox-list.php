<?php
/**
 * @var string $label
 * @var string $name
 * @var array $value
 * @var array $attributes
 * @var array $options
 */

use Ultraleet\WP\Settings\Helpers\Template; ?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <?= esc_html($label) ?>
    </th>
    <td class="forminp forminp-checkbox">
        <?php foreach ($options as $id => $title): ?>
        <div>
            <label>
                <input type="checkbox"
                       name="<?= $name ?>[]"
                       <?= Template::attributes($attributes) ?>
                       <?= in_array($id, $value) ? 'checked' : '' ?>
                       value="<?= $id ?>"
                >
                <?= esc_html($title) ?>
            </label>
        </div>
        <?php endforeach; ?>
    </td>
</tr>
