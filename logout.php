<?php
session_start();

// beeindigd alle sessiedata
session_destroy();

// Redirect naar de loginpagina
header("Location: homepage.html");
exit;
?>