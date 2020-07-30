<?php
session_start();

$db_config = include __DIR__ . '/../../config/database.php';
		
//var_dump($db_config);die;
//数据库模块
$conn=mysqli_connect('p:localhost',$db_config['username'],$db_config['password'],$db_config['database']);

/*
if($conn->connect_error){
    die('Connect Error (' . $conn->connect_errno . ') '
   . $conn->connect_error);
}
if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') '
            . mysqli_connect_error());
}
echo 'Success... ' . $conn->host_info . "\n";

$conn->close();
*/
?>