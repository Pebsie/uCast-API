<?php
    /*
        uCast API v1
        Created by Thomas Lock

        All parameters are sent through GET.

        a = {
            register = { 
                username,
                password
            },
            login = {
                username,
                password
            },
            upload = {
                authcode
            },
            get = {
                type = {
                    story = {
                        user,
                        date
                    },
                    profile = {
                        user
                    }
                }
            }
        }
    */
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

    } elseif ($a == "upload") {

        $token = $_GET['token'];
        $stmt = $pdo->prepare("SELECT * FROM user WHERE token='".$token."'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $target_dir = "clips/";
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO clips (owner, filepath, created) VALUES (".$result['id'].", 'http://freshplay.co.uk/uCast/".$target_file."', GETDATE());";
            $query = $pdo->prepare($sql);
            $result = $query->execute();
            echo '{"status":"success","filepath":"http://freshplay.co.uk/uCast/clips/'.$target_file.'"}';
        } else {
            echo '{"status":"failed"}';
        }

    } elseif ($a == "get") {

        $t = $_GET['type'];

        if ($t == "story") {

            $user = $_GET['user'];
            $date = $_GET['date'];

            $sql = "SELECT * FROM clip WHERE owner=".$user.", created='".$date."';";
            $query = $pdo->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results);

        } elseif ($t == "profile") {
            $user = $_GET['user'];
            
            $sql = "SELECT username, avatarURL, bio FROM user WHERE id=".$user;
            $query = $pdo->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($results);
        }

    }
?>