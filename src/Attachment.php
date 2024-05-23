<?php

namespace dcorreah\Postmark;

class Attachment
{
    /**
     * Name of the attachment.
     *
     * @var string
     */
    public $name;

    /**
     * The content id of the attachment.
     *
     * @var string
     */
    public $contentId;

    /**
     * The content type of the attachment.
     *
     * @var string
     */
    public $contentType;

    /**
     * The content length of the attachment.
     *
     * @var int
     */
    public $contentLength;

    /**
     * Base64 encoded content of the attachment.
     *
     * @var mixed
     */
    protected $content;

    /**
     * Create a new attachment.
     *
     * @param string $name
     * @param string $contentType
     * @param int $contentLength
     * @param mixed $content
     */
    public function __construct($name, $contentId, $contentType, $contentLength, $content)
    {
        $this->name = $name;
        $this->contentId = $contentId;
        $this->contentType = $contentType;
        $this->contentLength = $contentLength;
        $this->content = $content;
    }

    /**
     * base64 decoded content of the attachment.
     *
     * @return false|string
     */
    public function content(): bool|string
    {
        return base64_decode(chunk_split($this->content));
    }
}
