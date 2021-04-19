<?php
namespace Mrpck\Email;

/**
 * The Email Class Library is a high level wrapper around the SmtpClient.
 * @author Michele Rosica <michelerosica@gmail.com>
 *
 */
class Email 
{
	public const PRIORITY_HIGHEST = 1;
    public const PRIORITY_HIGH   = 2;
    public const PRIORITY_NORMAL = 3;
    public const PRIORITY_LOW    = 4;
    public const PRIORITY_LOWEST = 5;

    private const PRIORITY_MAP = [
        self::PRIORITY_HIGHEST => 'Highest',
        self::PRIORITY_HIGH   => 'High',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_LOW    => 'Low',
        self::PRIORITY_LOWEST => 'Lowest',
    ];

    private $text;
    private $textCharset;
    private $html;
    private $htmlCharset;
    private $attachments = [];
	
	var $ErrorInfo = '';
	//private $host = "ssl://smtp.example.com";
	//private $username = "youremail@example.com";
	//private $password = "your email password";
	//private $port = 25;
	private $from;
	private $recipients = [];
	private $subject;

	/**
	* Constructor
	*/
    function __construct() 
	{

    }

	/**
	* Destructor
	*/
    function __destruct() 
	{

    }

	public function Send($from = null, $recipients = null, $subject = null, $body = null, $attachments = null, $headers = null)
	{
		if (empty($this->from))
			$this->from = $from;
		if (empty($this->recipients))
			$this->recipients = $recipients;
		if (empty($this->subject))
			$this->subject = $subject;
		if (empty($this->html))
			$this->html = $body;
		if (empty($this->html) && !empty($this->text))
			$this->html = $this->text;
		if (empty($this->attachments) && !empty($attachments)) {
			if (is_array($attachments))
				$this->attachments = $attachments;
			else
				$this->attach($attachments);
		}

		if (is_array($this->recipients)) {
			$this->recipients = implode(',', $this->recipients);
			//$cc = implode(',', $this->recipients);
			//$bcc = implode(',', $this->recipients);
		}

		if (empty($headers) && empty($this->attachments)) {
			// carriage return type (RFC)
			$eol = "\r\n";

			// Create email headers
			$headers = 'From: ' . strip_tags($this->from) . $eol . // Sender Email
					   'Reply-To: ' . strip_tags($this->from) . $eol . // Email addrress to reach back
					   'X-Mailer: PHP/' . phpversion();
		}

		if (!empty($this->attachments))
			return $this->SendAttach($this->from, $this->recipients, $this->subject, $this->html, $this->attachments, $headers);

		// Sending email
		if(@mail($this->recipients, $this->subject, $this->html, $headers)) {
			return true;
		} else
			$this->ErrorInfo = json_encode(error_get_last());

		return false;
	}

	private function SendAttach($from, $recipients, $subject, $message, $attachments, $headers = null)
	{
		$attach = $attachments[0];
		$filename = $attach['name']; //basename($attachment);
		$content  = $attach['body']; //file_get_contents($attachment);
		$content  = chunk_split(base64_encode($content));

		// a random hash will be necessary to send mixed content
		$separator = md5(time());

		// carriage return type (RFC)
		$eol = "\r\n";

		// main header (multipart mandatory)
		if (empty($headers)) {
			$headers = "From: " . strip_tags($from) . $eol; // Sender Email
			//$headers .= "Reply-To: ".$reply_to_email."\r\n"; // Email addrress to reach back
			$headers .= "MIME-Version: 1.0" . $eol;
			$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
			$headers .= "Content-Transfer-Encoding: 7bit" . $eol;
			$headers .= "This is a MIME encoded message." . $eol;
		}

		// message
		$body = "--" . $separator . $eol;
		if (!empty($this->getTextBody()))
			$body .= "Content-Type: text/plain; charset=\"iso-8859-1\"" . $eol;
		else
			$body .= "Content-Type: text/html; charset=\"iso-8859-1\"" . $eol;
		//$body .= "Content-Transfer-Encoding: 8bit" . $eol;
		//$body .= $message . $eol;
		$body .= "Content-Transfer-Encoding: base64"  . $eol.$eol; 
		$body .= chunk_split(base64_encode($message)) . $eol; 

		// attachment
		$body .= "--" . $separator . $eol;
		$body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
		$body .= "Content-Transfer-Encoding: base64" . $eol;
		$body .= "Content-Disposition: attachment" . $eol;
		$body .= $content . $eol;
		$body .= "--" . $separator . "--";

		// Sending email
		if (@mail($recipients, $subject, $body, $headers)) {
			return true;
		} else
			$this->ErrorInfo = json_encode(error_get_last());

		return false;
	}

