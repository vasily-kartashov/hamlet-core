<?php

namespace Hamlet\Requests;

interface Builder
{
    public function withHeader(string $name, string $value): Builder;

    public function withQueryParameter(string $name, string $value): Builder;

    public function withParameter(string $name, string $value): Builder;

    public function withBody(string $body): Builder;

    public function withSessionParameter(string $name, string $value): Builder;

    public function withCookie(string $name, string $value): Builder;

    public function withFile(string $name, array $value): Builder;

    public function withServerParameter(string $name, string $value): Builder;

    public function build(): Request;

    public function withMethod(string $method): Builder;

    public function withUri(string $uri): Builder;
}