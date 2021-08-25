<?php

declare(strict_types=1);

use Atomastic\View\View;
use RuntimeException as ViewException;

test('__construct method', function (): void {
    $this->assertInstanceOf(View::class, new View(__DIR__ . '/fixtures/foo'));
});

test('throw exception ViewException', function (): void {
    $view =  new View(__DIR__ . '/fixtures/bar');
})->throws(ViewException::class);

test('view helper', function (): void {
    $this->assertInstanceOf(View::class, view(__DIR__ . '/fixtures/foo'));
});

test('e helper', function (): void {
    $this->assertEquals("&lt;a href='test'&gt;Test&lt;/a&gt;", e("<a href='test'>Test</a>"));
    $this->assertEquals("&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;", e("<a href='test'>Test</a>", ENT_QUOTES));
});

test('with method', function (): void {
    $view = view(__DIR__ . '/fixtures/foo');

    $view->with('foo', 'Foo');
    $view->with('bar', 'Bar');

    $this->assertEquals(['foo' => 'Foo', 'bar' => 'Bar'], $view->getData());
});

test('share method', function (): void {
    $view = view(__DIR__ . '/fixtures/share');

    View::share('share', 'Foo');

    $this->assertEquals('Foo', $view->render());
});

test('view magic methods', function (): void {
    $view = view(__DIR__ . '/fixtures/magic');

    $this->assertFalse(isset($view->foo));

    $view->foo = 'Foo';
    $this->assertEquals('Foo', $view->foo);
    $this->assertEquals($view['foo'], $view->foo);
    $this->assertTrue(isset($view->foo));
    $this->assertTrue(isset($view['foo']));
    $this->assertTrue($view->offsetExists('foo'));
    $this->assertEquals('Foo', $view->render());
});
