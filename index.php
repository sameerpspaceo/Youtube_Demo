<?php
if(session_id() != '') session_destroy();
if(isset($_GET['err'])){
	if($_GET['err'] == 'bf'){
		$errorMsg = 'Please select a video file for upload.';
	}elseif($_GET['err'] == 'ue'){
		$errorMsg = 'Sorry, there was an error uploading your file.';
	}elseif($_GET['err'] == 'fe'){
		$errorMsg = 'Sorry, only MP4, AVI, MPEG, MPG, MOV & WMV files are allowed.';
	}else{
		$errorMsg = 'Some problems occured, please try again.';
	}
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
		<form method="post" name="multiple_upload_form" id="multiple_upload_form" enctype="multipart/form-data" action="youtube_upload.php">
		<?php echo (!empty($errorMsg))?'<p class="err-msg">'.$errorMsg.'</p>':''; ?>
		<label for="title">Title:</label><input type="text" name="title" id="title" value="" />
		<label for="description">Description:</label> <textarea name="description" id="description" cols="20" rows="2" ></textarea>
		<label for="tags">Tags:</label> <input type="text" name="tags" id="tags" value="" />
		<label for="video_file">Choose Video File:</label>	<input type="file" name="videoFile" id="videoFile" >
		<input name="videoSubmit" id="submit" type="submit" value="Upload">
		</form>
	</div>
</body>
</html>