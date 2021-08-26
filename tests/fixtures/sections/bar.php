<?php $this->section('bar') ?>
Foo content...
<?php $this->endSection('bar') ?>
<?php $this->section('bar') ?>
Bar content...
<?php $this->endSection('bar') ?>
<?= $this->getSection('bar') ?>