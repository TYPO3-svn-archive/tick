<?php

setcookie('tick', 'tack', time() + 3600, '/');

echo "Starting tick...";

echo '<a href="stop.php">[Stop it]</a>';