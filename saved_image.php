<?php
$mysqli = new mysqli("localhost", "root", "password", "spiceworks_app_data");
$auid = $mysqli->escape_string($_GET["auid"]);
$ticket = (int) $_GET["ticket"];
?>
<section>
  <heading>
    <h2>
<?php
$result = $mysqli->query("SELECT value FROM extra_ticket_data WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_title'");
$row = $result->fetch_assoc();
if ($row) {
  $value = $row['value'];
  echo htmlentities($value);
}
else {
  http_response_code(404);
  echo "Not found";
}
$result->free();
?>
    </h2>
  </heading>
<?php
$result = $mysqli->query("SELECT value FROM extra_ticket_data WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_data'");
$row = $result->fetch_assoc();
if ($row) {
  $value = $row['value'];
  echo "<img id='saved-image' src='$value'>";
}
$result->free();
?>
</section>
