<?php $this->section('foo') ?>
Foo content...
<?php $this->endSection() ?>

<div class="foo">
    <?= $this->getSection('foo') ?>
</div>

<div class="bar">
    <?= $this->getSection('bar') ?>
</div>

<div class="zed">
    <?= $this->getSection('zed') ?>
</div>