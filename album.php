<html>
<head><title>Album</title></head>
<body>
<?php

include('../connectionData.php');

$conn = mysqli_connect($server, $user, $pass, $dbname, $port)
or die('Error connecting to MySQL server.');

$album_name = $_GET["album"];
print "<h2>" . $album_name . "</h2>";
?>

<h3>Tracklist</h3>
<table border=1>

<?php 

$album_query = $conn->prepare("SELECT song.name, song.duration FROM song
                                JOIN album using(idalbum)
                                WHERE album.name=?");
$album_query->bind_param("s", $album_name);
$album_query->execute();
$album_query->bind_result($song_name, $song_time);
while($album_query->fetch()) {
    print "<tr>";
    print "<td>" . $song_name . "</td>";
    print "<td>" . $song_time . "</td>";
    print "</tr>";
}
?>

</table>

<?php

print "<h3>Buy this album from</h3>";
$stocks_query = $conn->prepare("SELECT album.idalbum, store.name, store.address, store.city, store.state FROM store
                                JOIN stocks using(idstore)
                                JOIN album using(idalbum)
                                WHERE album.name=?");
$stocks_query->bind_param("s", $album_name);

$name = $address = $city = $state = $data_error = "";

list($albumid, $names, $addresses, $cities, $states) = get_stores($stocks_query);

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim(htmlspecialchars($_POST["name"]));
    $address = trim(htmlspecialchars($_POST["address"]));
    $city = trim(htmlspecialchars($_POST["city"]));
    $state = trim(htmlspecialchars($_POST["state"]));
    $data_error = "";
    if(empty($name) ||  empty($address) || empty($city) || empty($state)) {
        $data_error = "All fields are required";
    } else {
        if(!in_stores($name, $conn)) {
            $stores_query = $conn->prepare("INSERT INTO store VALUES (DEFAULT, ?, ?, ?, ?)");
            $stores_query->bind_param("ssss", $name, $address, $city, $state);
            $stores_query->execute();
            $stores_query->close();
        }
        if(!in_albums_stores($name, $names)) {
            $get_storeid = $conn->prepare("SELECT store.idstore, store.name FROM store WHERE store.name=?");
            $get_storeid->bind_param("s", $name);
            $get_storeid->execute();
            $get_storeid->bind_result($storeid, $name);
            $get_storeid->fetch();
            $get_storeid->close();

            $insert_stocks = $conn->prepare("INSERT INTO stocks VALUES (?, ?)");
            $insert_stocks->bind_param("ii", $albumid, $storeid);
            $insert_stocks->execute();
            $insert_stocks->close();
        }

        list($albumid, $names, $addresses, $citites, $states) = get_stores($stocks_query);
    }

}

function get_stores($stocks_query) {
    $stocks_query->execute();
    $stocks_query->bind_result($albumid, $store_name, $store_address, $store_city, $store_state);
    $names = $addresses = $cities = $states = array();
    while($stocks_query->fetch()) {
        $names[] = array($store_name, $storeid);
        $addresses[] = $store_address;
        $cities[] = $store_city;
        $states[] = $store_state;
    }
    return array($albumid, $names, $addresses, $cities, $states);
}

function in_stores($name, $conn) {
    $all_stores = $conn->prepare("SELECT store.name FROM store");
    $all_stores->execute();
    $all_stores->bind_result($row_name);
    while($all_stores->fetch()) {
        if($name == $row_name)
            return true;
    }
    return false;
}

function in_albums_stores($name, $names) {
    for($k = 0; $k < count($names); $k++) {
        if($names[$k] == $name)
            return true;
    } 
    return false;
}

for($j = 0; $j < count($names); $j++) {
    print "<p>" . $names[$j][0] . ", " . $addresses[$j] . " " . $cities[$j] . ", " . $states[$j] . "</p>";
}

$album_query->close();
$stocks_query->close();
$conn->close();
?>

<h3>Know a store that stocks this album? Enter it here</h3>
<?php print "<form method='POST' action='album.php?album=" . $album_name . "'>"; ?>
<table>
<tr><th>Name</th><th>Address</th><th>City</th><th>State</th></tr>
<tr>
    <td><input type="text" name="name"></td>
    <td><input type="text" name="address"></td>
    <td><input type="text" name="city"></td>
    <td><input type="text" name="state"></td>
    <td><input type="submit" value="Submit"></td>
    <td><span><?php echo $data_error;?></span></td>
</tr>
</table>
</form>
</body>
</html>
