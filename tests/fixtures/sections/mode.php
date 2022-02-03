<?php use Glowy\View\View ?>
<?php $this->section('foo') ?>
Foo content...
<?php $this->endSection() ?>
<?php $this->section('foo', View::SECTION_MODE_APPEND) ?>
append content...
<?php $this->endSection() ?>
<?php $this->appendSection('foo') ?>
append content...
<?php $this->endSection() ?>
<?php $this->section('foo', View::SECTION_MODE_PREPEND) ?>
prepend content...
<?php $this->endSection() ?>
<?php $this->prependSection('foo') ?>
prepend content...
<?php $this->endSection() ?>
<?= $this->getSection('foo') ?>