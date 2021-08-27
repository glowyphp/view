<?php $this->section('bar') ?>
Foo content...
<?php $this->endSection() ?>
<?php $this->section('bar') ?>
Bar content...
<?php $this->endSection() ?>
<?= $this->getSection('bar') ?>