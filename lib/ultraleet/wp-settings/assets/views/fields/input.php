<?php
/**
 * @var string $label
 * @var string $name
 * @var string $value
 * @var array $attributes
 */

use Ultraleet\WP\Settings\Helpers\Template; ?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?= $attributes['id'] ?>"><?= esc_html($label) ?></label>
    </th>
    <td class="forminp forminp-text">
        <input name="<?= $name ?>" <?= Template::attributes($attributes) ?> value="<?= esc_attr($value) ?>">
    </td>
</tr>
