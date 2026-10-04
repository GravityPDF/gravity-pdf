<?php
// src/bootstrap.php registers this name with class_alias(), which PHPStan cannot follow.
abstract class GF_Background_Process extends \Gravity_Forms\Gravity_Forms\Async\GF_Background_Process {}
