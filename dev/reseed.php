<?php
/** Refresh the localhost fixture after development changes. Never distributed. */
require '/wordpress/wp-load.php';
require __DIR__ . '/seed.php';
echo 'Fixture refreshed';
