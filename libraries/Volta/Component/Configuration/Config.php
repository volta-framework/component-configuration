<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Volta\Component\Configuration;

use Throwable;
use ArrayAccess;
use Closure;
use JsonSerializable;
use ReflectionClass;

use Volta\Component\Configuration\Exception as ConfigException;


/**
 * Class Config
 * Class for storing name value pairs
 * @implements ArrayAccess<string, mixed>
 * @package Volta
 */
class Config implements ArrayAccess, JsonSerializable
{

    #region - Construction:

    /**
     * @ignore (do not show up in generated documentation)
     * @var string|null
     */
    protected null|string $_file = null;

    /**
     * Returns the name of the file provided, NULL otherwise
     *
     * @return string|null
     */
    public function getFile(): null|string
    {
        return $this->_file;
    }

    /**
     * Config constructor.
     * Passed options can be
     * 1. A PHP file returning array
     * 2. A Json file returning valid json
     * 3. A valid Json string
     * 4. An array itself
     *
     * @param array<string, mixed>|string $options
     * @param Closure|null $onOptionChangeCallback
     * @throws ConfigException
     */
    public function __construct(array|string $options=[], null|Closure $onOptionChangeCallback = null)
    {
        $this->setOptions($options);
        $this->_onOptionChange = $onOptionChangeCallback;
    }

    #endregion -------------------------------------------------------------------------------------------------
    #region - ArrayAccess interface stubs:

    /**
     * @inheritdoc
     * @see https://www.php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->hasOption($offset);
    }

    /**
     * @inheritdoc
     * @see https://www.php.net/manual/en/arrayaccess.offsetGet.php
     * @throws ConfigException
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getOption($offset);
    }

    /**
     * @inheritdoc
     * @see https://www.php.net/manual/en/arrayaccess.offsetSet.php
     * @throws ConfigException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setOption($offset, $value);
    }

    /**
     * @inheritdoc
     * @see https://www.php.net/manual/en/arrayaccess.offsetUnset.php
     * @throws ConfigException
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->unsetOption($offset);
    }

    #endregion -------------------------------------------------------------------------------------------------
    #region - Generation

    /**
     * @ignore Do not show up in generated documentation
     * @var Key[]
     */
    private static array $_attributes = [];

    /**
     * Generates a configuration file based on the Key attributes found in the classes in the libraries
     *
     * @param string[] $library
     * @return array
     */
    public static function generate(array $library): array
    {
        self::$context = [];
        self::$_attributes = [];
        foreach($library as $class) {
            self::getConfigDefaults($class, true);
        }
        return self::$_attributes;
    }

    /**
     * Static cache for the current configuration
     *
     * @var array
     */
    public static array $context = [];

    /**
     * Get the default values for all the configuration keys attributes found in the given class
     *
     * @param string $class
     * @param bool $full
     * @return int
     */
    public static function getConfigDefaults(string $class, bool $full): int
    {
        $numberOfKeysFound = 0;

        try {
            $reflection = new ReflectionClass($class);
        } catch(Throwable $e) {
            return $numberOfKeysFound;
        }

        // class attributes
        $attributes = $reflection->getAttributes(Key::class);
        if (count($attributes) > 0) {
            foreach ($attributes as $index => $attribute ){
                $numberOfKeysFound++;
                $attributeInstance = $attribute->newInstance();
                self::$context[]  = [$class, $attributeInstance->getKey()];
                if ($full) {
                    self::$_attributes = array_replace_recursive(self::$_attributes, $attributeInstance->toArray());
                } else {
                    self::$_attributes = array_replace_recursive(self::$_attributes, $attributeInstance->getDefaultAsArray());
                }
            }
        }

        // method attributes
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(Key::class);
            if (count($attributes) > 0) {
                foreach ($attributes as $index => $attribute ){
                    $numberOfKeysFound++;
                    $attributeInstance = $attribute->newInstance();
                    self::$context[]  = [$class . '::' . $method->getName() . '()', $attributeInstance->getKey()];
                    if ($full) {
                        self::$_attributes = array_replace_recursive(self::$_attributes, $attributeInstance->toArray());
                    } else {
                        self::$_attributes = array_replace_recursive(self::$_attributes, $attributeInstance->getDefaultAsArray());
                    }
                }
            }
        }

