<?php
$mysqli = new mysqli("localhost", "root", "password", "spiceworks_app_data");
$auid = $mysqli->escape_string($_POST["auid"]);
$ticket = (int) $_POST["ticket"];
$image_title = $mysqli->escape_string($_POST["title"]);
$image_data = $mysqli->escape_string($_POST["image"]);
$result = $mysqli->query("SELECT value FROM extra_ticket_data WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_title'");
if ($result->num_rows > 0) {
  $sql1 = "UPDATE extra_ticket_data SET value = '$image_title' WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_title'";
  $sql2 = "UPDATE extra_ticket_data SET value = '$image_data' WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_data'";
  if (!$mysqli->query($sql1)) {
    http_response_code(400);
    echo "\nupdate error: " . $mysqli->sqlstate;
    echo "\nupdate error: " . $mysqli->error;
  }
  elseif (!$mysqli->query($sql2)) {
    http_response_code(400);
    echo "\nupdate error: " . $mysqli->sqlstate;
    echo "\nupdate error: " . $mysqli->error;
  }
  else {
    echo "updated";
  }
}
else {
  $sql1 = "INSERT INTO extra_ticket_data (`auid`, `ticket_id`, `key`, `value`) VALUES ('$auid', $ticket, 'image_title', '$image_title')";
  $sql2 = "INSERT INTO extra_ticket_data (`auid`, `ticket_id`, `key`, `value`) VALUES ('$auid', $ticket, 'image_data', '$image_data')";
  if (!$mysqli->query($sql1)) {
    http_response_code(400);
    echo "\ninsert error: " . $mysqli->sqlstate;
    echo "\ninsert error: " . $mysqli->error;
  }
  elseif (!$mysqli->query($sql2)) {
    http_response_code(400);
    echo "\ninsert error: " . $mysqli->sqlstate;
    echo "\ninsert error: " . $mysqli->error;
  }
  else {
    echo "created";
  }
}
$result->free();
?>
