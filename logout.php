<?php
session_start();

// beeindigd alle sessiedata
session_destroy();

// ga terug naar de loginpagina
header("Location: homepage.html");
exit;
?>