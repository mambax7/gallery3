<?php defined('SYSPATH') || die('No direct script access.') ?>
<li>
    <?php $name = $menu->label->for_html() ?>
    <?php $hover_text = t('Your profile')->for_html_attr() ?>
    <?= t('Logged in as %name', [
        'name' => html::mark_clean("<a href='$menu->url' title='$hover_text' id='$menu->id'>{$name}</a>")
    ]) ?>
</li>
