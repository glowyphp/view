<?php

declare(strict_types=1);

use Glowy\View\View;
use RuntimeException as ViewException;
use LogicException as ViewLogicException;

test('construct', function (): void {
    $this->assertInstanceOf(View::class, new View(__DIR__ . '/fixtures/foo'));
});

test('throw exception ViewException', function (): void {
    $view = new View(__DIR__ . '/fixtures/bar');
})->throws(ViewException::class);

test('throw exception BadMethodCallException', function (): void {
    $view = new View(__DIR__ . '/fixtures/foo');
    $view->foo();
})->throws(BadMethodCallException::class);

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
    $view->bar = 'Bar';
    $this->assertEquals('Foo', $view->foo);
    $this->assertEquals($view['foo'], $view->foo);
    $this->assertTrue(isset($view->foo));
    $this->assertTrue(isset($view['foo']));
    $this->assertTrue($view->offsetExists('foo'));
    $this->assertEquals('Foo', $view->render());


    $view->offsetset('zed', 'Zed');
    $this->assertTrue($view->offsetExists('zed'));
    $this->assertEquals('Zed', $view->offsetGet('zed'));
    $view->offsetUnset('zed');
    $this->assertFalse(isset($view->zed));

    unset($view->bar);
    $this->assertFalse(isset($view->bar));
});

test('render', function (): void {
    $view = view(__DIR__ . '/fixtures/foo');

    $this->assertEquals('Foo', (string) $view);
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

    $view = view(__DIR__ . '/fixtures/macro');
    $this->assertEquals(2, $view->customMethod());
    $this->assertEquals(4, $view->customMethod(2, 2));

    $this->assertEquals('Foo Bar', (string) $view->withFoo('Foo')->withBar('Bar'));
});

test('set directory', function (): void {
    View::setDirectory(__DIR__ . '/fixtures/');

    $this->assertEquals('Foo', view('foo')->render());
});

test('set extension', function (): void {
    View::setExtension('php');

    $this->assertEquals('Foo', view('foo')->render());
});

test('normalize view name', function (): void {
    $this->assertEquals('foo.bar.zed', View::normalizeName('foo/bar/zed'));
});

test('denormalize view name', function (): void {
    $this->assertEquals('foo/bar/zed', View::denormalizeName('foo.bar.zed'));
});

test('sections', function (): void {
    $view = view('sections/foo');

    $this->expectOutputString("Foo content...\n");
    $view->display();
});

test('sections with default', function (): void {
    $view = view('sections/section-default');

    $this->expectOutputString("Default");
    $view->display();
});

test('sections has', function (): void {
    $view = view('sections/section-has');

    $this->expectOutputString("");
    $view->display();
});

test('fetch', function (): void {
    $view = view('fetch');

    $this->expectOutputString("Foo");
    $view->display();
});

test('inlcude', function (): void {
    $view = view('include');

    $this->expectOutputString("Foo");
    $view->display();
});

test('sections with override', function (): void {
    $view = view('sections/bar');

    $this->expectOutputString("Bar content...\n");
    $view->display();
});

test('sections with extends', function (): void {
    $view = view('sections/layouts/bar');

    $string = '<divclass="foo">Foocontent...</div><divclass="bar">Barcontent...</div><divclass="zed">Zedcontent...</div>';
    $this->assertEquals($string, trim(preg_replace('/\s/', '', $view->render())));
});

test('sections prepend and append', function (): void {
    $view = view('sections/mode');

    $string = "prependcontent...prependcontent...Foocontent...appendcontent...appendcontent...";
    $this->assertEquals($string, trim(preg_replace('/\s/', '', $view->render())));
});

test('sections throw LogicException for endSection method', function (): void {
    $view = view('sections/foo')->endSection();
})->throws(ViewLogicException::class);