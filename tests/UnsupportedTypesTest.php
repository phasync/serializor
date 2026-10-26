<?php

declare(strict_types=1);

namespace Tests;

use Fiber;
use Generator;
use Serializor\Codec;
use Serializor\SerializerError;
use SplFileObject;

/**
 * Values that cannot survive a serialize/unserialize round trip must be
 * rejected eagerly by serialize(), rather than silently corrupting (resources)
 * or failing later during unserialize() (Generator, Fiber, SplFileObject, ...).
 *
 * @see https://github.com/phasync/serializor/issues/15
 */

test('serializing a resource directly throws', function (): void {
    $resource = fopen('php://memory', 'r');

    try {
        expect(fn () => (new Codec())->serialize($resource))->toThrow(SerializerError::class);
    } finally {
        fclose($resource);
    }
});

test('serializing a resource nested in an array throws', function (): void {
    $resource = fopen('php://memory', 'r');
    $value = ['nested' => ['resource' => $resource]];

    try {
        expect(fn () => (new Codec())->serialize($value))->toThrow(SerializerError::class);
    } finally {
        fclose($resource);
    }
});

test('serializing a resource captured by a closure throws', function (): void {
    $resource = fopen('php://memory', 'r');
    $closure = static function () use ($resource) {};

    try {
        expect(fn () => (new Codec())->serialize($closure))->toThrow(SerializerError::class);
    } finally {
        fclose($resource);
    }
});

test('serializing a Generator directly throws', function (): void {
    $gen = (static function () { yield 1; })();

    expect(fn () => (new Codec())->serialize($gen))->toThrow(SerializerError::class);
});

test('serializing a Generator captured by a closure throws', function (): void {
    $gen = (static function () { yield 1; })();
    $closure = static function () use ($gen) {};

    expect(fn () => (new Codec())->serialize($closure))->toThrow(SerializerError::class);
});

test('serializing a Fiber throws', function (): void {
    $fiber = new Fiber(static fn () => 1);

    expect(fn () => (new Codec())->serialize($fiber))->toThrow(SerializerError::class);
});

test('serializing a Fiber captured by a closure throws', function (): void {
    $fiber = new Fiber(static fn () => 1);
    $closure = static function () use ($fiber) {};

    expect(fn () => (new Codec())->serialize($closure))->toThrow(SerializerError::class);
});

test('serializing an SplFileObject throws', function (): void {
    $file = new SplFileObject(__FILE__);

    expect(fn () => (new Codec())->serialize($file))->toThrow(SerializerError::class);
});

test('serializing an SplFileObject captured by a closure throws', function (): void {
    $file = new SplFileObject(__FILE__);
    $closure = static function () use ($file) {};

    expect(fn () => (new Codec())->serialize($closure))->toThrow(SerializerError::class);
});

test('error message names the type for a resource', function (): void {
    $resource = fopen('php://memory', 'r');

    try {
        (new Codec())->serialize($resource);
        $this->fail('Expected SerializerError');
    } catch (SerializerError $e) {
        expect($e->getMessage())->toContain('resource');
    } finally {
        fclose($resource);
    }
});

test('error message names the class for an unsupported object', function (): void {
    $gen = (static function () { yield 1; })();

    try {
        (new Codec())->serialize($gen);
        $this->fail('Expected SerializerError');
    } catch (SerializerError $e) {
        expect($e->getMessage())->toContain('Generator');
    }
});

test('error message names the closure variable when feasible', function (): void {
    $gen = (static function () { yield 1; })();
    $closure = static function () use ($gen) {};

    try {
        (new Codec())->serialize($closure);
        $this->fail('Expected SerializerError');
    } catch (SerializerError $e) {
        expect($e->getMessage())->toContain('gen');
    }
});
