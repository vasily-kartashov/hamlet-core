<?php

namespace Hamlet\Entities;

use Psr\Http\Message\StreamInterface;

class StreamEntity extends AbstractEntity
{
    private $stream;
    private $content;

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
        return $this->content;
    }

    public function getKey(): string
    {
        return md5($this->getContent());
    }

    public function getMediaType()
    {
        return null;
    }
}