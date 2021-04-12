# paypal
The Email Class Library is a high level wrapper around the SmtpClient.

## Install
Copy the files under `src/` to your program

OR

```bash
composer require mrpck/email 1.0.0
```


## Usage

```php
use Mrpck\Email\Email;

$mail = (new Email())
    ->from('hello@example.com')
    ->to('you@example.com')
    ->cc('cc@example.com')
    //->bcc('bcc@example.com')
    //->replyTo('fabien@example.com')
    //->priority(Email::PRIORITY_HIGH)
    ->subject('Time for PHP Mailer!')
	//->attach(file_get_contents('test.pdf'), 'test.pdf')
	//->text('Sending emails is fun again!')
    ->html('<p>See Twig integration for better HTML integration!</p>');

if(!$mail->Send()) {
	echo 'Message could not be sent.';
	echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
	echo 'Message has been sent';
}
```
