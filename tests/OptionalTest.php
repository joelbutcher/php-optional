<?php

use JoelButcher\PhpOptional\Exceptions\NoSuchElementException;
use JoelButcher\PhpOptional\Optional;
use JoelButcher\PhpOptional\Tests\TestCase;
use Psl\Type\Internal\StringType;

uses(TestCase::class);

it('can be empty', function () {
    $value = Optional::empty();
    expect($value->isEmpty())->toBeTrue();
});

it('can be null', function () {
    $value = Optional::forNullable(null);
    expect($value->isPresent())->toBeFalse();
});

it('throws when attempting the retrieve the contents of an empty value', function () {
    $value = Optional::forNullable(null);
    expect(fn () => $value->get())->toThrow(NoSuchElementException::class, 'No value present.');
});

it('can be a string', function () {
    $value = Optional::for('foo');
    expect($value->get())->toEqual('foo');
});

it('can be a number', function () {
    $value = Optional::for(123);
    expect($value->get())->toEqual(123);
});

it('can be an array', function () {
    $value = Optional::for(['foo', 'bar']);
    expect($value->get())->toEqual(['foo', 'bar']);
});

it('can be an array key that exists', function () {
    $value = Optional::forOptionalArrayKey(['foo' => 'bar'], 'foo', new StringType());
    expect($value->get())->toEqual('bar');
});

it('can be an array key that does not exists', function () {
    $value = Optional::forOptionalArrayKey(['foo' => 'bar'], 'bar', new StringType());
    expect($value->isEmpty())->toBeTrue();
});

it('can be an object', function () {
    $value = Optional::for((object) ['foo' => 'bar']);
    expect($value->get())->toEqual((object) ['foo' => 'bar']);
});

it('applies a given callback if the value is present', function () {
    Optional::for('foo')->ifPresent(fn (string $contents) => expect($contents)->toEqual('foo'));
    Optional::for(123)->ifPresent(fn (int $contents) => expect($contents)->toEqual('123'));
    Optional::for(['foo', 'bar'])->ifPresent(fn (array $contents) => expect($contents)->toEqual(['foo', 'bar']));
    Optional::for((object) ['foo', 'bar'])->ifPresent(fn (object $contents) => expect($contents)->toEqual((object) ['foo', 'bar']));
});

it('applies a given callback, not the default if the value is present', function () {
    Optional::for('foo')->ifPresentOrElse(fn (string $contents) => expect($contents)->toEqual('foo'), fn () => null);
    Optional::for(123)->ifPresentOrElse(fn (int $contents) => expect($contents)->toEqual('123'), fn () => null);
    Optional::for(['foo', 'bar'])->ifPresentOrElse(fn (array $contents) => expect($contents)->toEqual(['foo', 'bar']), fn () => null);
    Optional::for((object) ['foo', 'bar'])->ifPresentOrElse(fn (object $contents) => expect($contents)->toEqual((object) ['foo', 'bar']), fn () => null);
});

it('applies the default callback, if the value is not present', function () {
    Optional::forNullable()->ifPresentOrElse(fn () => null, fn (mixed $contents) => expect($contents)->toBeNull());
});

it('returns an empty instance if the filter callback does not match the value', function () {
    $value = Optional::for('foo')->filter(fn (string $contents) => $contents === 'bar');
    expect($value->isEmpty())->toBeTrue();

    $value = Optional::for(123)->filter(fn (string $contents) => $contents === 'foo');
    expect($value->isEmpty())->toBeTrue();

    $value = Optional::for(['foo', 'bar', 'baz'])->filter(fn (array $contents) => $contents === 'foo');
    expect($value->isEmpty())->toBeTrue();

    $value = Optional::for((object) ['foo', 'bar', 'baz'])->filter(fn (object $contents) => $contents === (object) ['foo']);
    expect($value->isEmpty())->toBeTrue();
});

it('returns itself if the value is null or empty', function () {
    $original = Optional::forNullable(null);
    expect($original->filter(fn () => true))->toBe($original)->and($original->filter(fn () => false))->toEqual($original);
});

it('can be filter strings', function () {
    $value = Optional::for('foo')->filter(fn (mixed $contents) => $contents === 'foo');
    expect($value->get())->toEqual('foo');
});

it('can be filter integers', function () {
    $value = Optional::for(123)->filter(fn (mixed $contents) => $contents === 123);
    expect($value->get())->toEqual(123);
});

it('can be filter arrays', function () {
    $value = Optional::for(['foo', 'bar'])->filter(fn (mixed $contents) => $contents === ['foo', 'bar']);
    expect($value->get())->toEqual(['foo', 'bar']);
});

