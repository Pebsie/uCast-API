<?php
    require "connect.php";

    $a = $_GET['a'];

    if ($a == "register") {

        $username = $_GET['username'];
        $password = password_hash($_GET['password'],PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("SELECT * FROM user WHERE username='".$username."'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) { // account doesn't exist
            $authcode = rand(10000, 99999);
            $sql = "INSERT INTO user (username, password, token, bio) VALUES ('".$username."', '".$password."', '".$authcode."', 'A new user on uCast!');";
            $query = $pdo->prepare($sql);
            $result = $query->execute();
            
            echo '{"status":"success","authcode":"'.$authcode.'"}';
        } else {
            echo '{"status":"failed","reason":"A user already exists with this username!"}';
        }

    } elseif ($a == "login") {

        $username = $_GET['username'];
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username='".$username."'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($_GET['password'], $result['password'])) {
            echo '{"status":"success","authcode":"'.$result['token'].'"}';
        } else {
            echo '{"status":"failed","reason":"The username or password was wrong!"}'; 
        }

    }
?>