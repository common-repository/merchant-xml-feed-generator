<?php

require('gmpf-generator.php');

$generator = new GoogleProductFeedGenerator(get_the_ID());

$generator->content_type();

echo $generator->headers();

echo $generator->products_loop();

echo $generator->footer();

?>