it('can be filter objects', function () {
    $value = Optional::for((object) ['foo', 'bar'])->filter(fn (object $value) => $value == (object) ['foo', 'bar']);
    expect($value->get())->toEqual((object) ['foo', 'bar']);
});

it('does not apply the mapping function if the value is null', function () {
    $value = Optional::forNullable();
    $mappedValue = $value->map(fn (mixed $contents) => 'foo');
    expect($mappedValue->isEmpty())->toBeTrue()->and($mappedValue)->not->toBe($value);
});

it('applies the mapping function to strings', function () {
    $value = Optional::forNullable('foo');
    $mappedValue = $value->map(fn (string $contents) => "$contents bar");
    expect($mappedValue->get())->toEqual('foo bar');
});

it('applies the mapping function to integers', function () {
    $value = Optional::forNullable(123);
    $mappedValue = $value->map(fn (int $contents) => $contents + 123);
    expect($mappedValue->get())->toEqual(246);
});

it('applies the mapping function to arrays', function () {
    $value = Optional::forNullable(['foo']);
    $mappedValue = $value->map(function (array $contents) {
        $contents[] = 'bar';

        return $contents;
    });

    expect($mappedValue->get())->toEqual(['foo', 'bar']);
});

it('applies the mapping function to objects', function () {
    $value = Optional::forNullable((object) ['foo' => 'bar']);
    $mappedValue = $value->map(function (object $contents) {
        $contents->baz = 'bic';

        return $contents;
    });

    expect($mappedValue->get())->toEqual((object) ['foo' => 'bar', 'baz' => 'bic']);
});

test('when comparing, it returns itself', function () {
    $value = Optional::for('foo')->or(Optional::for('bar'));
    expect($value->get())->toEqual('foo');

    $value = Optional::for(123)->or(Optional::for(456));
    expect($value->get())->toEqual(123);

    $value = Optional::for(['foo', 'bar'])->or(Optional::for(['baz']));
    expect($value->get())->toEqual(['foo', 'bar']);

    $value = Optional::for((object) ['foo', 'bar'])->or(Optional::for((object) ['baz']));
    expect($value->get())->toEqual((object) ['foo', 'bar']);
});

test('when comparing, it returns the other value', function () {
    $value = Optional::forNullable()->or(Optional::for('bar'));
    expect($value->get())->toEqual('bar');

    $value = Optional::forNullable()->or(Optional::for(456));
    expect($value->get())->toEqual(456);

    $value = Optional::forNullable()->or(Optional::for(['baz']));
    expect($value->get())->toEqual(['baz']);

    $value = Optional::forNullable()->or(Optional::for((object) ['baz']));
    expect($value->get())->toEqual((object) ['baz']);
});

test('when comparing the contents, it returns its own contents', function () {
    $value = Optional::forNullable('foo')->orElse('bar');
    expect($value)->toEqual('foo');

    $value = Optional::forNullable(123)->orElse(456);
    expect($value)->toEqual(123);

    $value = Optional::forNullable(['foo', 'bar'])->orElse(['baz']);
    expect($value)->toEqual(['foo', 'bar']);

    $value = Optional::forNullable((object) ['foo', 'bar'])->orElse((object) ['baz']);
    expect($value)->toEqual((object) ['foo', 'bar']);
});

test('when comparing the contents, it returns the other value', function () {
    $value = Optional::forNullable()->orElse('foo');
    expect($value)->toEqual('foo');

    $value = Optional::forNullable()->orElse(456);
    expect($value)->toEqual(456);

    $value = Optional::forNullable()->orElse(['baz']);
    expect($value)->toEqual(['baz']);

    $value = Optional::forNullable()->orElse((object) ['baz']);
    expect($value)->toEqual((object) ['baz']);
});

test('when comparing with another, it "gets" its own contents', function () {
    $value = Optional::for('foo')->orElseGet(Optional::for('bar'));
    expect($value)->toEqual('foo');

    $value = Optional::for(123)->orElseGet(Optional::for(456));
    expect($value)->toEqual(123);

    $value = Optional::for(['foo', 'bar'])->orElseGet(Optional::for(['baz']));
    expect($value)->toEqual(['foo', 'bar']);

    $value = Optional::for((object) ['foo', 'bar'])->orElseGet(Optional::for((object) ['baz']));
    expect($value)->toEqual((object) ['foo', 'bar']);
});

test('when comparing with another, it "gets" the others contents', function () {
    $value = Optional::forNullable()->orElseGet(Optional::for('bar'));
    expect($value)->toEqual('bar');

    $value = Optional::forNullable()->orElseGet(Optional::for(456));
    expect($value)->toEqual(456);

    $value = Optional::forNullable()->orElseGet(Optional::for(['baz']));
    expect($value)->toEqual(['baz']);

    $value = Optional::forNullable()->orElseGet(Optional::for((object) ['baz']));
    expect($value)->toEqual((object) ['baz']);
});

