<?php
require_once('includes/header.php');
require_once('includes/classes/VideoUploadData.php');
require_once('includes/classes/VideoProcessor.php');

if(!isset($_POST['uploadButton'])) {
  echo "No file sent to page";
  exit();
}

$videoUploadData = new VideoUploadData(
  $_FILES['fileInput'],
  $_POST['titleInput'],
  $_POST['descriptionInput'],
  $_POST['privacyInput'],
  $_POST['categoryInput'],
  'REPLACE-THIS'
);

$videoProcessor = new VideoProcessor($con);
$uploadSuccess = $videoProcessor->upload($videoUploadData);
?>
