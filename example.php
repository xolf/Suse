<?php
require_once('suse.class.php');
suse_setup('localhost', 'root', '', 'test');
suse_start();
$_SESSION['Text'] = 1;
suse_finish();
