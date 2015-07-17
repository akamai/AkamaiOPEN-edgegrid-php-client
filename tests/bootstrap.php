<?php
require_once __DIR__ . '/../vendor/autoload.php';
function getPrivatePropertyTesterClosure($bind)
{
    $closure = function($what) {
        return $this->{$what};
    };
    $tester = $closure->bindTo($bind, $bind);

    return $tester;
}
