<?php

declare(strict_types=1);

use Atomastic\View\View;
use RuntimeException as ViewException;

test('construct', function (): void {
    $this->assertInstanceOf(View::class, new View(__DIR__ . '/fixtures/foo'));
});

test('throw exception ViewException', function (): void {
    $view =  new View(__DIR__ . '/fixtures/bar');
})->throws(ViewException::class);

test('view', function (): void {
    $this->assertInstanceOf(View::class, view(__DIR__ . '/fixtures/foo'));
});

test('e', function (): void {
    $this->assertEquals("&lt;a href='test'&gt;Test&lt;/a&gt;", e("<a href='test'>Test</a>"));
    $this->assertEquals("&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;", e("<a href='test'>Test</a>", ENT_QUOTES));
});

test('with', function (): void {
    $view = view(__DIR__ . '/fixtures/foo');

    $view->with('foo', 'Foo');
    $view->with('bar', 'Bar');

    $this->assertEquals(['foo' => 'Foo', 'bar' => 'Bar'], $view->getData());

    $view->with(['qwe' => 'QWE']);

    $this->assertEquals(['foo' => 'Foo', 'bar' => 'Bar', 'qwe' => 'QWE'], $view->getData());
});

test('share', function (): void {
    $view = view(__DIR__ . '/fixtures/share');

    View::share('share', 'Foo');

    $this->assertEquals('Foo', $view->render());
});

test('getShared', function (): void {
    $view = view(__DIR__ . '/fixtures/share');

    View::share('share', 'Foo');

    $this->assertEquals(['share' => 'Foo'], View::getShared());
});

test('view magic', function (): void {
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

test('render', function (): void {
    $view = view(__DIR__ . '/fixtures/foo');

    $this->assertEquals('Foo', $view->render());
});

test('render with callback', function (): void {
    $view = view(__DIR__ . '/fixtures/foo');

    $this->assertEquals('1. Foo', $view->render(function ($value) { return '1. ' . $value; }));
});

test('dislay', function (): void {
    $view = view(__DIR__ . '/fixtures/foo');

    $this->expectOutputString('Foo');
    $view->display();
});

test('macro', function (): void {
    View::macro('customMethod', function($arg1 = 1, $arg2 = 1) {
        return $arg1 + $arg2;
    });

    $view = view(__DIR__ . '/fixtures/foo');
    $this->assertEquals(2, $view->customMethod());
    $this->assertEquals(4, $view->customMethod(2, 2));
});
