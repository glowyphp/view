<?php

declare(strict_types=1);

namespace Atomastic\View;

use ArrayAccess;
use Atomastic\Macroable\Macroable;
use BadMethodCallException;
use RuntimeException as ViewException;

use function array_key_exists;
use function array_merge;
use function call_user_func;
use function extract;
use function filesystem;
use function is_array;
use function ob_get_clean;
use function ob_start;
use function sprintf;
use function strings;
use function substr;
use function vsprintf;

use const EXTR_REFS;

class View implements ArrayAccess
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The views directory.
     */
    protected static string $directory = '';

    /**
     * The name of the view.
     */
    protected string $view;

    /**
     * The array of view data.
     *
     * @var array
     */
    protected array $data;

    /**
     * The content of view.
     *
     * @var array
     */
    protected string $content;

    /**
     * Data that should be available to all views.
     *
     * @var array
     */
    protected static array $shared = [];

    /**
     * The extension of the view.
     */
    protected static string $extension = 'php';

    /**
     * Create a new view instance.
     *
     * @param string $view Name of the view file
     * @param array  $data Array of view variables
     */
    public function __construct(string $view, array $data = [])
    {
        $viewFile = self::$directory . '/' . self::denormalizeName(self::normalizeName($view)) . '.' . self::$extension;

        // Check if view file exists
        if (! filesystem()->file($viewFile)->exists()) {
            throw new ViewException(vsprintf("%s(): The '%s' view does not exist.", [__METHOD__, $view]));
        }

        // Set view file
        $this->view = $viewFile;

        // Set view data
        $this->data = $data;

        // Set view content
        $this->content = '';
    }

    /**
     * Share data with all views.
     *
     * @param  array|string $key   Data key
     * @param  mixed|null   $value Data value
     */
    public static function share($key, $value)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            self::$shared[$key] = $value;
        }

        return $value;
    }

    /**
     * Get shared data with.
     *
     * @param  array|string $key   Data key
     * @param  mixed|null   $value Data value
     */
    public static function getShared()
    {
        return self::$shared;
    }

    /**
     * Add a piece of data to the view.
     *
     * @param  string|array $key
     * @param  mixed        $value
     *
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Include the view file and extracts the view variables before returning the generated output.
     *
     * @param  string $callback Callback function used to filter output.
     */
    public function render(?callable $callback = null): string
    {
        // Is output empty ?
        if (empty($this->content)) {
            // Extract variables as references
            extract(array_merge($this->data, self::$shared), EXTR_REFS);

            // Turn on output buffering
            ob_start();

            // Include view file
            include $this->view;

            // Output...
            $this->content = ob_get_clean();
        }

        // Filter output ?
        if ($callback !== null) {
            $this->content = call_user_func($callback, $this->content);
        }

        // Return output
        return $this->content;
    }

    /**
     * Displays the rendered view.
     */
    public function display(): void
    {
        echo $this->render();
    }

    /**
     * Get the array of view data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set views directory.
     *
     * @param string $directory Views directory.
     */
    public static function setDirectory(string $directory): void
    {
        self::$directory = $directory;
    }

    /**
     * Set views extension.
     *
     * @param string $extension Views extension.
     */
    public static function setExtension(string $extension): void
    {
        self::$extension = $extension;
    }

    /**
     * Denormalize view name.
     * 
     * @param string $view View name.
     */
    public static function denormalizeName(string $view): string
    {
        return strings($view)->replace('.', '/')->toString();
    }

    /**
     * Normalize view name.
     * 
     * @param string $view View name.
     */
    public static function normalizeName(string $view): string
    {
        return strings($view)->replace('/', '.')->toString();
    }

    /**
     * Dynamically bind parameters to the view.
     *
     * @param string $method     Method.
     * @param array  $parameters Parameters.
     */
    public function __call(string $method, array $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (! strings($method)->startsWith('with')) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.',
                static::class,
                $method
            ));
        }

        return $this->with(strings(substr($method, 4))->camel()->toString(), $parameters[0]);
    }

    /**
     * Whether an offset exists.
     *
     * @param mixed $offset An offset to check for.
     *
     * @return bool Return TRUE key exists in the array, FALSE otherwise.
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * Get a piece of bound data to the view.
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * Assign a value to the specified offset.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     *
     * @return void Return void.
     */
    public function offsetSet($offset, $value): void
    {
        $this->with($offset, $value);
    }

    /**
     * Unset an offset.
     *
     * @param mixed $offset The offset to unset.
     *
     * @return void Return void.
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Returns the value of the array.
     */
    public function &__get($offset)
    {
        return $this->data[$offset];
    }

    /**
     * Assign a value to the specified offset.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     *
     * @return void Return void.
     */
    public function __set($offset, $value): void
    {
        $this->with($offset, $value);
    }

    /**
     * Whether an offset exists.
     *
     * @param mixed $offset An offset to check for.
     *
     * @return bool Return TRUE key exists in the array, FALSE otherwise.
     */
    public function __isset($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Unset an offset.
     *
     * @param mixed $offset The offset to unset.
     *
     * @return void Return void.
     */
    public function __unset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Get the string contents of the view.
     *
     * @return string Returns the string contents of the view.
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
