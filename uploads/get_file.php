<?php

require_once __DIR__ . '/../lib.inc.php';
// Make sure an ID was passed
if (isset($_GET['ID'])) {
  $LetterID = $_GET['ID'];
  // Connect to the database
  $Data = new MySQLiDB();
  // Fetch the file information
  $query = "SELECT `FileName`,`mime`, `Size`, `file` FROM `WebSite_ViewFiles` "
          . " WHERE `UploadID` = " . intval($LetterID);
  $result = $Data->do_sel_query($query);

  if ($result > 0) {
    // Get the row
    $row = $Data->get_row();
    // Print headers
    header("Content-Type: " . $row['mime']);
    header("Content-Length: " . $row['Size']);
    header("Content-Disposition: attachment; filename=\"". $row['FileName']."\"");
    // Print data
    echo $row['file'];
    exit;
  } else {
    echo 'Error! No File exists with that ID.';
  }

  // Free the mysql resources
  $Data->do_close();
  ;
} else {
  echo 'Error! No ID was passed.';
}
?>
