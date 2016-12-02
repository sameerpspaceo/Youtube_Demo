<?php
require_once 'config.php';
require_once 'includes/DB.php';
// create an object of class DB.
$db = new DB;

if(isset($_REQUEST['videoSubmit'])){
	$videoTitle = $_REQUEST['title'];
	$videoDesc = $_REQUEST['description'];
	$videoTags = $_REQUEST['tags'];
	
	if($_FILES["videoFile"]["name"] != ''){
	    $fileSize = $_FILES['videoFile']['size'];
	    $fileType = $_FILES['videoFile']['type'];
	    $fileName = str_shuffle('nityanandamaity').'-'.basename($_FILES["videoFile"]["name"]);
		$targetDir = "videos/";
		$targetFile = $targetDir . $fileName;
		$allowedTypeArr = array("video/mp4", "video/avi", "video/mpeg", "video/mpg", "video/mov", "video/wmv", "video/rm");
		if(in_array($fileType, $allowedTypeArr)) {
		    if(move_uploaded_file($_FILES['videoFile']['tmp_name'], $targetFile)) {
		        $videoFilePath = $targetFile;
		    }else{
		        header('Location:'.BASE_URI.'index.php?err=ue');
				exit;
		    }
		}else{
			header('Location:'.BASE_URI.'index.php?err=fe');
			exit;
		}
	
		// insert video data
		$db->insert($videoTitle,$videoDesc,$videoTags,$videoFilePath);
		
	}else{
		header('Location:'.BASE_URI.'index.php?err=bf');
		exit;
	}
}

// get last video data
$result = $db->getLastRow();



/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * Google Developers Console <https://console.developers.google.com/>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */



if (isset($_GET['code'])) {
	if (strval($_SESSION['state']) !== strval($_GET['state'])) {
	  die('The session state did not match.');
	}

	$client->authenticate($_GET['code']);
	$_SESSION['token'] = $client->getAccessToken();

	header('Location: ' . REDIRECT_URI);
}

if (isset($_SESSION['token'])) {
	$client->setAccessToken($_SESSION['token']);
}

$htmlBody = '';
// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try{
    // REPLACE this value with the path to the file you are uploading.
    $videoPath = $result['video_path'];

    // Create a snippet with title, description, tags and category ID
    // Create an asset resource and set its snippet metadata and type.
    // This example sets the video's title, description, keyword tags, and
    // video category.
    $snippet = new Google_Service_YouTube_VideoSnippet();
    $snippet->setTitle($result['video_title']);
    $snippet->setDescription($result['video_description']);
    $snippet->setTags(explode(",",$result['video_tags']));

    // Numeric video category. See
    // https://developers.google.com/youtube/v3/docs/videoCategories/list 
    $snippet->setCategoryId("22");

    // Set the video's status to "public". Valid statuses are "public",
    // "private" and "unlisted".
    $status = new Google_Service_YouTube_VideoStatus();
    $status->privacyStatus = "public";

    // Associate the snippet and status objects with a new video resource.
    $video = new Google_Service_YouTube_Video();
    $video->setSnippet($snippet);
    $video->setStatus($status);

    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 1 * 1024 * 1024;

    // Setting the defer flag to true tells the client to return a request which can be called
    // with ->execute(); instead of making the API call immediately.
    $client->setDefer(true);

    // Create a request for the API's videos.insert method to create and upload the video.
    $insertRequest = $youtube->videos->insert("status,snippet", $video);

    // Create a MediaFileUpload object for resumable uploads.
    $media = new Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        'video/*',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($videoPath));

    // Read the media file and upload it.
    $status = false;
    $handle = fopen($videoPath, "rb");
    while (!$status && !feof($handle)) {
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
    }
    fclose($handle);

    // If you want to make other calls after the file upload, set setDefer back to false
    $client->setDefer(false);
	
	// Update youtube video ID to database
	$db->update($result['video_id'],$status['id']);
	// delete video file from local folder
	@unlink($result['video_path']);
	
    $htmlBody .= "<p class='succ-msg'>Video have been uploaded successfully.</p><ul>";
	$htmlBody .= '<embed width="400" height="315" src="https://www.youtube.com/embed/'.$status['id'].'"></embed>';
	$htmlBody .= '<li><b>Title: </b>'.$status['snippet']['title'].'</li>';
	$htmlBody .= '<li><b>Description: </b>'.$status['snippet']['description'].'</li>';
	$htmlBody .= '<li><b>Tags: </b>'.implode(",",$status['snippet']['tags']).'</li>';
    $htmlBody .= '</ul>';
	$htmlBody .= '<a href="logout.php">Logout</a>';

  } catch (Google_ServiceException $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
	$htmlBody .= 'Please reset session <a href="logout.php">Logout</a>';
  }
  
  $_SESSION['token'] = $client->getAccessToken();
} else {
	// If the user hasn't authorized the app, initiate the OAuth flow
	$state = mt_rand();
	$client->setState($state);
	$_SESSION['state'] = $state;
  
	$authUrl = $client->createAuthUrl();
	$htmlBody = <<<END
	<h3>Authorization Required</h3>
	<p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>SPACE-O :: Youtube upload</title>
<link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body>
<div class="youtube-box">
	<h1>Upload video to YouTube using PHP</h1>
	<div class="video-up"><a href="<?php echo BASE_URI; ?>">New Upload</a></div>
	<div class="content">
		<?php echo $htmlBody; ?>
	</div>
</div>
</div>
</body>
</html>