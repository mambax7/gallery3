<?php defined('SYSPATH') || die('No direct script access.') ?>
<?php foreach ($items as $item): ?>
<div class="g-image-block">
  <a href="<?= url::site('image_block/random/' . $item->id); ?>">
   <?= $item->thumb_img(['class' => 'g-thumbnail']) ?>
  </a>
</div>
<?php endforeach ?>
