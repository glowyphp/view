<?php

declare(strict_types=1);

namespace Atomastic\View;

use ArrayAccess;
use Atomastic\Macroable\Macroable;
use BadMethodCallException;
use RuntimeException as ViewException;
use LogicException as ViewLogicException;

use function array_key_exists;
use function array_merge;
use function call_user_func;
use function extract;
use function filesystem;
use function is_array;
use function is_null;
use function ob_get_clean;
use function ob_start;
use function sprintf;
use function strings;
use function substr;
use function view;
use function vsprintf;

use const EXTR_REFS;

class View implements ArrayAccess
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * Section mode
     */
    public const SECTION_MODE_REWRITE = 1;
    public const SECTION_MODE_PREPEND = 2;
    public const SECTION_MODE_APPEND  = 3;

    /**
     * The views directory.
     *
     * @var string View directory.
     */
    protected static string $directory = '';

    /**
     * The name of the view.
     *
     * @var string View name.
     */
    protected string $view;

    /**
     * The array of view data.
     *
     * @var array View data.
     */
    protected array $data;

    /**
     * The content of view.
     *
     * @var string View content.
     */
    protected string $content;

    /**
     * Data that should be available to all views.
     *
     * @var array Shared data with all views.
     */
    protected static array $shared = [];

    /**
     * The extension of the view.
     */
    protected static string $extension = 'php';

    /**
     * Set section content mode:
     * rewrite, append, prepend
     *
     * @var int Section mode.
     */
    protected int $sectionMode = self::SECTION_MODE_REWRITE;

    /**
     * The name of the parent view.
     *
     * @var string Parent view name.
     */
    protected string $parentViewName;

    /**
     * The data assigned to the parent view.
     *
     * @var array Parent view data.
     */
    protected array $parentViewData;

    /**
     * Create a new view instance.
     *
     * @param string $view Name of the view file
     * @param array  $data Array of view variables
     */
    public function __construct(string $view, array $data = [])
    {
        $viewFilePath = self::getFilePath($view);

        // Check if view file exists
        if (! filesystem()->file($viewFilePath)->exists()) {
            throw new ViewException(vsprintf("%s(): The '%s' view does not exist.", [__METHOD__, $view]));
        }

        // Set view file
        $this->view = $viewFilePath;

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
     * Get shared data.
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
     * @param string $callback Callback function used to filter output.
     *
     * @return string View content.
     */
    public function render(?callable $callback = null): string
    {
        // Is content empty
        if (empty($this->content)) {
            // Extract variables as references
            extract(array_merge($this->data, self::$shared), EXTR_REFS);

            // Turn on output buffering
            ob_start();

            // Include view file
            include $this->view;

            // Write content.
            $this->content = ob_get_clean();

            // Extend parent view
            if (isset($this->parentViewName)) {
                $parent           = view($this->parentViewName, $this->parentViewData);
                $parent->sections = $this->sections;
                $this->content    = $parent->render();
            }
        }

        // Filter content
        if ($callback !== null) {
            $this->content = call_user_func($callback, $this->content);
        }

        // Return content
        return $this->content;
    }

    /**
     * Displays the rendered view.
     *
     * @param string $callback Callback function used to filter output.
     */
    public function display(?callable $callback = null): void
    {
        echo $this->render($callback);
    }

    /**
     * Get the array of view data.
     *
     * @return array View data.
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
     * Determining If A View Exists
     *
     * @param string $view View name.
     * 
     * @return bool Returns true or false view doesnt exists.
     */
    public static function exists(string $view): bool
    {
        return filesystem()->file(self::getFilePath($view))->exists();
    }

    /**
     * Get view file path.
     * 
     * @param string $view View name.
     * 
     * @return string Retruns view file path.
     */
    public static function getFilePath(string $view): string
    {
        return self::$directory . '/' . self::denormalizeName(self::normalizeName($view)) . '.' . self::$extension;
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
     * Fetch view.
     *
     * @param string $name     View name.
     * @param array  $data     View data.
     * @param string $callback Callback function used to filter output.
     *
     * @return string View content.
     */
    public function fetch(string $view, array $data = [], ?callable $callback = null): string
    {
        return view($view, $data)->render($callback);
    }

    /**
     * Include view and display.
     *
     * @param string $name     View name.
     * @param array  $data     View data.
     * @param string $callback Callback function used to filter output.
     * 
     * @return void Return void.
     */
    public function include(string $view, array $data = [], ?callable $callback = null): void
    {
        view($view, $data)->display($callback);
    }

    /**
     * Extend parent view.
     *
     * @param string $name View name to extend.
     * @param array  $data View data.
     * 
     * @return void Return void.
     */
    public function extends(string $name, array $data = []): void
    {
        $this->parentViewName = $name;
        $this->parentViewData = $data;
    }

    /**
     * Determine if section exists.
     *
     * @param string $name Section name.
     *
     * @return bool Returns true or false section doesnt exists.
     */
    public function hasSection(string $name): bool
    {
        if (isset($this->sections[$name])) {
            return true;
        }

        return false;
    }

    /**
     * Get section.
     *
     * @param string $name    Section name.
     * @param mixed  $default Default data to display.
     */
    public function getSection(string $name, $default = null)
    {
        if (! isset($this->sections[$name])) {
            return $default;
        }

        return $this->sections[$name];
    }

    /**
     * Start new prepend section.
     *
     * @param string $name The name of the section.
     */
    public function prependSection(string $name): void
    {
        $this->section($name, self::SECTION_MODE_PREPEND);
    }

    /**
     * Start new append section.
     *
     * @param string $name The name of the section.
     */
    public function appendSection(string $name): void
    {
        $this->section($name, self::SECTION_MODE_APPEND);
    }

    /**
     * Start a new section block.
     *
     * @param string $name The name of the section.
     * @param string $mode The mode of the section.
     */
    public function section(string $name, int $mode = self::SECTION_MODE_REWRITE): void
    {
        if ($this->sectionName) {
            throw new ViewLogicException('You cannot nest sections within other sections.');
        }

        $this->sectionName = $name;
        $this->sectionMode = $mode;

        ob_start();
    }

    /**
     * Stop the current section block.
     */
    public function endSection(): void
    {
        if (is_null($this->sectionName)) {
            throw new ViewLogicException(
                'You must start a section before you can stop it.'
            );
        }

        if (! isset($this->sections[$this->sectionName])) {
            $this->sections[$this->sectionName] = '';
        }

        switch ($this->sectionMode) {
            case self::SECTION_MODE_APPEND:
                $this->sections[$this->sectionName] .= ob_get_clean();
                break;

            case self::SECTION_MODE_PREPEND:
                $this->sections[$this->sectionName] = ob_get_clean() . $this->sections[$this->sectionName];
                break;

            default:
            case self::SECTION_MODE_REWRITE:
                $this->sections[$this->sectionName] = ob_get_clean();
                break;
        }

        $this->sectionName = null;
        $this->sectionMode = self::SECTION_MODE_REWRITE;
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
