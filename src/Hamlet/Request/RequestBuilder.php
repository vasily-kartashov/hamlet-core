<?php

namespace Hamlet\Request;

class RequestBuilder
{
    /** @var string[]  */
    protected $cookies = [];

    /** @var string */
    protected $environmentName = 'localhost';

    /** @var string[] */
    protected $headers = [];

    /** @var string */
    protected $ip = '127.0.0.1';

    /** @var string */
    protected $method = 'GET';

    /** @var string */
    protected $path = '/';

    /** @var  string[] */
    protected $parameters = [];

    /** @var string[] */
    protected $sessionParameters = [];

    /**
     * Set request path
     *
     * @param string $path
     *
     * @return \Hamlet\Request\RequestBuilder
     */
    public function setPath($path)
    {
        $questionMarkPosition = strpos($path, '?');
        if ($questionMarkPosition === false) {
            $this->path = urldecode($path);
        } else {
            $this->path = urldecode(substr($path, 0, $questionMarkPosition));
            parse_str(substr($path, $questionMarkPosition + 1), $parameters);
            $this->setParameters($parameters);
        }
        return $this;
    }

    /**
     * Add parameter to request
     *
     * @param string $name
     * @param string $value
     *
     * @return \Hamlet\Request\RequestBuilder
     */
    public function setParameter($name, $value)
    {
        assert(is_string($name));
        assert(is_string($value));
        $this->parameters[(string) $name] = (string) $value;
        return $this;
    }

    /**
     * Add parameters to request
     *
     * @param string[] $parameters
     *
     * @return \Hamlet\Request\RequestBuilder
     */
    public function setParameters($parameters)
    {
        $this->parameters += $parameters;
        return $this;
    }

    /**
     * Create request object
     *
     * @return \Hamlet\Request\RequestInterface
     */
    public function getRequest()
    {
        return new Request($this->method, $this->path, $this->environmentName, $this->ip, $this->headers,
            $this->parameters, $this->sessionParameters, $this->cookies);
    }
}