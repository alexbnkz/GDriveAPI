<?php
$linux = false;

if ($linux) {
	include_once __DIR__ . '/vendor/autoload.php';
	include_once __DIR__ . '/vendor/google/apiclient/examples/templates/base.php';
}
else {    
	include_once __DIR__ . '\vendor\autoload.php';
	include_once __DIR__ . '\vendor\google\apiclient\examples\templates\base.php';
}

echo pageHeader("File Upload - Uploading a simple file");

/*************************************************
 * Ensure you've downloaded your oauth credentials
 ************************************************/
if (!$oauth_credentials = getOAuthCredentialsFile()) {
  echo missingOAuth2CredentialsWarning();
  return;
}

/************************************************
 * The redirect URI is to the current page, e.g:
 * http://localhost:8080/simple-file-upload.php
 ************************************************/
$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

$client = new Google_Client();
$client->setAuthConfig($oauth_credentials);
$client->setRedirectUri($redirect_uri);
$client->addScope('https://www.googleapis.com/auth/plus.me ');
$client->addScope('https://www.googleapis.com/auth/userinfo.email ');
$client->addScope('https://www.googleapis.com/auth/userinfo.profile ');
$client->addScope('https://www.googleapis.com/auth/drive ');
$client->addScope('https://www.googleapis.com/auth/drive.file ');
$client->addScope('https://www.googleapis.com/auth/drive.apps.readonly');
$client->addScope('https://www.googleapis.com/auth/drive.appdata');

$service = new Google_Service_Drive($client);

