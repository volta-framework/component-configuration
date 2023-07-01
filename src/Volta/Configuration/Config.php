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

use Volta\Component\Configuration\Key;
use Volta\Component\Configuration\OptionsTrait;
use ArrayAccess;
use Closure;
use JsonSerializable;
use Volta\Component\Configuration\Exception as ConfigException;
use Volta\Component\Configuration\Exception;
use ReflectionClass;
use ReflectionException;

/**
 * Class Config
 * Class for storing name value pairs
 * @implements ArrayAccess<string, mixed>
 * @package Volta
 */
class Config implements ArrayAccess, JsonSerializable
{

    /**
     * Setting, Getting option values
     * @see OptionsTrait
     */
    use OptionsTrait;

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
     * 1. A PHP file returning an array
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
        if (is_string($options)) { // can be a file or json string
            if (file_exists($options)) { // if not assume a json string
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
        $this->setOptions($options); // on this point the options variable will be an array
        $this->_onOptionChange = $onOptionChangeCallback;
    }

    /**
     * @ignore Do not show up in generated documentation
     * @param string $json
     * @return array<string, mixed>
     * @throws Exception
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

    #endregion

    #region - Options Configuration:

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

    /**
     * Returns the list with allowed options
     *
     * @return string[]
     */
    public function getAllowedOptions(): array
    {
        return $this->_allowedOptions;
    }

    /**
     * Sets the list with allowed options
     *
     * @param string[] $allowedOptions
     * @return static
     */
    public function setAllowedOptions(array $allowedOptions): static
    {
        $this->_allowedOptions = $allowedOptions;
        return $this;
    }

    #endregion

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
     * @throws Exception
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getOption($offset);
    }

    /**
     * @inheritdoc
     * @see https://www.php.net/manual/en/arrayaccess.offsetSet.php
     * @throws Exception
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setOption($offset, $value);
    }

    /**
     * @inheritdoc
     * @see https://www.php.net/manual/en/arrayaccess.offsetUnset.php
     * @throws Exception
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->unsetOption($offset);
    }

    #endregion

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
        } catch(\Throwable $e)
        {
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

        return $numberOfKeysFound;;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): mixed
    {           
        return $this->getOptions();
    }

    #endregion
}