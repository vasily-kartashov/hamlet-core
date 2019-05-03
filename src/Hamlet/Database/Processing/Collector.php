<?php

namespace Hamlet\Database\Processing;

use Hamlet\Cast\Type;

/**
 * @template K as array-key
 * @template V
 */
class Collector
{
    /**
     * @var array
     * @psalm-var array<K,V>
     */
    protected $records;

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
     * @param array $records
     * @psalm-param array<K,V> $records
     */
    public function __construct(array $records)
    {
        $this->records = $records;
    }

    /**
     * @return array
     * @psalm-return array<K,V>
     */
    public function collectAll(): array
    {
        if ($this->validationEnabled) {
            foreach ($this->records as $key => $value) {
                $this->validate($key, $value);
            }
        }
        return $this->records;
    }

    /**
     * @return mixed
     * @psalm-return V|null
     */
    public function collectHead()
    {
        if (empty($this->records)) {
            return null;
        }
        if ($this->validationEnabled) {
            foreach ($this->records as $key => $value) {
                $this->validate($key, $value);
                break;
            }
        }
        return \reset($this->records);
    }

    /**
     * @template K1 as array-key
     * @template V1
     * @param Type $keyType
     * @psalm-param Type<K1> $keyType
     * @param Type $valueType
     * @psalm-param Type<V1> $valueType
     * @return Collector
     * @psalm-return Collector<K1,V1>
     * @psalm-suppress MixedTypeCoercion
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
