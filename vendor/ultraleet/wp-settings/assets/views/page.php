<?php
/**
 * @var string $title
 * @var Page[] $pages
 * @var string $currentPageId
 * @var array $sectionContent
 */

use Ultraleet\WP\Settings\Components\Page;

?>
<div class="wrap wcerply">
    <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <?php foreach ($pages as $pageId => $page): ?>
                <a href="<?= add_query_arg(['tab' => $pageId]) ?>"
                   class="nav-tab<?= $pageId == $currentPageId ? ' nav-tab-active' : '' ?>"><?= esc_html(
                        $page->getTitle()
                    ) ?>
                </a>

            <?php endforeach; ?>
        </nav>

        <h1 class="screen-reader-text"><?= $title ?>></h1>

        <?php foreach ($sectionContent as $content): ?>
            <?= $content ?>
        <?php endforeach; ?>

        <p class="submit">
            <button class="button-primary save-button" type="submit"><?= __('Save Changes') ?></button>
            <?php wp_nonce_field("save_settings_$currentPageId") ?>
            <input type="hidden" name="ultraleet_save_settings" value="1">
        </p>
    </form>
</div>
