<?php
    if ($_POST['dbname']) {
        $authFile = fopen("auth.php", "w");
        $authWrite = '
        <?php
            $mysql_username = "'.$_POST["username"].'";
            $mysql_password = "'.$_POST["password"].'";
            $mysql_server = "'.$_POST["server"].'";
            $mysql_dbname = "'.$_POST['dbname'].'";
        ?>';
        fwrite( $authFile, $authWrite );
        require "connect.php";
        echo "<h1>Setup</h1>";
        echo "<h2>Database</h2>";

        echo "<h3>User</h3>";
        $sql = "CREATE TABLE user (
            id int NOT NULL AUTO_INCREMENT,
            username text NOT NULL,
            password text NOT NULL,
            token text,
            bio text,
            PRIMARY KEY (id)
        );";
        $query = $pdo->prepare($sql);
        $query->execute();  

        echo "<h3>Following</h3>";
        $sql = "CREATE TABLE following (
            id int NOT NULL AUTO_INCREMENT,
            owner int NOT NULL,
            creator int NOT NULL,
            PRIMARY KEY (id)
        );";
        $query = $pdo->prepare($sql);
        $query->execute();  
        
        echo "<h3>Clips</h3>";
        $sql = "CREATE TABLE following (
            id int NOT NULL AUTO_INCREMENT,
            owner int NOT NULL,
            filepath text NOT NULL,
            created date NOT NULL,
            PRIMARY KEY (id)
        );";
        $query = $pdo->prepare($sql);
        $query->execute();  

        echo "<h3>Finished</h3>";
    } else {
?>
    <h1>Setup</h1>
    <h2>Please enter database details</h2>
    
    <form method="POST" action="setup.php">
        Username: <input type="text" name="username" default="mysql_username" /> <br />
        Password: <input type="password" name="password" default="password" /> <br />
        Server address: <input type="text" name="server" default="mysql_server" /> <br />
        Database name: <input type="text" name="dbname" default="mysql_dbname" /> <br />
        <input type="submit" value="Install" /> <br />
    </form>
    <? } ?>