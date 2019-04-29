<?php

    require "auth.php";

    try {
        $pdo = new PDO('mysql:host='.$mysql_server.';dbname='.$mysql_dbname, $mysql_username, $mysql_password);
    } catch (PDOException $e) {
        echo $e;
    }

?>