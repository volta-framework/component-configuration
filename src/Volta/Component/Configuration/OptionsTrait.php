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

use Volta\Component\Configuration\Config;
use Closure;
use Volta\Component\Configuration\Exception as Exception;

/** 
 * Set of methods to manage _options in a class/object
 *
 * @package libraries\Volta
 * @author Rob <rob@jaribio.com>
 */
trait OptionsTrait
{
     
    // The first placeholder(%s) is the name of the option key,
    // the second the name of the class the Trait is being used in.
    protected string $_requiredMissingMessage = 'Required option "%s" is missing in "%s::_options"!';
    protected string $_notAllowedMessage      = 'Option "%s" not allowed in "%s::_options"!';
    protected string $_optionNotFoundMessage  = 'Option "%s" not found in "%s::_options" and no default value provided.';
    protected string $_alreadySetMessage      = 'Option "%s" already set in "%s::_options"!';

    /**
     * @ignore (do not show up in generated documentation)
     * @var string[] If not empty only these _options keys are required
     */
    protected array $_requiredOptions = [];
    
    /**
     * @ignore (do not show up in generated documentation)
     * @var string[] White list _options. If not empty only these _options are allowed
     */
    protected array $_allowedOptions = [];

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
     * @param array<string, mixed> $_options
     * @return object The current instance of the class implementing this trait
     * @throws Exception
     */
    public function setOptions(array $_options): object
    {
        foreach($this->_requiredOptions as $key) {
            if(!array_key_exists($key, $_options)){
                 throw new Exception(sprintf($this->_requiredMissingMessage, $key, $this->_getCallee()));
            }
        }       
        if (count($this->_allowedOptions)) {
            foreach(array_keys($_options) as $key) {
                if(!in_array($key, $this->_allowedOptions)){
                     throw new Exception(sprintf($this->_notAllowedMessage, $key, $this->_getCallee()));
                }
            }
        }
        $this->_options = $_options;
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
     * @throws Exception
     */
    public function getOption(string $key, mixed $default=null): mixed
    {
        if (count($this->_allowedOptions)) {
            if (!in_array($key, $this->_allowedOptions)){
                throw new Exception(sprintf($this->_notAllowedMessage, $key, $this->_getCallee()));
            }
        }
        $keys = explode('.', $key);
        $current = &$this->_options;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!is_array($current) || !isset($current[$keys[$keyIndex]])) {
                if (null===$default) {
                    throw new Exception(sprintf($this->_optionNotFoundMessage, $key, $this->_getCallee()));
                }
                return $default;
            }
            $current = &$current[$keys[$keyIndex]];
        }
        return $current;
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
     * @throws Exception         When $overWrite is false and the option already exists
     */
    public function setOption(string $key, mixed $value, bool $overWrite=false ): Static
    {
        if ($this->hasOption($key) && !$overWrite) {
            throw new Exception(sprintf($this->_alreadySetMessage, $key, $this->_getCallee()));
        }
        if (count($this->_allowedOptions)) {
            if (!in_array($key, $this->_allowedOptions)){
                throw new Exception(sprintf($this->_notAllowedMessage, $key, $this->_getCallee()));
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
     * @param string $key
     * @return void
 * @throws \Volta\Component\Configuration\Exception
     */
    public function unsetOption(string $key): void
    {
        if ($this->hasOption($key)) return;
        if(in_array($key, $this->getRequiredOptions())) {
            throw new Exception(sprintf('Cannot unset a required option "%s"', $key));
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
     * @throws Exception
     */
    public function optionEquals(string $key, mixed $value): bool
    {
        return ($this->hasOption($key) && $this->getOption($key) == $value);
        
    } // optionEquals(...)
   
    
} // trait
