<?php
/**
 * @var string $label
 * @var string $description
 * @var string $name
 * @var string $value
 * @var array $attributes
 */

use Ultraleet\WP\Settings\Helpers\Template; ?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <?= esc_html($label) ?>
    </th>
    <td class="forminp forminp-text">
        <label for="<?= $attributes['id'] ?>">
            <input type="checkbox"
                   name="<?= $name ?>"
                   <?= Template::attributes($attributes) ?>
                   <?= $value ? 'checked' : '' ?>
                   value="true"
            >
            <?= esc_html($description) ?>
        </label>
    </td>
</tr>
