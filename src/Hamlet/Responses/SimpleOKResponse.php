<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;
use Hamlet\Requests\Request;
use Hamlet\Writers\ResponseWriter;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Basic OK response with absolute minimum of headers
 */
class SimpleOKResponse extends Response
{
    public function __construct(Entity $entity)
    {
        parent::__construct(200);
        $this->withEntity($entity);
    }

    public function output(Request $request, CacheItemPoolInterface $cache, ResponseWriter $writer)
    {
        $writer->status($this->statusCode, $this->getStatusLine());

        $content = $this->entity->getContent();
        $writer->header('Content-Length', (string) strlen($content));
        $mediaType = $this->entity->getMediaType();
        if ($mediaType) {
            $writer->header('Content-Type', $mediaType);
        }
        $writer->write($content);
    }
}
