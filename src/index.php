<?php
header("Cache-Control: no-store");
header("Content-Type: text/plain");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: CREATE, GET, ADD, TAKE, OPTIONS");
try {
    require "donotputongithub.php";
    $db = new PDO($db_dsn, $db_user, $db_pass);
    $id = ltrim($_SERVER['REQUEST_URI'], '/');
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'CREATE':
            $id = random_bytes(64);
            $query = $db->prepare("INSERT INTO `data`(`id`) VALUES (?)");
            $query->execute([$id]);
            http_response_code(201);
            echo bin2hex($id);
            break;

        case 'GET':
            $query = $db->prepare("SELECT `data` FROM `data` WHERE `id`=?");
            $query->execute([hex2bin($id)]);
            $data = $query->fetchColumn();
            if($data === false) {
                http_response_code(404);
                die();
            }
            http_response_code(200);
            echo $data;
            break;
        
        case 'ADD':
            $data = intval(file_get_contents("php://input"));
            $query = $db->prepare("UPDATE `data` SET `data`=`data`+? WHERE `id`=?");
            $query->execute([$data, hex2bin($id)]);
            if($query->rowCount() != 1) {
                http_response_code(404);
                die();
            }
            http_response_code(204);
            break;

        case 'TAKE':
            $data = intval(file_get_contents("php://input"));
            $query = $db->prepare("UPDATE `data` SET `data`=`data`-:data WHERE `id`=:id AND `data`>=:data");
            $query->execute(["data"=>$data, "id"=>hex2bin($id)]);
            if($query->rowCount() != 1) {
                http_response_code(404);
                die();
            }
            http_response_code(204);
            break;

        default:
            http_response_code(204);
            die();
            break;
    }
} catch(Exception $e) {
    http_response_code(500);
    echo $e;
}