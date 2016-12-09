<?php

include('../connectionData.php');

$conn = mysqli_connect($server, $user, $pass, $dbname, $port)
or die('Error connecting to MySQL server.');

$artist_name = htmlspecialchars($_GET["artist"]);
$selected_label = htmlspecialchars($_POST["select_label"]);
$new_label = trim(htmlspecialchars($_POST["new_label"]));
if(!empty($selected_label))
    update_artist_label($conn, $selected_label, $artist_name);
if(!empty($new_label)) {
    $inserted_label = insert_new_label($conn, $new_label, $artist_name);
    update_artist_label($conn, $inserted_label, $artist_name);
}

print "<h2>". $artist_name . "</h2>";
$label_query = $conn->prepare("SELECT label.name FROM artist
                                JOIN label USING(idlabel)
                                WHERE artist.name=?");
$label_query->bind_param("s", $artist_name);
$label_query->execute();
$label_query->bind_result($label_name);
while($label_query->fetch()) {
    if($label_name == "Independent") {
        print $artist_name . " is currently listed as an independent artist.";
    } else {
        print $artist_name . " is signed with " . $label_name;
    }
}
if(empty($label_name)) {
    print $artist_name . " is currently listed as an independent artist.<br>";
    print "Is " . $artist_name . " signed to a label? Choose an existing one, or enter a new label.";
    $label_data = $conn->query("SELECT idlabel, name FROM label");
    print "<form id='label_form' method='POST' action='artist.php?artist=" . $artist_name . "'>";
    print "<select form='label_form' name='select_label'>";
    print "<option value=''></option>";
    while($row = $label_data->fetch_assoc()) {
        print "<option value=" . $row["idlabel"] . ">" . $row["name"] . "</option>";
    }
    print "</select>";
    print "New Label:";
    print "<input type='text' name='new_label'>";
    print "<input type='submit'>";
    print "</form>";
}
print "<h3>Music</h3>";
$artist_music = $conn->prepare("SELECT album.name, song.name FROM song
                                JOIN album USING(idalbum)
                                JOIN artist USING(idartist)
                                WHERE artist.name=?
                                ORDER BY album.name");
$artist_music->bind_param("s", $artist_name);

function insert_new_label($conn, $label, $artist_name) {
    $new_label_query = $conn->prepare("INSERT INTO label VALUES (DEFAULT, ?)");
    $new_label_query->bind_param("s", $label);
    $new_label_query->execute();
    $new_label_query->close();
    
    $get_id_query = $conn->prepare("SELECT idlabel FROM label where name=?");
    $get_id_query->bind_param("s", $label);
    $get_id_query->execute();
    $get_id_query->bind_result($idlabel);
    $get_id_query->fetch();
    return $idlabel;
}

function update_artist_label($conn, $label, $artist_name) {
    $update_artist_label_query = $conn->prepare("UPDATE artist
                                                SET idlabel=? 
                                                WHERE artist.name=?");
    $update_artist_label_query->bind_param("is", $label, $artist_name);
    $update_artist_label_query->execute();
    $update_artist_label_query->close();
}

?>

<html>
<head><title>Artist</title></head>
<body>

<table border=1>
<tr><th>Songs</th><th>Album</th></tr>
<?php

$artist_music->execute();
$artist_music->bind_result($album_name, $song_name);

while($artist_music->fetch()) {
    print "<tr>";
    print "<td>" . $song_name . "</td>";
    print "<td><a href='album.php?album=$album_name'>" . $album_name . "</a></td>";
    print "</tr>";
}

$artist_music->close();
?>
</table>

<h3>Tours</h3>

<table border=1>

<?php

$tour_query = $conn->prepare("SELECT tour.name, tour.to, tour.from FROM tour
                                JOIN artist USING(idartist)
                                WHERE artist.name=?");
$tour_query->bind_param("s", $artist_name);
$tour_query->execute();
$tour_query->bind_result($tour_name, $tour_to, $tour_from);
while($tour_query->fetch()) {
    print "<tr>";
    print "<td><a href='tour.php?tour=$tour_name'>" . $tour_name . "</a></td>";
    $from = explode(" ", $tour_from);
    $to = explode(" ", $tour_to);
    print "<td>" . $from[0] . " to " . $to[0] . "</td>";
    print "</tr>";
}

$tour_query->close();
$conn->close();

?>

</table>

</body>
</html>
