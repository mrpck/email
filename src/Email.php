<?php
namespace Mrpck\Email;

/**
 * The Email Class Library is a high level wrapper around the SmtpClient.
 * @author Michele Rosica <michelerosica@gmail.com>
 *
 */
class Email 
{
	var $host = '';

	/**
	* Constructor
	*/
    function __construct() 
	{

    }

	/**
	* destructor
	*/
    function __destruct() 
	{

    }

	function Send($from, $recipients, $subject, $body)
	{
		$to      = $recipients;
		$message = $body;
		$headers = 'From: webmaster@example.com'     . "\r\n" .
				   'Reply-To: webmaster@example.com' . "\r\n" .
				   'X-Mailer: PHP/' . phpversion();
	
		if(!mail($to, $subject, $message, $headers)) {
			echo 'Message could not be sent.';
			echo 'Mailer Error: ' . $mail->ErrorInfo;
		} else {
			echo 'Message has been sent';
		}

		return false;
	}

	function Send($from, $recipients, $subject, $body, $attachment)
	{
		$filename = basename($attachment);
		$mailto   = $recipients;
		$message  = $body;

		$content = file_get_contents($attachment);
		$content = chunk_split(base64_encode($content));

		// a random hash will be necessary to send mixed content
		$separator = md5(time());

		// carriage return type (RFC)
		$eol = "\r\n";

		// main header (multipart mandatory)
		$headers = "From: Privacy <no-reply@xtouch.it>" . $eol;
		$headers .= "MIME-Version: 1.0" . $eol;
		$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
		$headers .= "Content-Transfer-Encoding: 7bit" . $eol;
		$headers .= "This is a MIME encoded message." . $eol;

		// message
		$body = "--" . $separator . $eol;
		$body .= "Content-Type: text/plain; charset=\"iso-8859-1\"" . $eol;
		$body .= "Content-Transfer-Encoding: 8bit" . $eol;
		$body .= $message . $eol;

		// attachment
		$body .= "--" . $separator . $eol;
		$body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
		$body .= "Content-Transfer-Encoding: base64" . $eol;
		$body .= "Content-Disposition: attachment" . $eol;
		$body .= $content . $eol;
		$body .= "--" . $separator . "--";

		// SEND Mail
		if (@mail($mailto, $subject, $body, $headers)) {
			return true;
		}

		return false;
	}
}
