<?php
namespace noelbosscom;

class Helpers {

	/**
	 * Grabs the mails from config or returns false
	 *
	 * @return string
	 * @author Noël Bossart
	 */
	public function getMails($conf) {
		$nomail = false;
		$to = '';

		if(!isset($conf->mails)){
			$nomail = true;
		} else {
			$mails = (array) $conf->mails;

			if(count($mails) > 0){
				foreach ($mails as $mail => $name) {
					if(filter_var($mail, FILTER_VALIDATE_EMAIL)){
						$to .= "$name <$mail>,";
					}
				}
			}
		}

		// final check if we got mail addresses...
		if(strpos($to,'@') === false || $nomail){
			return false;
		}
		$to = substr($to, 0, -1);
		return $to;
	}

	public function sendMail($to, $subject, $message, $conf) {
		if (!$to) return;

		$from = [
				'name' => 'Deepl.io',
				'email' => 'noreply@deepl.io',
			];

		// echo $to;exit;
		if ($conf->smtp->enabled !== true) {
			$headers = "From: " . $from['name'] . " <" . strip_tags($from['email']) . "> \r\n";
			//$headers .= "Reply-To: ". $to . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

			mail($to, $subject, $message, $headers);
			return;
		}


		$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
		try {
			$mail->CharSet = 'utf-8';
			$mail->XMailer = 'PHP/' . phpversion();

			// $mail->SMTPDebug = 2;                                 // Enable verbose debug output
			$mail->isSMTP(); // Set mailer to use SMTP

			# Server settings
			$mail->Host = $conf->smtp->server;  // Specify main and backup SMTP servers
			if ($conf->smtp->port && is_int($conf->smtp->port)) {
				$mail->Port = (int)$conf->smtp->port; // TCP port to connect to
			}
			if ($conf->smtp->secure) {
				$mail->SMTPSecure = $conf->smtp->secure; // Enable TLS encryption, `ssl` also accepted
			}

			if ($conf->smtp->auth === true) {
				$mail->SMTPAuth = true;                               // Enable SMTP authentication
				$mail->Username = $conf->smtp->username;                 // SMTP username
				$mail->Password = $conf->smtp->password;                           // SMTP password
			}

			# Sender
			$mail->setFrom($from['email'], $from['name']);

			# Recipients
			$_to_arr = explode(',', $to);
			foreach($_to_arr as $item) {
				if (preg_match('/^(.+\s)<(.*)>$/', trim($item), $matches)) {
					echo 'Add: '. $matches[2];
					$mail->addAddress($matches[2], $matches[1]);
				}
			}

			# Content
			$mail->isHTML(true);                                  // Set email format to HTML
			$mail->Subject = $subject;
			$mail->Body    = $message;


			# Sendmail
			$mail->send();

		} catch (\Exception $e) {
			echo $mail->ErrorInfo;
			return [
					'error' => $mail->ErrorInfo
				];
		}

		return;
	}
}