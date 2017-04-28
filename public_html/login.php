<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns = "http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>OTP LC HU Login</title>
		<style>	

			body {
				font-family: "Open Sans", "Arial", "Helvetica", sans-serif;
				font-size: 0.9em;
				margin: 25px;
			}

			h1 { 
				margin-top: 20px;
				font-size: 160%;
			}

			h2 { 
				font-size: 140%;
				color: #7D7D7A;
			}

			#login_button {
				position: 
				cursor: pointer;
				float: left;
				margin-top: 25px;"
			}
		</style>
	</head>

	<body>
		<h1>TED Open Translation Project</h1>
		<h2>Private Area<br>for Hungarian Language Coordinators</h2>
		<p>
			<b>Note:</b> Access is restrictred to language coordinators only
		</p>
		<p>
			<?php
				if (isset($auth_alert)) {
					echo $auth_alert;
				} else {
					echo '<a href="'. $loginUrl  .'"><img id="login_button" src="images/facebook.png" /></a>';
				}
			?>
		</p>
	</body>
</html>


