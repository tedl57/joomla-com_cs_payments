<?php // 2014-01-04 - $Header$ {{{1
define('_JEXEC','');
define('JPATH_COMPONENT',dirname(dirname(__DIR__)));
echo JPATH_COMPONENT;
require "base.php";
require "plugins/download.php";
echo "<br />classes:<br />";
echo "<pre>";
print_r(get_declared_classes());

// vim: fdm=marker
?>
