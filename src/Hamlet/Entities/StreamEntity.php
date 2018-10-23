<?php

namespace Hamlet\Entities;

use Psr\Http\Message\StreamInterface;

class StreamEntity extends AbstractEntity
{
    /** @var StreamInterface */
    private $stream;

    /** @var string|null */
    private $content = null;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function getContent(): string
    {
        if (!isset($this->content)) {
            $this->stream->rewind();
            $this->content = $this->stream->getContents();
        }
        return $this->content ?? '';
    }

    public function getKey(): string
    {
        return \md5($this->getContent());
    }

    /**
     * Get media type
     * @return string|null
     */
    public function getMediaType()
    {
        return null;
    }
}