it('returns the value when "orElseThrow" is called and the value is non-empty', function () {
    expect(Optional::for('foo')->orElseThrow())->toEqual('foo')
        ->and(Optional::for(123)->orElseThrow())->toEqual(123)
        ->and(Optional::for(['foo', 'bar'])->orElseThrow())->toEqual(['foo', 'bar'])
        ->and(Optional::for((object) ['foo', 'bar'])->orElseThrow())->toEqual((object) ['foo', 'bar']);
});

it('throws the default exception when "orElseThrow" is called and the value is empty', function () {
    expect(fn () => Optional::forNullable()->orElseThrow())->toThrow(NoSuchElementException::class, 'No value present.');
});

it('to return true for equal objects', function () {
    $value = Optional::for('foo');
    expect($value->equals($value))->toBeTrue();

    $value = Optional::for(123);
    expect($value->equals($value))->toBeTrue();

    $value = Optional::for(['foo', 'bar']);
    expect($value->equals($value))->toBeTrue();

    $value = Optional::for((object) ['foo', 'bar']);
    expect($value->equals($value))->toBeTrue();
});

it('to return false for different object types', function () {
    $value1 = Optional::for('foo');
    expect($value1->equals((object) ['foo']))->toBeFalse();

    $value1 = Optional::for(123);
    expect($value1->equals((object) [123]))->toBeFalse();

    $value1 = Optional::for(['foo', 'bar']);
    expect($value1->equals((object) [['foo', 'bar']]))->toBeFalse();

    $value1 = Optional::for((object) ['foo', 'bar']);
    expect($value1->equals((object) [(object) ['foo', 'bar']]))->toBeFalse();
});

it('to return true for equal contents', function () {
    $value1 = Optional::for('foo');
    $value2 = Optional::for('foo');
    expect($value1->equals($value2))->toBeTrue();

    $value1 = Optional::for(123);
    $value2 = Optional::for(123);
    expect($value1->equals($value2))->toBeTrue();

    $value1 = Optional::for(['foo', 'bar']);
    $value2 = Optional::for(['foo', 'bar']);
    expect($value1->equals($value2))->toBeTrue();

    $value1 = Optional::for((object) ['foo', 'bar']);
    $value2 = Optional::for((object) ['foo', 'bar']);
    expect($value1->equals($value2))->toBeTrue();
});

it('does not apply the given callback if the value is empty', function () {
    $called = false;
    $value = Optional::forNullable();
    $value->apply(function () use (&$called) {
        $called = true;
    });

    expect($called)->toBeFalse();
});

it('does not apply the given callback if the value is non-empty', function () {
    $called = false;
    $value = Optional::forNullable('foo');
    $value->apply(function () use (&$called) {
        $called = true;
    });
    expect($called)->toBeTrue();

    $called = false;
    $value = Optional::forNullable(123);
    $value->apply(function () use (&$called) {
        $called = true;
    });
    expect($called)->toBeTrue();

    $called = false;
    $value = Optional::forNullable(['foo', 'bar']);
    $value->apply(function () use (&$called) {
        $called = true;
    });
    expect($called)->toBeTrue();

    $called = false;
    $value = Optional::forNullable((object) ['foo', 'bar']);
    $value->apply(function () use (&$called) {
        $called = true;
    });
    expect($called)->toBeTrue();
});

it('can cast a null value to a string', function () {
    expect((string) Optional::forNullable())->toEqual('Optional[empty]');
});

it('can cast a non-null value to a string', function () {
    expect((string) Optional::for('foo'))->toEqual('Optional[foo]')
        ->and((string) Optional::for(123))->toEqual('Optional[123]')
        ->and((string) Optional::for(['foo', 'bar']))->toEqual('Optional[[foo, bar]]')
        ->and((string) Optional::for((object) ['foo', 'bar']))->toEqual('Optional[[foo, bar]]');
});

it('can chain multiple methods together', function () {
    $value = Optional::for('foobarbazbiz')
        ->filter(fn (string $v) => strpos($v, 'baz') !== false)
        ->map(fn (string $v) => substr($v, 0, 3));

    expect($value->get())->toEqual('foo')
        ->and(
            fn () => Optional::for('foobarbazbiz')
                ->filter(fn (string $v) => strpos($v, 'bic') !== false)
                ->orElseThrow()
        )->toThrow(NoSuchElementException::class, 'No value present.')
        ->and(
            fn () => Optional::for('foobarbazbiz')
                ->filter(fn (string $v) => strpos($v, 'bic') !== false)
                ->orElseThrow(fn () => throw new \RuntimeException('Value not present.'))
        )->toThrow(\RuntimeException::class, 'Value not present.');
});