    /**
     * @return $this
     */
    public function subject(string $subject)
    {
		$this->subject = $subject;
        return $this->setHeaderBody('Text', 'Subject', $subject);
    }

    public function getSubject(): ?string
    {
        return $this->getHeaders()->getHeaderBody('Subject');
    }

    /**
     * @return $this
     */
    public function date(\DateTimeInterface $dateTime)
    {
        return $this->setHeaderBody('Date', 'Date', $dateTime);
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->getHeaders()->getHeaderBody('Date');
    }

    /**
     * @param Address|string $address
     *
     * @return $this
     */
    public function returnPath($address)
    {
        return $this->setHeaderBody('Path', 'Return-Path', Address::create($address));
    }

    public function getReturnPath(): ?Address
    {
        return $this->getHeaders()->getHeaderBody('Return-Path');
    }

    /**
     * @param Address|string $address
     *
     * @return $this
     */
    public function sender($address)
    {
        return $this->setHeaderBody('Mailbox', 'Sender', Address::create($address));
    }

    public function getSender(): ?Address
    {
        return $this->getHeaders()->getHeaderBody('Sender');
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function addFrom(...$addresses)
    {
        return $this->addListAddressHeaderBody('From', $addresses);
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function from(...$addresses)
    {
		$this->from = $addresses[0];
        return $this->setListAddressHeaderBody('From', $addresses);
    }

    /**
     * @return Address[]
     */
    public function getFrom(): array
    {
        return $this->getHeaders()->getHeaderBody('From') ?: [];
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function addReplyTo(...$addresses)
    {
        return $this->addListAddressHeaderBody('Reply-To', $addresses);
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function replyTo(...$addresses)
    {
        return $this->setListAddressHeaderBody('Reply-To', $addresses);
    }

    /**
     * @return Address[]
     */
    public function getReplyTo(): array
    {
        return $this->getHeaders()->getHeaderBody('Reply-To') ?: [];
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function addTo(...$addresses)
    {
        return $this->addListAddressHeaderBody('To', $addresses);
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function to(...$addresses)
    {
		$this->recipients = $addresses;
        return $this->setListAddressHeaderBody('To', $addresses);
    }

    /**
     * @return Address[]
     */
    public function getTo(): array
    {
        return $this->getHeaders()->getHeaderBody('To') ?: [];
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function addCc(...$addresses)
    {
        return $this->addListAddressHeaderBody('Cc', $addresses);
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function cc(...$addresses)
    {
        return $this->setListAddressHeaderBody('Cc', $addresses);
    }

    /**
     * @return Address[]
     */
    public function getCc(): array
    {
        return $this->getHeaders()->getHeaderBody('Cc') ?: [];
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function addBcc(...$addresses)
    {
        return $this->addListAddressHeaderBody('Bcc', $addresses);
    }

    /**
     * @param Address|string ...$addresses
     *
     * @return $this
     */
    public function bcc(...$addresses)
    {
        return $this->setListAddressHeaderBody('Bcc', $addresses);
    }

    /**
     * @return Address[]
     */
    public function getBcc(): array
    {
        return $this->getHeaders()->getHeaderBody('Bcc') ?: [];
    }

    /**
     * Sets the priority of this message.
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @return $this
     */
    public function priority(int $priority)
    {
        if ($priority > 5) {
            $priority = 5;
        } elseif ($priority < 1) {
            $priority = 1;
        }

        return $this->setHeaderBody('Text', 'X-Priority', sprintf('%d (%s)', $priority, self::PRIORITY_MAP[$priority]));
    }

    /**
     * Get the priority of this message.
     *
     * The returned value is an integer where 1 is the highest priority and 5
     * is the lowest.
     */
    public function getPriority(): int
    {
        [$priority] = sscanf($this->getHeaders()->getHeaderBody('X-Priority'), '%[1-5]');

        return $priority ?? 3;
    }

    /**
     * @param resource|string $body
     *
     * @return $this
     */
    public function text($body, string $charset = 'utf-8')
    {
        $this->text = $body;
        $this->textCharset = $charset;

        return $this;
    }

    /**
     * @return resource|string|null
     */
    public function getTextBody()
    {
        return $this->text;
    }

    public function getTextCharset(): ?string
    {
        return $this->textCharset;
    }

    /**
     * @param resource|string|null $body
     *
     * @return $this
     */
    public function html($body, string $charset = 'utf-8')
    {
        $this->html = $body;
        $this->htmlCharset = $charset;

        return $this;
    }

    /**
     * @return resource|string|null
     */
    public function getHtmlBody()
    {
        return $this->html;
    }

    public function getHtmlCharset(): ?string
    {
        return $this->htmlCharset;
    }

    /**
     * @param resource|string $body
     *
     * @return $this
     */
    public function attach($body, string $name = null, string $contentType = null)
    {
        $this->attachments[] = ['body' => $body, 'name' => $name, 'content-type' => $contentType, 'inline' => false];

        return $this;
    }

    /**
     * @return $this
     */
    public function attachFromPath(string $path, string $name = null, string $contentType = null)
    {
        $this->attachments[] = ['path' => $path, 'name' => $name, 'content-type' => $contentType, 'inline' => false];

        return $this;
    }

    /**
     * @param resource|string $body
     *
     * @return $this
     */
    public function embed($body, string $name = null, string $contentType = null)
    {
        $this->attachments[] = ['body' => $body, 'name' => $name, 'content-type' => $contentType, 'inline' => true];

        return $this;
    }

    /**
     * @return $this
     */
    public function embedFromPath(string $path, string $name = null, string $contentType = null)
    {
        $this->attachments[] = ['path' => $path, 'name' => $name, 'content-type' => $contentType, 'inline' => true];

        return $this;
    }

    /**
     * @return $this
     */
    public function attachPart(DataPart $part)
    {
        $this->attachments[] = ['part' => $part];

        return $this;
    }

    /**
     * @return array|DataPart[]
     */
    public function getAttachments(): array
    {
        $parts = [];
        foreach ($this->attachments as $attachment) {
            $parts[] = $this->createDataPart($attachment);
        }

        return $parts;
    }

    public function getBody(): AbstractPart
    {
        return $this->generateBody();
    }

    public function ensureValidity()
    {
        if (null === $this->text && null === $this->html && !$this->attachments) {
            throw new LogicException('A message must have a text or an HTML part or attachments.');
        }
    }

    /**
     * Generates an AbstractPart based on the raw body of a message.
	 *
     */
    private function generateBody(): AbstractPart
    {
        $this->ensureValidity();

        [$htmlPart, $attachmentParts, $inlineParts] = $this->prepareParts();

        $part = null === $this->text ? null : new TextPart($this->text, $this->textCharset);
        if (null !== $htmlPart) {
            if (null !== $part) {
                $part = new AlternativePart($part, $htmlPart);
            } else {
                $part = $htmlPart;
            }
        }

        if ($inlineParts) {
            $part = new RelatedPart($part, ...$inlineParts);
        }

        if ($attachmentParts) {
            if ($part) {
                $part = new MixedPart($part, ...$attachmentParts);
            } else {
                $part = new MixedPart(...$attachmentParts);
            }
        }

        return $part;
    }

    private function prepareParts(): ?array
    {
        $names = [];
        $htmlPart = null;
        $html = $this->html;
        if (null !== $this->html) {
            $htmlPart = new TextPart($html, $this->htmlCharset, 'html');
            $html = $htmlPart->getBody();
            preg_match_all('(<img\s+[^>]*src\s*=\s*(?:([\'"])cid:([^"]+)\\1|cid:([^>\s]+)))i', $html, $names);
            $names = array_filter(array_unique(array_merge($names[2], $names[3])));
        }

        $attachmentParts = $inlineParts = [];
        foreach ($this->attachments as $attachment) {
            foreach ($names as $name) {
                if (isset($attachment['part'])) {
                    continue;
                }
                if ($name !== $attachment['name']) {
                    continue;
                }
                if (isset($inlineParts[$name])) {
                    continue 2;
                }
                $attachment['inline'] = true;
                $inlineParts[$name] = $part = $this->createDataPart($attachment);
                $html = str_replace('cid:'.$name, 'cid:'.$part->getContentId(), $html);
                $part->setName($part->getContentId());
                continue 2;
            }
            $attachmentParts[] = $this->createDataPart($attachment);
        }
        if (null !== $htmlPart) {
            $htmlPart = new TextPart($html, $this->htmlCharset, 'html');
        }

        return [$htmlPart, $attachmentParts, array_values($inlineParts)];
    }

    private function createDataPart(array $attachment): DataPart
    {
        if (isset($attachment['part'])) {
            return $attachment['part'];
        }

        if (isset($attachment['body'])) {
            $part = new DataPart($attachment['body'], $attachment['name'] ?? null, $attachment['content-type'] ?? null);
        } else {
            $part = DataPart::fromPath($attachment['path'] ?? '', $attachment['name'] ?? null, $attachment['content-type'] ?? null);
        }
        if ($attachment['inline']) {
            $part->asInline();
        }

        return $part;
    }

    /**
     * @return $this
     */
    private function setHeaderBody(string $type, string $name, $body): object
    {
        //$this->getHeaders()->setHeaderBody($type, $name, $body);

        return $this;
    }

    private function addListAddressHeaderBody(string $name, array $addresses)
    {
        return $this;
    }

    private function setListAddressHeaderBody(string $name, array $addresses)
    {
        return $this;
    }
}
