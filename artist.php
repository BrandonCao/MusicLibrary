<?php

include('../connectionData.php');

$conn = mysqli_connect($server, $user, $pass, $dbname, $port)
or die('Error connecting to MySQL server.');

$artist_name = htmlspecialchars($_GET["artist"]);

print "<h2>". $artist_name . "</h2>";
$label_query = $conn->prepare("SELECT label.name FROM artist
                                JOIN label USING(idlabel)
                                WHERE artist.name=?");
$label_query->bind_param("s", $artist_name);
$label_query->execute();
$label_query->bind_result($label_name);
while($label_query->fetch()) {
    if($label_name == "Independent") {
        print $artist_name . " is an independent artist."; 
    } else {
        print $artist_name . " is signed with " . $label_name;
    }
}
print "<h3>Music</h3>";
$artist_music = $conn->prepare("SELECT album.name, song.name FROM song
                                JOIN album USING(idalbum)
                                JOIN artist USING(idartist)
                                WHERE artist.name=?
                                ORDER BY album.name");
$artist_music->bind_param("s", $artist_name);
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
