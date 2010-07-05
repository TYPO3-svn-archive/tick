<?php

setcookie('tick', '', time() + 3600, '/');

echo "Stopping tick...";

echo '<a href="start.php">[Start it]</a>';