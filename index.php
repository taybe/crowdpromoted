<?php require('includes/config.php');

//if logged in redirect to members page
if( $user->is_logged_in() ){ header('Location: memberpage.php'); }

//if form has been submitted process it
if(isset($_POST['submitRegister'])){

	//very basic validation
	if(strlen($_POST['username']) < 3){
		$error[] = 'Username is too short.';
	} else {
		$stmt = $db->prepare('SELECT username FROM members WHERE username = :username');
		$stmt->execute(array(':username' => $_POST['username']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if(!empty($row['username'])){
			$error[] = 'Username provided is already in use.';
		}

	}

	if(strlen($_POST['password']) < 3){
		$error[] = 'Password is too short.';
	}

	if(strlen($_POST['passwordConfirm']) < 3){
		$error[] = 'Confirm password is too short.';
	}

	if($_POST['password'] != $_POST['passwordConfirm']){
		$error[] = 'Passwords do not match.';
	}

	//email validation
	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
	    $error[] = 'Please enter a valid email address';
	} else {
		$stmt = $db->prepare('SELECT email FROM members WHERE email = :email');
		$stmt->execute(array(':email' => $_POST['email']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if(!empty($row['email'])){
			$error[] = 'Email provided is already in use.';
		}

	}


	//if no errors have been created carry on
	if(!isset($error)){

		//hash the password
		$hashedpassword = $user->password_hash($_POST['password'], PASSWORD_BCRYPT);

		//create the activasion code
		$activasion = md5(uniqid(rand(),true));
		try {

			//insert into database with a prepared statement
			$stmt = $db->prepare('INSERT INTO members (username,password,email,active) VALUES (:username, :password, :email, :active)');
			$stmt->execute(array(
				':username' => $_POST['username'],
				':password' => $hashedpassword,
				':email' => $_POST['email'],
				':active' => $activasion
			));
			$id = $db->lastInsertId('memberID');

			//send email
			$to = $_POST['email'];
			$subject = "Registration Confirmation";
			$body = "<p>Thank you for registering at our site.</p>
			\n<p>If you have received this e-mail by mistake, click this link: <a href='".DIR."deactivate.php?x=$id&y=$activasion'>".DIR."deactivate.php?x=$id&y=$activasion</a></p>
			\n<p>Regards Site Admin</p>";
			$bodyAlt = "Thank you for registering at our site.";
			$mail = new PHPMailer;
			$mail->isSMTP();                                      // Set mailer to use SMTP
			$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
			$mail->SMTPAuth = true;                               // Enable SMTP authentication
			$mail->Username = 'taybemuharem@gmail.com';                 // SMTP username
			$mail->Password = 'embieMINE1986';                           // SMTP password
			$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
			$mail->Port = 465;                                    // TCP port to connect to

			$mail->setFrom(SITEEMAIL, 'Crowd Promoted');
			$mail->addAddress($to);     // Add a recipient
			//$mail->addAddress('ellen@example.com');               // Name is optional
			//$mail->addReplyTo('info@example.com', 'Information');
			//$mail->addCC('cc@example.com');
			//$mail->addBCC('bcc@example.com');

			//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
			//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
			$mail->isHTML(true);                                  // Set email format to HTML

			$mail->Subject = $subject;
			$mail->Body    = $body;
			$mail->AltBody = $bodyAlt;
			
			if(!$mail->send()) {
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			} else {
				echo 'Message has been sent';
			}
			$username = $_POST['username'];
			$password = $_POST['password'];
			//redirect to index page
			if(isset($_POST['bandConfirm'])) {
				if($user->login($username,$password)){ 
					$_SESSION['username'] = $username;
					header('Location: bandCreation.php?action=joined');
					exit;
				
				} else {
					$errorLogin[] = 'Wrong username or password.';
				}
				// Checkbox is selected
			} else {
				if($user->login($username,$password)){ 
					$_SESSION['username'] = $username;
					header('Location: memberpage.php?action=joined');
					exit;
				
				} else {
					$errorLogin[] = 'Wrong username or password.';
				}

			   // Alternate code
			}
			

		//else catch the exception and show the error.
		} catch(PDOException $e) {
		    $error[] = $e->getMessage();
		}

	}

}
//process login form if submitted
if(isset($_POST['submitLogin'])){

	$username = $_POST['username'];
	$password = $_POST['password'];
	
	if($user->login($username,$password)){ 
		$_SESSION['username'] = $username;
		header('Location: memberpage.php');
		exit;
	
	} else {
		$errorLogin[] = 'Wrong username or password';
	}

}//end if submit

//define page title
$title = 'Crowd Promoted - Login or Register';

//include header template
require('layout/header-loggedOut.php');
?>



	<div class="row">
		<div class="col-xs-12 col-sm-8 col-md-6">
			<div class="vertically-centered">
				<form role="form" method="post" autocomplete="off">
					<p>New to Crowd Promoted?</p>
					<h2>Create Account</h2>
					<?php
					//check for any errors
					if(isset($error)){
						foreach($error as $error){
							echo '<p class="bg-danger">'.$error.'</p>';
						}
					}
					?>

					<div class="form-group">
						<input type="text" name="username" id="username" class="form-control input" placeholder="User Name" value="<?php if(isset($error)){ echo $_POST['username']; } ?>" tabindex="1">
					</div>
					<div class="form-group">
						<input type="email" name="email" id="email" class="form-control input" placeholder="Email Address" value="<?php if(isset($error)){ echo $_POST['email']; } ?>" tabindex="2">
					</div>
					<div class="form-group">
						<input type="password" name="password" id="password" class="form-control input" placeholder="Password" tabindex="3">
					</div>
					<div class="form-group">
						<input type="password" name="passwordConfirm" id="passwordConfirm" class="form-control input" placeholder="Confirm Password" tabindex="4">
					</div>
					<div class="row">
						<div class="col-xs-6 col-sm-6 col-md-6 text-left">
							<input type="checkbox" name="bandConfirm" id="bandConfirm" tabindex="5">
							<label for="bandConfirm">I am in a band</label>
						</div>
					</div>
					<hr>
					<div class="form-group">
						<input type="submit" name="submitRegister" value="Register" class="btn btn-primary btn-block btn-lg" tabindex="6">
					</div>
				</form>
			</div>
		</div>
		<div class="col-xs-12 col-sm-8 col-md-6">
			<div class="vertically-centered">
				<form role="form" method="post" action="" autocomplete="off">
					<p>Already a member?</p><h2>Login to your account</h2>
					<?php
					//check for any errors
					if(isset($errorLogin)){
						foreach($errorLogin as $errorLogin){
							echo '<p class="bg-danger">'.$errorLogin.'</p>';
						}
					}

					if(isset($_GET['action'])){

						//check the action
						switch ($_GET['action']) {
							case 'active':
								echo "<h2 class='bg-success'>Your account is now active you may now log in.</h2>";
								break;
							case 'reset':
								echo "<h2 class='bg-success'>Please check your inbox for a reset link.</h2>";
								break;
							case 'resetAccount':
								echo "<h2 class='bg-success'>Password changed, you may now login.</h2>";
								break;
						}

					}

						
					?>
					<div class="form-group">
						<input type="text" name="username" id="username" class="form-control" placeholder="User Name" value="<?php if(isset($error)){ echo $_POST['username']; } ?>" tabindex="7">
					</div>

					<div class="form-group">
						<input type="password" name="password" id="passwordLogin" class="form-control" placeholder="Password" tabindex="8">
					</div>
						
					<div class="row">
						<div class="col-xs-6 col-sm-6 col-md-6">
							 <a href='reset.php'>Forgot your Password?</a>
						</div>
					</div>
						
					<hr>
					<div class="form-group">
						<input type="submit" name="submitLogin" value="Login" class="btn btn-primary btn-block btn-lg" tabindex="9">
					</div>
				</form>
			</div>
		</div>
	</div>


</div>

<?php
//include header template
require('layout/footer-loggedOut.php');
?>
