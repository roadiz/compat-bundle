<?php
declare(strict_types=1);

$aliases = require __DIR__ . '/aliases.php';

foreach ($aliases as $className => $alias) {
    class_alias($className, $alias);
}
