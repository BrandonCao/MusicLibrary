<?php

include('../connectionData.php');

$conn = mysqli_connect($server, $user, $pass, $dbname, $port)
or die('Error connecting to MySQL server.');

$library_query = $conn->prepare("SELECT song.name, song.duration, artist.idartist, artist.name, 
                                album.idalbum, album.name, album.genre FROM song 
                                JOIN album USING(idalbum) 
                                JOIN artist USING(idartist) 
                                ORDER BY artist.name, album.name, song.name");
list($songs, $times, $artists, $albums, $genres) = get_library($library_query);

$song=$time=$artist=$album=$genre="";
$song=trim(htmlspecialchars($_POST["song"]));
$time=trim(htmlspecialchars($_POST["time"]));
$artist=trim(htmlspecialchars($_POST["artist"]));
$album=trim(htmlspecialchars($_POST["album"]));
$genre=trim(htmlspecialchars($_POST["genre"]));
$data_error = "";
if($_SERVER["REQUEST_METHOD"] == "POST") { 
if(empty($song) || empty($time) || empty($artist) || empty($album) || empty($genre)) {
        $data_error = "All fields are required";
} else {
    $idartist = "";
    if(empty($idartist = in_music_array($artist, $artists))) {
        $insert_artist = $conn->prepare("INSERT INTO artist VALUES (DEFAULT, ?, NULL)");
        $insert_artist->bind_param("s", $artist);
        $insert_artist->execute();
        $insert_artist->close();

        $get_artist_id = $conn->prepare("SELECT idartist FROM artist WHERE name=?");
        $get_artist_id->bind_param("s", $artist);
        $get_artist_id->execute();
        $get_artist_id->bind_result($idartist);
        $get_artist_id->fetch();
        $get_artist_id->close();
    }

    $idalbum = "";
    if(empty($idalbum = in_music_array($album, $albums))) {
        $insert_album = $conn->prepare("INSERT INTO album VALUES (DEFAULT, ?, ?, ?, ?)");
        $insert_album->bind_param("ssss", $album, $genre, $dropped, $idartist);
        $dropped = date("Y-m-d");
        $insert_album->execute();
        $insert_album->close();

        $get_album_id = $conn->prepare("SELECT idalbum FROM album WHERE name=?");
        $get_album_id->bind_param("s", $album);
        $get_album_id->execute();
        $get_album_id->bind_result($idalbum);
        $get_album_id->fetch();
        $get_album_id->close();
    }

    if(!in_array($song, $songs)) {
        $insert_song = $conn->prepare("INSERT INTO song VALUES (DEFAULT, ?, ?, ?)");
        $insert_song->bind_param("sss", $song, $time, $idalbum);
        $insert_song->execute();
        $insert_song->close();
        list($songs, $times, $artists, $albums, $genres) = get_library($library_query);
    }
}
}

function get_library(&$library_query) {
    $library_query->execute();
    $library_query->bind_result($song_name, $song_time, $artist_id, $artist_name, $album_id, $album_name, $album_genre);
    $songs = $times = $artists = $albums = $genres = array();
    while($library_query->fetch()) {
        $songs[] = $song_name;
        $times[] = $song_time;
        $artists[] = array($artist_id, $artist_name);
        $albums[] = array($album_id, $album_name);
        $genres[] = $album_genre;
    }
    return array($songs, $times, $artists, $albums, $genres);
}

function in_music_array($needle, $haystack) {
    for($x = 0; $x < count($haystack); $x++) {
        if($needle == $haystack[$x][1])
            return $haystack[$x][0];
    }
    return "";
}

?>
<html>

<head>

    <title>Music Library</title>

</head>

<body>

<h2>Library</h2>
<form action="index.php" method="POST">
<table>
<tr><th>Song Name</th><th>Time</th><th>Artist</th><th>Album</th><th>Genre</th></tr>
<tr>
    <td><input type="text" name="song"></td>
    <td><input type="text" name="time"></td>
    <td><input type="text" name="artist"></td>
    <td><input type="text" name="album"></td>
    <td><input type="text" name="genre"></td>
    <td><input type="submit" value="Submit"></td>
    <td><span><?php echo $data_error;?></span>
</tr>
</table>
</form>
<table border=1>
    <tr><th>Song</th><th>Time</th><th>Artist</th><th>Album</th><th>Genre</th></tr>
<?php 
for($i = 0; $i < count($songs); $i++) {
    print "<tr>";
    print "<td>" . $songs[$i]. "</td>";
    print "<td>" . $times[$i] . "</td>";
    print "<td> <a href='artist.php?artist=" . $artists[$i][1] . "'>" . $artists[$i][1] . "</a></td>";
    print "<td> <a href='album.php?album=" . $albums[$i][1] . "'>" . $albums[$i][1] . "</a></td>";
    print "<td>" . $genres[$i] . "</td>";
    print "</tr>";
}

$conn->close();
?>
</table>

</body>

</html>
