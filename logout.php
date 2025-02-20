<?php
session_start();

// Vernietig alle sessiedata
session_destroy();

// Redirect naar de loginpagina
header("Location: homepage.html");
exit;
?>