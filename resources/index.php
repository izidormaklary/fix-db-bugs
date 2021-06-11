<?php
declare(strict_types=1);

$sports = ['Football', 'Tennis', 'Ping pong', 'Volley ball', 'Rugby', 'Horse riding', 'Swimming', 'Judo', 'Karate'];

function openConnection(): PDO
{
    // No bugs in this function, just use the right credentials.
    $dbhost = "localhost";
    $dbuser = "becode";
    $dbpass = "becode";
    $db = "sportsDb";

    $driverOptions = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO('mysql:host=' . $dbhost . ';dbname=' . $db, $dbuser, $dbpass, $driverOptions);
}

$pdo = openConnection();

if (!empty($_POST['firstname']) && !empty($_POST['lastname'])) {
    //@todo possible bug below? DONE: table row needs to be created if no id is set

    if (empty($_POST['id'])) {
        $handle = $pdo->prepare('INSERT INTO user ( firstname, lastname, year) VALUES ( :firstname, :lastname, :year)');
        $message = 'Your record has been added';
    } else {
        //@todo why does this not work? DONE: wrongly formatted query, UPDATE not VALUES but SET
        $handle = $pdo->prepare(' UPDATE user SET firstname = :firstname, lastname = :lastname, year = :year WHERE id = :id ');
        $handle->bindValue(':id', $_POST['id']);
        $message = 'Your record has been updated';
    }

    $handle->bindValue(':firstname', $_POST['firstname']);
    $handle->bindValue(':lastname', $_POST['lastname']);
    $handle->bindValue(':year', (date('Y')));
    $handle->execute();
    if (!empty($_POST['id'])) {
        $handle = $pdo->prepare('DELETE FROM sport WHERE user_id = :id');
        $handle->bindValue(':id', $_POST['id']);
        $handle->execute();
        $userId = $_POST['id'];
    } else {
        //todo ?why did I leave this if empty? There must be no important reason for this. Move on.
        //DONE: userId can be get from the last row from user table
        $userId =$pdo->lastInsertId();
    }

    //@todo Why does this loop not work? If only I could see the bigger picture. DONE: userID variable was in the wrong place.
    foreach ($_POST['sports'] as $sport) {

        $handle = $pdo->prepare('INSERT INTO sport (user_id, sport) VALUES (:userId, :sport)');
        $handle->bindValue(':userId', $userId);
        $handle->bindValue(':sport', $sport);
        $handle->execute();
    }

} elseif (isset($_POST['delete'])) {
    //@todo BUG? Why does always delete all my users? DONE: didnt match for user id.
    $handle = $pdo->prepare('DELETE FROM user WHERE id = :id');
    //The line below just gave me an error, probably not important. Annoying line.
    $handle->bindValue(':id', $_POST['id']);
    $handle->execute();

    $message = 'Your record has been deleted';
}

//@todo Invalid query? DONE: separator in the wrong place in CONCAT_WS, wasnt defined from which table should the query get the id, also added aliases.

$handle = $pdo->prepare('SELECT u.id, concat_ws(" ", firstname, lastname) AS name, sport FROM user u LEFT JOIN sport s ON u.id = s.user_id where year = :year order by s.user_id');
$handle->bindValue(':year', date('Y'));
$handle->execute();
$users = $handle->fetchAll();

$saveLabel = 'Save record';
if (!empty($_GET['id'])) {
    $saveLabel = 'Update record';

    $handle = $pdo->prepare('SELECT id, firstname, lastname FROM user where id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    $selectedUser = $handle->fetch();

    //This segment checks all the current sports for an existing user when you update him. Currently that is not working however. :-(
    $selectedUser['sports'] = [];
    $handle = $pdo->prepare('SELECT sport FROM sport where user_id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();

    foreach ($handle->fetchAll() as $sport) {
        $selectedUser['sports'][] = $sport["sport"];//@todo I just want an array of all sports of this, why is it not working?  DONE: fetchAll outputs a nested array.
    }
}

if (empty($selectedUser['id'])) {
    $selectedUser = [
        'id' => '',
        'firstname' => '',
        'lastname' => '',
        'sports' => []
    ];
}

require 'view.php';
// All bugs where written with Love for the learning Process. No actual bugs where harmed or eaten during the creation of this code.