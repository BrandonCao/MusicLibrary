<html>
<head><title>Tour</title></head>
<body>
<?php

include('../connectionData.php');

$conn = mysqli_connect($server, $user, $pass, $dbname, $port)
or die('Error connecting to MySQL server.');

$tour_name = $_GET["tour"];
print "<h2>" . $tour_name . "</h2>";
$tour_query = $conn->prepare("SELECT s.date, venue.name, venue.address, venue.city, venue.state FROM music.show as s
                                JOIN venue USING(idvenue)
                                JOIN tour USING(idtour)
                                WHERE tour.name=?
                                ORDER BY s.date");
$tour_query->bind_param("s", $tour_name);
?>

<table border=1>
<tr><th>Date</th><th>Venue</th><th>Address</th><tr>

<?php
$tour_query->execute();
$tour_query->bind_result($show_date, $venue_name, $venue_address, $venue_city, $venue_state);
while($tour_query->fetch()) {
    $date = explode(" ", $show_date);
    print "<tr>";
    print "<td>" . $date[0] . "</td>";
    print "<td>" . $venue_name . "</td>";
    print "<td>" . $venue_address . " " . $venue_city . ", " . $venue_state;
    print "</tr>";
}
?>
</table>
</body>
</html>
