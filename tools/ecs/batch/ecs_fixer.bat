:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/markocupic/sac-pilatus-event-stats/src --fix --config vendor/markocupic/sac-pilatus-event-stats/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-pilatus-event-stats/contao --fix --config vendor/markocupic/sac-pilatus-event-stats/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-pilatus-event-stats/config --fix --config vendor/markocupic/sac-pilatus-event-stats/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-pilatus-event-stats/templates --fix --config vendor/markocupic/sac-pilatus-event-stats/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-pilatus-event-stats/tests --fix --config vendor/markocupic/sac-pilatus-event-stats/tools/ecs/config.php
