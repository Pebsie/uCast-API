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
            
            echo json_encode(['success' => true, 'token' => $authcode]);
        } else {
            echo json_encode(['success' => false, 'reason' => "Username or password was incorrect."]);
        }

    } elseif ($a == "login") {

        $username = $_GET['username'];
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username='".$username."'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($_GET['password'], $result['password'])) {
            echo json_encode(['success' => true, 'token' => $result['token']]);
        } else {
            echo json_encode(['success' => false, 'reason' => "Username or password was incorrect."]);
        }

    } elseif ($a == "upload") {

        $token = $_GET['token'];
        $stmt = $pdo->prepare("SELECT * FROM user WHERE token='".$token."'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $p = $result;

        $target_dir = "clips/";
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
     //   if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
            $sql = "INSERT INTO clip (owner, filepath, created) VALUES (".$result['id'].", 'http://freshplay.co.uk/ucast/".$target_file."', CURRENT_DATE);";
            
            $query = $pdo->prepare($sql);
            $result = $query->execute();
            
            echo json_encode(['success' => true, 'filepath' => 'http://freshplay.co.uk/ucast/'.$target_file]);

            $sql = "SELECT * FROM story WHERE owner=".$p['id']." AND created=CURRENT_DATE";
            $query = $pdo->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_ASSOC);

            if (sizeof($results) == 0) {
                $sql = "INSERT INTO story (owner, thumbnailURL, created, views) VALUES (".$p['id'].", 'http://freshplay.co.uk/ucast/".$target_file."', CURRENT_DATE, 0);";
                $query = $pdo->prepare($sql);
                $query->execute();

                $stmt = $pdo->prepare("SELECT * FROM story WHERE owner=".$p['id']." AND created=CURRENT_DATE");
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                $sql = "DELETE FROM seen WHERE storyID=".$row['id'];
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
            } else {

            }

        } else {
            echo json_encode(['success' => false, 'reason' => 'File is not an image.']);
        }

    } elseif ($a == "get") {

        $t = $_GET['type'];

        if ($t == "story") {

            $user = $_GET['user'];
            $date = $_GET['date'];
            $token = $_GET['token'];

            $sql = "SELECT * FROM clip WHERE owner=".$user." AND created='".$date."';";
            $query = $pdo->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'results' => sizeof($results), 'data' => $results]);

            $sql = "UPDATE story SET views = views + 1 WHERE owner=".$user." AND created='".$date."';";
            $query = $pdo->prepare($sql);
            $query->execute();

            $stmt = $pdo->prepare("SELECT * FROM story WHERE owner=".$user." AND created='".$date."'");
            $stmt->execute();
            $s = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT * FROM user WHERE token='".$token."'");
            $stmt->execute();
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql = "INSERT INTO seen (owner, storyID) VALUES (".$p['id'].", ".$s['id'].");";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        } elseif ($t == "profile") {
            $user = $_GET['user'];
            
            $sql = "SELECT username, avatarURL, bio FROM user WHERE id=".$user;
            $query = $pdo->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($results);
        } elseif ($t == "stories") {

            $user = $_GET['user'];
            
            $sql = "SELECT * FROM story WHERE owner=".$user." ORDER BY id DESC";
            $query = $pdo->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'results' => sizeof($results), 'data' => $results]);

        } elseif ($t == "followinguser") {
            $token = $_GET['token'];
            $toFollow = $_GET['follow'];

            $stmt = $pdo->prepare("SELECT * FROM user WHERE token='".$token."'");
            $stmt->execute();
            $p = $stmt->fetch(PDO::FETCH_ASSOC);
    
            $stmt = $pdo->prepare("SELECT * FROM following WHERE owner=".$p['id']." AND creator=".$toFollow.";");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);    

            if (sizeof($result) != 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        } elseif ($t == "followingcount") {

            $token = $_GET['token'];
            $stmt = $pdo->prepare("SELECT * FROM user WHERE token='".$token."'");
            $stmt->execute();
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM following WHERE owner=".$p['id'].";");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);    
            
            echo json_encode(['success' => true, 'result' => sizeof($result)]);
        } elseif ($t == "popularstories") {
            $stmt = $pdo->prepare("SELECT * FROM story WHERE created = CURRENT_DATE ORDER BY views DESC LIMIT 20;");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);    

            echo json_encode(['success' => true, 'results' => sizeof($result), 'data' => $result]);
        } elseif ($t == "followingstories") {

            $token = $_GET['token'];
            $stmt = $pdo->prepare("SELECT * FROM user WHERE token='".$token."'");
            $stmt->execute();
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($p) {
                $stmt = $pdo->prepare("SELECT * FROM following WHERE owner=".$p['id'].";");
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);    
                
                $data = array();

                foreach ($result as $row) {
                    $stmt = $pdo->prepare("SELECT * FROM story WHERE owner=".$row['creator']);
                    $stmt->execute();
                    $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($stories as $story) {
                        $stmt = $pdo->prepare("SELECT * FROM seen WHERE owner=".$p['id']." AND storyID=".$story['id'].";");
                        $stmt->execute();
                        $seen = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (sizeof($seen) == 0){
                            array_push($data, $story);
                        }
                    }
                }

                echo json_encode(['success' => true, 'results' => sizeof($data), 'data' => $data]);
            }
        }
    } elseif ($a == "search") {
        $q = $_GET['query'];

        $sql = "SELECT id, username, avatarURL FROM user WHERE username LIKE '%".$q."%';";
        $query = $pdo->prepare($sql);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'results' => sizeof($results), 'data' => $results]);

    } elseif ($a == "follow") {
        $token = $_GET['token'];
        $toFollow = $_GET['follow'];

        $stmt = $pdo->prepare("SELECT * FROM user WHERE token='".$token."'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $stmt = $pdo->prepare("SELECT * FROM following WHERE owner=".$result['id']." AND creator=".$toFollow.";");
            $stmt->execute();
            $f = $stmt->fetchAll(PDO::FETCH_ASSOC);    

            if (sizeof($f) > 0) { // they're already following this user! unfollow
                $stmt = $pdo->prepare("DELETE FROM following WHERE owner=".$result['id']." AND creator=".$toFollow.";");
                $stmt->execute();
                echo json_encode(['success' => true, 'status' => "not following"]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO following (owner, creator) VALUES (".$result['id'].", ".$toFollow.");");
                $stmt->execute();
                echo json_encode(['success' => true, 'status' => "following"]);
            }
        } else {
            echo json_encode(['success' => false, 'reason' => 'Incorrect API token']);
        }

    } 
?>