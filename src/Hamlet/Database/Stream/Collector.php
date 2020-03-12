<?php

namespace Hamlet\Database\Stream;

use Hamlet\Cast\Type;

/**
 * @template K as array-key
 * @template V
 */
class Collector
{
    /**
     * @var callable
     * @psalm-var callable():array<array{0:K,1:V}>
     */
    protected $generator;

    /**
     * @var Type|null
     */
    protected $keyType = null;

    /**
     * @var Type|null
     */
    protected $valueType = null;

    /**
     * @var callable|null
     * @psalm-var (callable(mixed,mixed):bool)|null
     */
    protected $assertion = null;

    /**
     * @var bool
     */
    protected $validationEnabled = false;

    /**
     * @param callable $generator
     * @psalm-param @psalm-var callable():array<array{0:K,1:V}> $generator
     */
    public function __construct(callable $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return array
     * @psalm-return array<K,V>
     */
    public function collectAll(): array
    {
        $result = [];
        foreach (($this->generator)() as list($key, $value)) {
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @return mixed
     * @psalm-return V|null
     */
    public function collectHead()
    {
        foreach (($this->generator)() as list($key, $value)) {
            if ($this->validationEnabled) {
                $this->validate($key, $value);
            }
            return $value;
        }
        return null;
    }

    /**
     * @param callable $callable
     * @psalm-param callable(V):void $callable
     * @return void
     */
    public function forEach(callable $callable)
    {
        foreach (($this->generator)() as list($key, $value)) {
            if ($this->validationEnabled) {
                $this->validate($key, $value);
            }
            ($callable)($value);
        }
    }

    /**
     * @param callable $callable
     * @psalm-param callable(K,V):void $callable
     * @return void
     */
    public function forEachWithIndex(callable $callable)
    {
        foreach (($this->generator)() as list($key, $value)) {
            if ($this->validationEnabled) {
                $this->validate($key, $value);
            }
            ($callable)($key, $value);
        }
    }

    /**
     * @template K1 as array-key
     * @template V1
     * @param Type $keyType
     * @psalm-param Type<K1> $keyType
     * @param Type $valueType
     * @psalm-param Type<V1> $valueType
     * @return self
     * @psalm-return Collector<K1,V1>
     * @psalm-suppress MixedTypeCoercion
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function assertType(Type $keyType, Type $valueType)
    {
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->validationEnabled = true;
        return $this;
    }

    /**
     * @param callable $callback
     * @psalm-param callable(mixed,mixed):bool $callback
     * @return self
     */
    public function assertForEach(callable $callback): self
    {
        $this->assertion = $callback;
        $this->validationEnabled = true;
        return $this;
    }

    /**
     * @param mixed $key
     * @psalm-param K $key
     * @param mixed $value
     * @psalm-param V $value
     * @return void
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    private function validate($key, $value)
    {
        if ($this->keyType) {
            $this->keyType->assert($key);
        }
        if ($this->valueType) {
            $this->valueType->assert($value);
        }
        if ($this->assertion) {
            assert(($this->assertion)($key, $value));
        }
    }
}