// add "?logout" to the URL to remove a token from the session
if (isset($_REQUEST['logout'])) {
  unset($_SESSION['upload_token']);
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

/************************************************
 * If we have a code back from the OAuth 2.0 flow,
 * we need to exchange that with the
 * Google_Client::fetchAccessTokenWithAuthCode()
 * function. We store the resultant access token
 * bundle in the session, and redirect to ourself.
 ************************************************/
if (isset($_GET['code'])) {
  $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
  $client->setAccessToken($token);

  // store in the session also
  $_SESSION['upload_token'] = $token;

  // redirect back to the example
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

// set the access token as part of the client
if (!empty($_SESSION['upload_token'])) {
  $client->setAccessToken($_SESSION['upload_token']);
  if ($client->isAccessTokenExpired()) {
    unset($_SESSION['upload_token']);
  }
} else {
  $authUrl = $client->createAuthUrl();
}
?>

<html lang="pt-BR">
<head>
	<meta http-equiv="Content-Language" content="pt-BR">
	<meta charset="utf-8">

	<title> WeCloud</title>
	<meta name="description" content="">
	<meta name="author" content="">

	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

	<!-- Basic Styles -->
	<link rel="stylesheet" type="text/css" media="screen" href="smart/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" media="screen" href="smart/font-awesome.min.css">

	<!-- SmartAdmin Styles : Caution! DO NOT change the order -->
	<link rel="stylesheet" type="text/css" media="screen" href="smart/smartadmin-production-plugins.min.css">
	<link rel="stylesheet" type="text/css" media="screen" href="smart/smartadmin-production.min.css">
	<link rel="stylesheet" type="text/css" media="screen" href="smart/smartadmin-skins.min.css">

	<!-- SmartAdmin RTL Support -->
	<link rel="stylesheet" type="text/css" media="screen" href="smart/smartadmin-rtl.min.css"> 

	<!-- Demo purpose only: goes with demo.js, you can delete this css when designing your own WebApp -->
	<link rel="stylesheet" type="text/css" media="screen" href="smart/demo.min.css">

	<!-- #FAVICONS -->
	<link rel="shortcut icon" href="img/favicon/favicon.ico" type="image/x-icon">
	<link rel="icon" href="img/favicon/favicon.ico" type="image/x-icon">

	<!-- #GOOGLE FONT -->
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,300,400,700">
</head>
<body>

	<div class="row">
		<div class="col-sm-12">
			<div id="myCarousel" class="carousel fade profile-carousel">

				<div class="air air-top-left padding-10">
					<h4 class="txt-color-white font-md"><?php echo date("M d, Y ");?></h4>
				</div>

				<div class="carousel-inner">
					<div class="item active"><img src="smart/s1.jpg" alt=""></div>
					<div class="item "><img src="smart/s2.jpg" alt=""></div>
					<div class="item "><img src="smart/m3.jpg" alt=""></div>
				</div>
			</div>
                        <div id="content">
<?php if (isset($authUrl)) { ?>
                            <div class="request col-lg-12 text-center">
                                <h1>Acesso ao Google Drive API</h1>
                                <a class='login btn btn-primary ' href='<?= $authUrl ?>'>Connect Me!</a>
                            </div> 
<?php 
} 
else 
    { 
    
    if($client->getAccessToken()) {
        $service = new Google_Service_Drive($client);

        $userInfo = new Google_Service_Oauth2($client);
        $user = $userInfo->userinfo->get();
        
        if (isset($_POST['delete']) && !empty($_POST['delete'])) {
            try {
                $service->files->delete($_POST['delete']);
            } catch (Exception $e) {
                print "An error occurred: " . $e->getMessage();
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {  
            for ($i = 0; $i < count($_FILES['userFile']['name']); $i++) {
                $uploadfile = __DIR__ .'\\'. basename($_FILES['userFile']['name'][$i]);
                
                move_uploaded_file($_FILES['userFile']['tmp_name'][$i], $uploadfile);

                $fileDrive = new Google_Service_Drive_DriveFile();
                $fileDrive->setName(basename($_FILES['userFile']['name'][$i]));
                $result2 = $service->files->create(
                    $fileDrive,
                    array(
                      'data' => file_get_contents($uploadfile),
                      'mimeType' => $_FILES['userFile']['type'][$i],
                      'uploadType' => 'multipart'
                    )
                );
                
                unlink($uploadfile);
            }
        }
        ?>
            <div class="row">
                <div class="col-sm-3 profile-pic">
                        <img src="<?=$user->picture ?>" />
                        <div class="padding-10">
                            <a class='logout' href='?logout'>Logout</a>
                        </div>

                </div>
                <div class="col-sm-6">
                        <h1><?=HtmlSpecialChars($user->givenName); ?> 
                                <span class="semi-bold"><?=HtmlSpecialChars($user->familyName); ?></span>
                                <br>
                                <small> <?=HtmlSpecialChars($user->hd); ?></small>
                        </h1>
                        <ul class="list-unstyled">
                                <li>
                                        <p class="text-muted">
                                                <i class="fa fa-envelope"></i>&nbsp;&nbsp;<a href="mailto:<?=HtmlSpecialChars($user->email); ?>">
                                                <?=HtmlSpecialChars($user->email); ?></a>
                                        </p>
                                </li>
                        </ul>
                </div>
                <div class="col-sm-12">
                    <div class="col-sm-12">

                            <div class="jarviswidget" id="wid-Upload" data-widget-colorbutton="false" data-widget-editbutton="false"  data-widget-deletebutton="false">
                                    <header>
                                        <h2><span class="widget-icon"> <i class="fa fa-upload"></i> </span>
                                            Upload </h2>
                                    </header>
                                    <div class="widget-body">
                                            <!--form id="dropzone-form" class="dropzone text-center ng-pristine ng-valid dz-clickable" 
                                                    enctype="multipart/form-data" method="post" style="border-width: 1px;">
                                                        <input type="hidden" id="code" name="code" value="<?=$_GET['code']?>" />
                                                    <p class="">Voc&ecirc; pode enviar arquivos sendo cada uma com no m&aacute;ximo 5MB.</p>
                                                    <hr>
                                            </form>
                                            <div class="row">
                                                    <div class="col-md-12 text-center">
                                                            <button id="submit" class="btn btn-default" type="button">
                                                                    <i class="fa fa-upload"></i>
                                                                    Upload
                                                            </button>
                                                    </div>
                                            </div-->
                                            <form method="POST" enctype="multipart/form-data" class="text-center"> 
                                                <input type="file" id="userFile" name="userFile[]" multiple="true" /><br>
                                                <button class="btn btn-primary" type="submit">
                                                        <i class="fa fa-upload"></i>
                                                        Enviar
                                                </button>
                                            </form>
                                    </div>
                            </div>

                            <br/><br/>
                    </div>

                    <ul class="inbox-download-list" style="position: relative">
                    <?php
                    foreach ($service->files->listFiles()->files as $file) { 
                            ?>
                            <div class="col-sm-2" style="box-sizing: content-box; white-space: normal;" title="<?=HtmlSpecialChars($file->name); ?>">
                                    <div class="well well-sm"  style="/*height: 100px;*/">
                                            <div class="row">
                                                <form method="POST" style="z-index:15;"> 
                                                        <input type="hidden" id="delete" name="delete" value="<?= $file->id ?>" />
                                                        <button class="btn btn-md btn-default pull-right" type="submit" style="padding:0px 4px;margin-right: 10px;">
                                                                <i class="fa fa-sm fa-times"></i>
                                                        </button>
                                                </form>

                                                <div class="col-xs-12 text-center">
                                                            <?php 
                                                            switch (explode('/', $file->mimeType)[0]){
                                                                    case 'text':
                                                                    echo '<i class="fa fa-2x fa-file-text-o"></i>';
                                                                    break;

                                                                    case 'image':
                                                                    echo '<i class="fa fa-2x fa-file-image-o"></i>';
                                                                    break;

                                                                    case 'audio':     
                                                                    echo '<i class="fa fa-2x fa-file-audio-o"></i>';
                                                                    break;

                                                                    default:
                                                                    if (!(strrpos($file->mimeType, "excel") === false) || !(strrpos($file->mimeType, "document.spreadsheet") === false)){
                                                                            echo '<i class="fa fa-2x fa-file-excel-o text-success"></i>';
                                                                    }
                                                                    elseif (!(strrpos($file->mimeType, "powerpoint") === false) || !(strrpos($file->mimeType, "document.presentation") === false)) {
                                                                            echo '<i class="fa fa-2x fa-file-powerpoint-o text-danger"></i>';
                                                                    }
                                                                    elseif (!(strrpos($file->mimeType, "msword") === false) || !(strrpos($file->mimeType, "document.word") === false)) {
                                                                            echo '<i class="fa fa-2x fa-file-word-o text-primary"></i>';
                                                                    }
                                                                    elseif (!(strrpos($file->mimeType, "application/javascript") === false)) {
                                                                            echo '<i class="fa fa-2x fa-file-code-o"></i>';
                                                                    }
                                                                    elseif (!(strrpos($file->mimeType, "application/pdf") === false)) {
                                                                            echo '<i class="fa fa-2x fa-file-pdf-o text-danger"></i>';
                                                                    }
                                                                    elseif (!(strrpos($file->mimeType, "application/x-httpd-php") === false)) {
                                                                            echo '<i class="fa fa-2x fa-file-code-o"></i>';
                                                                    }
                                                                    else{
                                                                            echo '<i class="fa fa-2x fa-file-o"></i>';                         
                                                                    }
                                                                    break;                         
                                                            }

                                                            ?>
                                                    <br />
                                                    <br />
                                                    </div>
                                               
                                                    <div class="col-xs-12 text-center" style="overflow: hidden;font-size:11px; ">                                    
                                                            <?=HtmlSpecialChars($file->name); ?>
                                                    </div>

                                                    <div class="col-xs-12 text-center">
                                                            <a href="https://drive.google.com/open?id=<?= $file->id ?>" target="_blank" title="Download"> <i class="fa fa-lg fa-download"></i></a>                                           
                                                    </div>
                                            </div>
                                            
                                    </div>
                                    
                            </div>
                            <?php    
                    }
                    ?>
                    </ul>
                </div>
            </div>
            <?php

    }
  
}
?>
                        </div>
                </div>
	</div>

	<!-- #PLUGINS -->
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script>
		if (!window.jQuery) {
			document.write('<script src="smart/jquery-2.1.1.min.js"><\/script>');
		}
	</script>

	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
	<script>
		if (!window.jQuery.ui) {
			document.write('<script src="smart/jquery-ui-1.10.3.min.js"><\/script>');
		}
	</script>
        
        <script src="http://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>

	<!-- IMPORTANT: APP CONFIG -->
	<script src="smart/app.config.js"></script>

	<!-- JS TOUCH : include this plugin for mobile drag / drop touch events-->
	<script src="smart/jquery.ui.touch-punch.min.js"></script> 

	<!-- BOOTSTRAP JS -->
	<script src="smart/bootstrap.min.js"></script>

	<!-- CUSTOM NOTIFICATION -->
	<script src="smart/SmartNotification.min.js"></script>

	<!-- JARVIS WIDGETS -->
	<script src="smart/jarvis.widget.min.js"></script>

	<!-- EASY PIE CHARTS -->
	<script src="smart/jquery.easy-pie-chart.min.js"></script>

	<!-- SPARKLINES -->
	<script src="smart/jquery.sparkline.min.js"></script>

	<!-- JQUERY VALIDATE -->
	<script src="smart/jquery.validate.min.js"></script>

	<!-- JQUERY MASKED INPUT -->
	<script src="smart/jquery.maskedinput.min.js"></script>

	<!-- JQUERY SELECT2 INPUT -->
	<script src="smart/select2.min.js"></script>

	<!-- JQUERY UI + Bootstrap Slider -->
	<script src="smart/bootstrap-slider.min.js"></script>

	<!-- browser msie issue fix -->
	<script src="smart/jquery.mb.browser.min.js"></script>

	<!-- FastClick: For mobile devices: you can disable this in app.js -->
	<script src="smart/fastclick.min.js"></script>

	<!-- Demo purpose only -->
	<script src="smart/demo.min.js"></script>

	<!-- MAIN APP JS FILE -->
	<script src="smart/app.min.js"></script>

	<!-- ENHANCEMENT PLUGINS : NOT A REQUIREMENT -->
	<!-- Voice command : plugin -->
	<script src="smart/voicecommand.min.js"></script>

	<!-- SmartChat UI : plugin -->
	<script src="smart/smart.chat.ui.min.js"></script>
	<script src="smart/smart.chat.manager.min.js"></script>

	<script src="smart/dropzone.js"></script>

	<script type="text/javascript">
		$('#submit').click(function() {
                    var myDropzone = Dropzone.forElement(".dropzone");   
                    myDropzone.processQueue();
		});
	</script>

	<!-- Your GOOGLE ANALYTICS CODE Below -->
	<script type="text/javascript">

		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-43548732-3']);
		_gaq.push(['_trackPageview']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();

	</script>
</body>
</html>
