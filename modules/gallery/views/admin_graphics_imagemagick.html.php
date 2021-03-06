<?php defined('SYSPATH') || die('No direct script access.') ?>
<div id="imagemagick" class="g-block<?= $is_active ? ' g-selected' : '' ?><?= $tk->installed ? '  g-installed-toolkit' : ' g-unavailable' ?>">
    <img class="logo" width="114" height="118" src="<?= url::file('modules/gallery/images/imagemagick.jpg'); ?>" alt="<?php t('Visit the ImageMagick project site') ?>"/>
    <h3> <?= t('ImageMagick') ?> </h3>
    <p>
        <?= t('ImageMagick is a standalone graphics program available on most Linux systems.  Please refer to the <a href="%url">ImageMagick website</a> for more information.', ['url' => 'http://www.imagemagick.org']) ?>
    </p>
    <?php if ($tk->installed): ?>
        <div class="g-module-status g-info">
            <?= t('ImageMagick version %version is available in %dir', ['version' => $tk->version, 'dir' => $tk->dir]) ?>
        </div>
        <p>
            <a class="g-button ui-state-default ui-corner-all"><?= t('Activate ImageMagick') ?></a>
        </p>
    <?php elseif ($tk->error): ?>
        <div class="g-module-status g-warning">
            <?= $tk->error ?>
        </div>
    <?php endif ?>
</div>
