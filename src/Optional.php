<?php

namespace JoelButcher\PhpOptional;

use JoelButcher\PhpOptional\Exceptions\NoSuchElementException;
use Psl\Type\TypeInterface;
use Throwable;

/** @template Contents */
final class Optional implements \Stringable
{
    /** @param  Contents  $value */
    public function __construct(
        private bool $hasValue,
        private mixed $value
    ) {
        //
    }

    /**
     * @return Optional<null>
     */
    public static function empty(): self
    {
        return new self(false, null);
    }

    /**
     * @template T
     *
     * @param  T  $value
     * @param  TypeInterface<T>|null  $type
     * @return Optional<T>
     */
    public static function for(mixed $value, ?TypeInterface $type = null): self
    {
        return new self(
            true,
            is_null($type) ? $value : $type->coerce($value)
        );
    }

    /**
     * @template T
     *
     * @param  T|null  $value
     * @param  TypeInterface<T>|null  $type
     * @return Optional<T|null>
     */
    public static function forNullable(mixed $value = null, ?TypeInterface $type = null): self
    {
        return new self(
            ! is_null($value),
            is_null($value) || is_null($type) ? $value : $type->coerce($value)
        );
    }

    /**
     * @template T
     *
     * @param  array<string, T>  $input
     * @param  TypeInterface<T>  $type
     * @return Optional<T|null>
     */
    public static function forOptionalArrayKey(array $input, string $key, TypeInterface $type): self
    {
        return new self(
            array_key_exists($key, $input),
            array_key_exists($key, $input) ? $type->coerce($input[$key]) : null
        );
    }

    /**
     * @return Contents
     */
    public function get(): mixed
    {
        if ($this->value === null) {
            throw new NoSuchElementException('No value present.');
        }

        return $this->value;
    }

    public function isPresent(): bool
    {
        return $this->hasValue;
    }

    public function isEmpty(): bool
    {
        return ! $this->hasValue;
    }

    /**
     * @param  callable(Contents): void  $callback
     */
    public function ifPresent(callable $callback): void
    {
        if ($this->isPresent()) {
            $callback($this->value);
        }
    }

    /**
     * @param  callable(Contents): void  $callback
     * @param  callable(Contents): void  $default
     */
    public function ifPresentOrElse(callable $callback, callable $default): void
    {
        $this->isPresent()
            ? $callback($this->value)
            : $default($this->value);
    }

    /**
     * @param  callable(Contents): bool  $callback
     * @return Optional<Contents>|Optional<null>
     */
    public function filter(callable $callback): self
    {
        return $this->isEmpty()
            ? $this
            : ($callback($this->value) ? $this : Optional::empty());
    }

    /**
     * @template T
     *
     * @param  callable(Contents): T  $callback
     * @return Optional<null>|Optional<T|null>
     */
    public function map(callable $callback): self
    {
        return $this->isEmpty()
            ? new self(false, null)
            : self::forNullable($callback($this->value));
    }

    /**
     * @template T
     *
     * @param  Optional<T>  $optional
     * @return Optional<T>
     */
    public function or(Optional $optional): Optional
    {
        if ($this->isPresent()) {
            return $this;
        }

        return new self(true, $optional->get());
    }

    /**
     * @template T
     *
     * @param  T  $other
     * @return T
     */
    public function orElse(mixed $other): mixed
    {
        return $this->isPresent() ? $this->get() : $other;
    }

    /**
     * @template T
     *
     * @param  Optional<T>  $other
     * @return T
     */
    public function orElseGet(Optional $other): mixed
    {
        return $this->isPresent() ? $this->get() : $other->get();
    }

    /**
     * @return Contents
     *
     * @throws NoSuchElementException|Throwable
     */
    public function orElseThrow(?callable $callback = null): mixed
    {
        if ($this->isEmpty()) {
            if ($callback) {
                $callback();
            }

            throw new NoSuchElementException('No value present.');
        }

        return $this->value;
    }

    public function equals(object $obj): bool
    {
        if ($this === $obj) {
            return true;
        }

        if (! $obj instanceof Optional) {
            return false;
        }

        return $this->get() == $obj->get();
    }

    /**
     * @param  callable(Contents): void  $apply
     */
    public function apply(callable $apply): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $apply($this->value);
    }

    public function __toString(): string
    {
        $value = $this->value;

        if (is_object($value)) {
            $value = json_decode(json_encode($value) ?: '', true);
        }

        if (is_array($value)) {
            $value = sprintf('[%s]', implode(', ', $value));
        }

        return $value != null
            ? sprintf('Optional[%s]', $value)
            : 'Optional[empty]';
    }
}