        return $numberOfKeysFound;
    }

    #endregion  -------------------------------------------------------------------------------------------------
    #region - JSON related methods

    /**
     * @ignore Do not show up in generated documentation
     * @param string $json
     * @return array<string, mixed>
     * @throws ConfigException
     */
    protected function _getOptionsFromJson(string $json): array
    {
        $options = @json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConfigException(sprintf('Json error %s', $this->_getJsonErrorToText(json_last_error())));
        }
        return $options;
    }
    /**
     * @ignore Do not show up in generated documentation
     * @param int $jsonErrorCode
     * @return string
     */
    protected function _getJsonErrorToText(int $jsonErrorCode): string
    {
        return match ($jsonErrorCode) {
            JSON_ERROR_NONE => '- No errors',
            JSON_ERROR_DEPTH => '- Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => '- Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => '- Unexpected control character found',
            JSON_ERROR_SYNTAX => '- Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => '- Malformed UTF-8 characters, possibly incorrectly encoded',
            default => '- Unknown error',
        };
    }

    /**
     * Generates a JSON formatted configuration string
     *
     * @param array $library
     * @return string
     */
    public static function generateJson(array $library): string
    {
        self::$context = [];
        self::$_attributes = [];
        foreach($library as $class) {
            self::getConfigDefaults($class, false);
        }
        return json_encode(self::$_attributes, JSON_PRETTY_PRINT);
    }


    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {           
        return $this->getOptions();
    }

    #endregion  -------------------------------------------------------------------------------------------------
    #region - Messages placeholders

    // The first placeholder(%s) is the name of the option key,
    // the second the name of the class it is being used in.
    protected string $_requiredMissingMessage = 'Required option "%s" is missing, called in "%s"!';
    protected string $_notAllowedMessage      = 'Option "%s" not allowed, called in "%s"!';
    protected string $_optionNotFoundMessage  = 'Option "%s" not found, called in"%s" and no default value provided!';
    protected string $_alreadySetMessage      = 'Option "%s" already set, called in "%s"!';
    protected string $_unsetRequiredMessage   = 'Cannot unset a required option "%s", called in "%s"!';


    #endregion  -------------------------------------------------------------------------------------------------
    #region - Required Options

    /**
     * @ignore (do not show up in generated documentation)
     * @var string[] If not empty, the option keys in this list are required
     */
    protected array $_requiredOptions = [];

    /**
     * Returns a list with required options
     *
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return $this->_requiredOptions;
    }

    /**
     * Sets teh list of required options
     *
     * @param string[] $requiredOptions
     * @return static
     */
    public function setRequiredOptions(array $requiredOptions): static
    {
        $this->_requiredOptions = $requiredOptions;
        return $this;
    }

    #endregion  -------------------------------------------------------------------------------------------------
    #region - Allowed options

    /**
     * @ignore (do not show up in generated documentation)
     * @var string[]
     */
    protected array $_allowedOptions = [];

    /**
     * Sets the list with allowed options
     * If not empty, only option keys in this list are allowed
     *
     * @param string[] $allowedOptions
     * @return static
     */
    public function setAllowedOptions(array $allowedOptions): static
    {
        $this->_allowedOptions = $allowedOptions;
        return $this;
    }

    /**
     * Returns the list with allowed options
     *
     * @return string[]
     */
    public function getAllowedOptions(): array
    {
        return $this->_allowedOptions;
    }

    #endregion  -------------------------------------------------------------------------------------------------
    #region - Options

    /**
     * @ignore (do not show up in generated documentation)
     * @var array<string, mixed> Internal list with _options;
     */
    protected array $_options = [];

    /**
     * @ignore (do not show up in generated documentation)
     * @var mixed|null Called when an option is changed
     */
    protected null|Closure $_onOptionChange = null;

    /**
     * Sets or overwrites the entire _options list.
     *
     * The __Options::$_requiredOptions__ and __Options::$_allowedOptions__
     * will be taken into account.
     *
     * @see Config::$_allowedOptions
     * @see Config::$_requiredOptions
     *
     * @param string|array<string, mixed> $options
     * @return object The current instance of the class implementing this trait
     * @throws ConfigException
     */
    public function setOptions(array|string $options): object
    {
        if (is_string($options)) { // can be a file or json string
            if (file_exists($options)) { // if not, assume a json string
                $this->_file = $options;
                if( !is_readable($options) || is_dir($options) ) {
                    throw new ConfigException(sprintf('Could not open "%s" as file.', $options));
                }
                $ext = pathinfo($options, PATHINFO_EXTENSION);
                $options = match ($ext) {
                    'php' => (array)include $options,
                    'json' => $this->_getOptionsFromJson((string) file_get_contents($options)),
                    default => throw new ConfigException(sprintf('Filetype "*.%s" not supported', $ext)),
                };
            } else {
                $options = $this->_getOptionsFromJson($options);
            }
        }

        foreach($this->_requiredOptions as $key) {
            if(!array_key_exists($key, $options)){
                throw new ConfigException(sprintf($this->_requiredMissingMessage, $key, $this->_getCallee()));
            }
        }
        if (count($this->_allowedOptions)) {
            foreach(array_keys($options) as $key) {
                if(!in_array($key, $this->_allowedOptions)){
                    throw new ConfigException(sprintf($this->_notAllowedMessage, $key, $this->_getCallee()));
                }
            }
        }
        $this->_options = array_merge($this->_options, $options);
        return $this;
    }

    /**
     * @return array<string, mixed> All the _options
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * Gets the value for an option.
     *
     * If a no option is found with the key __$key__ and a default is provided
     * the default value is returned. An exception is thrown otherwise.
     *
     * @param  string $key     The index of the option
     * @param  mixed  $default Defaults to NULL, default value if the option is not found
     * @return mixed
     * @throws ConfigException
     */
    public function getOption(string $key, mixed $default=null): mixed
    {
        if (count($this->_allowedOptions)) {
            if (!in_array($key, $this->_allowedOptions)){
                throw new ConfigException(sprintf($this->_notAllowedMessage, $key, $this->_getCallee()));
            }
        }
        $keys = explode('.', $key);
        $current = &$this->_options;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!is_array($current) || !isset($current[$keys[$keyIndex]])) {
                if (null===$default) {
                    throw new ConfigException(sprintf($this->_optionNotFoundMessage, $key, $this->_getCallee()));
                }
                return $default;
            }
            $current = &$current[$keys[$keyIndex]];
        }
        return $current;
    }

    /**
     * Shorthand for getOption()
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws Exception
     */
    public function get(string $key, mixed $default=null): mixed
    {
        return $this->getOption($key, $default);
    }

    /**
     * @ignore (do not show up in generated documentation)
     * @return string
     */
    protected function _getCallee(): string
    {
        $callee  = '' ; //static::class;
        $backtrace = debug_backtrace();
        if (isset($backtrace[2])) {
            $callee  = $backtrace[2]['class']??'';
            $callee .= $callee==''?'':'::';
            $callee .= $backtrace[2]['function'];
            $callee .= $callee==''?'':'()';
        }
        if ('' == $callee) $callee = static::class;
        return $callee;
    }

    /**
     * Sets one option
     *
     * Throws an exception when the option already is set and __$overWrite__
     * is set to false
     *
     * The __Options::$_requiredOptions__ and __Options::$_allowedOptions__
     * will be taken into account.
     *
     * @see Config::$_allowedOptions
     * @see Config::$_requiredOptions
     *
     * @param  string $key       The index of the option
     * @param  mixed  $value     The value for the option
     * @param  bool   $overWrite Whether to overwrite if the option exists
     * @return static            The current instance of the class implementing this trait
     * @throws ConfigException         When $overWrite is false and the option already exists
     */
    public function setOption(string $key, mixed $value, bool $overWrite=false ): Static
    {
        if ($this->hasOption($key) && !$overWrite) {
            throw new ConfigException(sprintf($this->_alreadySetMessage, $key, $this->_getCallee()));
        }
        if (count($this->_allowedOptions)) {
            if (!in_array($key, $this->_allowedOptions)){
                throw new ConfigException(sprintf($this->_notAllowedMessage, $key, $this->_getCallee()));
            }
        }
        $keys = explode('.', $key);
        $current = &$this->_options;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!isset($current[$keys[$keyIndex]])) {
                if (!is_array($current)) $current = [];
                $current[$keys[$keyIndex]] = [];
            }
            $current = &$current[$keys[$keyIndex]];
        }
        $old = $current;
        $current = $value;
        if(is_callable($this->_onOptionChange)){
            $this->_onOptionChange->bindTo($this);
            call_user_func($this->_onOptionChange,$key , $old, $value);
        }
        return $this;
    }

    /**
     * Shorthand for setOption()
     *
     * @param string $key
     * @param mixed $value
     * @param bool $overWrite
     * @return $this
     * @throws Exception
     */
    public function set(string $key, mixed $value, bool $overWrite=false ):static
    {
        $this->setOption($key, $value, $overWrite);
        return $this;
    }

    /**
     * @param string $key
     * @return void
     * @throws ConfigException
     */
    public function unsetOption(string $key): void
    {
        if (!$this->hasOption($key)) return;
        if(in_array($key, $this->getRequiredOptions())) {
            throw new ConfigException(sprintf($this->_unsetRequiredMessage, $key, $this->_getCallee()));
        }
        unset($this->_options[$key]);
    }

    /**
     * Checks if an option exists.
     *
     * @param  string $key The index of the option
     * @return bool        TRUE when there is an option with index __$key__, FALSE otherwise
     */
    public function hasOption(string $key): bool
    {
        $keys = explode('.', $key);
        $current = &$this->_options;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!is_array($current) || !isset($current[$keys[$keyIndex]])) {
                return false;
            }
            $current = &$current[$keys[$keyIndex]];
        }
        return true;
    }

    /**
     * Checks whether an _options equal the given value
     *
     * Returns _TRUE_ when option with index __$key__ is set and is of the same value
     * as __$value__, _FALSE_ otherwise.
     *
     * @param  string $key   The index of the option
     * @param  mixed  $value The value for the option
     * @return bool          TRUE when equal, FALSE otherwise
     * @throws ConfigException
     */
    public function optionEquals(string $key, mixed $value): bool
    {
        return ($this->hasOption($key) && $this->getOption($key) == $value);

    } // optionEquals(...)
    #endregion
}