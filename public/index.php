<?php

require '/home/corebotu/googledrive/vendor/autoload.php';

require '../app/autoload.php';

require '../app/config.php';

use Controllers\BotController;

$controller = new BotController($pdo);
$controller->handle();