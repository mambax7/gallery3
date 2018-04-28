<?php defined('SYSPATH') || die('No direct script access.') ?>
<?= $theme->sidebar_top() ?>
<div id="g-view-menu" class="g-buttonset ui-helper-clearfix">
  <?php if ('album' == $page_subtype):?>
    <?= $theme->album_menu() ?>
  <?php elseif ('photo' == $page_subtype) : ?>
    <?= $theme->photo_menu() ?>
  <?php elseif ('movie' == $page_subtype) : ?>
    <?= $theme->movie_menu() ?>
  <?php elseif ('tag' == $page_subtype) : ?>
    <?= $theme->tag_menu() ?>
  <?php endif ?>
</div>

<?= $theme->sidebar_blocks() ?>
<?= $theme->sidebar_bottom() ?>
