<?php

namespace dcorreah\Postmark;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use dcorreah\Postmark\Support\PostmarkDate;

/**
 * API to process Postmark Inbound Webhooks.
 *
 * @property-read Contact $from
 * @property-read Collection $attachments
 * @property-read Collection $bcc
 * @property-read Collection $cc
 * @property-read Collection $headers
 * @property-read Collection $to
 * @property-read PostmarkDate $date
 * @property-read bool $isSpam
 * @property-read string $htmlBody
 * @property-read string $mailboxHash
 * @property-read string $messageId MessageID assigned by Postmark.
 * @property-read string $messageIdFromHeaders Message-ID value from headers.
 * @property-read string $originalDate
 * @property-read string $originalRecipient
 * @property-read string $replyTo
 * @property-read float $spamScore
 * @property-read string $spamStatus
 * @property-read string $strippedTextReply
 * @property-read string $subject
 * @property-read string $tag
 * @property-read string $textBody
 * @property-read string $timezone
 */
class InboundMessage
{
    /**
     * Collection of the json data.
     *
     * @var Collection
     */
    protected $data;

    /*
     * DateTime when the message was reveived,
     *
     * @var \dcorreah\Postmark\Support\PostmarkDate
     */
    protected $datetime;

    /**
     * Create a new InboundMessage instance.
     *
     * @param mixed $json
     * @throws InvalidArgumentException
     */
    public function __construct($json = null)
    {
        $this->data = collect(json_decode($json, true));

        if ((json_last_error() !== JSON_ERROR_NONE)) {
            throw new InvalidArgumentException('You must provide a valid JSON source.');
        }

        $this->datetime = PostmarkDate::parse($this->data->get('Date'));
    }

    /**
     * Retrieve the collecion of attachments.
     *
     * @return Collection
     */
    public function getAttachmentsAttribute(): Collection
    {
        return collect($this->data->get('Attachments', []))
            ->map(function ($data) {
                $attachment = collect($data);

                return new Attachment(
                    $attachment->get('Name'),
                    $attachment->get('ContentID'),
                    $attachment->get('ContentType'),
                    $attachment->get('ContentLength'),
                    $attachment->get('Content')
                );
            });
    }

    /**
     * Retrieve the collecion of bcc recipients.
     *
     * @return Collection
     */
    public function getBccAttribute()
    {
        return $this->parseContacts($this->data->get('BccFull'));
    }

    /**
     * Parse contacts and return a collection of contacts.
     *
     * @param array $contacts
     * @return Collection
     */
    protected function parseContacts($contacts = [])
    {
        return collect($contacts)
            ->map(function ($contact) {
                $contact = collect($contact);

                return new Contact($contact->get('Name'), $contact->get('Email'), $contact->get('MailboxHash'));
            });
    }

    /**
     * Retrieve the collecion of cc recipients.
     *
     * @return Collection
     */
    public function getCcAttribute()
    {
        return $this->parseContacts($this->data->get('CcFull'));
    }

    /**
     * Retrieve the from contact.
     *
     * @return \Mvdnbrk\Postmark\Contact
     */
    public function getFromAttribute()
    {
        return $this->parseContacts([
            $this->data->get('FromFull'),
        ])->first();
    }

    /**
     * Retrieve the collecion of recipient contacts.
     *
     * @return Collection
     */
    public function getToAttribute()
    {
        return $this->parseContacts($this->data->get('ToFull'));
    }

    /**
     * Retrieve the timezone from the message.
     *
     * @return string
     */
    public function getTimezoneAttribute()
    {
        return $this->datetime->getTimezone()->getName();
    }

    /**
     * Retrieve the UTC date from the message.
     *
     * @return PostmarkDate
     */
    public function getDateAttribute()
    {
        return $this->datetime;
    }

    /**
     * Determines if the message is to be considered as spam.
     *
     * @return bool
     */
    public function getIsSpamAttribute()
    {
        return ucfirst($this->spamStatus) === 'Yes';
    }

    /**
     * Retrieve the collecion of headers.
     *
     * @return Collection
     */
    public function getHeadersAttribute()
    {
        return collect($this->data->get('Headers', []))
            ->mapWithKeys(function ($header) {
                return [$header['Name'] => $header['Value']];
            });
    }

    /**
     * Retrieve the MessageID.
     *
     * @return string
     */
    public function getMessageIdAttribute()
    {
        return $this->data->get('MessageID');
    }

    /**
     * Retrieve the Message-ID value from the headers.
     *
     * @return string
     */
    public function getMessageIdFromHeadersAttribute()
    {
        return $this->headers->mapWithKeys(function ($item, $key) {
            return [strtolower($key) => $item];
        })->get('message-id');
    }

    /**
     * Retrieve the original date value from the message.
     *
     * @return string
     */
    public function getOriginalDateAttribute()
    {
        return $this->data->get('Date');
    }

    /**
     * Retrieve spam score value from the message.
     *
     * @return float
     */
    public function getSpamScoreAttribute()
    {
        return (float)$this->headers->get('X-Spam-Score', '0.0');
    }

    /**
     * Retrieve spam status value from the message.
     *
     * @return float
     */
    public function getSpamStatusAttribute()
    {
        return $this->headers->get('X-Spam-Status', 'No');
    }

    /**
     * Dynamically retrieve attributes on the data model.
     *
     * @param string $key
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __get($key)
    {
        $key = ucfirst($key);

        if (method_exists($this, 'get' . $key . 'Attribute')) {
            return $this->{'get' . $key . 'Attribute'}();
        }

        if ($this->data->has($key)) {
            return $this->data->get($key);
        }

        throw new InvalidArgumentException(sprintf("Unknown getter '%s'", $key));
    }
}
