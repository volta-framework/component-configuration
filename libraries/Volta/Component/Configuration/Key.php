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

Use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Key
{

    /**
     * The full name of the Configuration key including the key part separators
     * @var string
     */
    public readonly string $key;

    /**
     * The default value for the configuration key
     * @var mixed|null
     */
    public readonly mixed $default;

    /**
     * Description for the configuration key
     * @var string
     */
    public readonly string $description;

    /**
     * @param string $key
     * @param mixed|null $default
     * @param string $description
     */
    public function __construct(string $key,  mixed $default=null, string $description='')
    {
       $this->key = $key;
       $this->default= $default;
       $this->description= $description;
    }

    /**
     * @deprecated  Use Instance->key
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @deprecated Use Instance->default
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return string
     * @deprecated @deprecated Use Instance->description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Convert the key to an array
     *
     * <code>
     * [
     *     "part1" => [
     *         "part2" => "value"
     *     ]
     * ]
     * </code>
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        $keys = explode('.', $this->key);
        $current = &$options;
        foreach($keys as $subKey){
            $current[$subKey] = [];
            $current = &$current[$subKey];
        }
        $current = $this;
        return (array)$options;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultAsArray(): array
    {
        $options = [];
        $keys = explode('.', $this->key);
        $current = &$options;
        foreach($keys as $subKey){
            $current[$subKey] = [];
            $current = &$current[$subKey];
        }
        $current = $this->default;
        return (array)$options;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDescriptionAsArray(): array
    {
        $options = [];
        $keys = explode('.', $this->key);
        $current = &$options;
        foreach($keys as $subKey){
            $current[$subKey] = [];
            $current = &$current[$subKey];
        }
        $current = $this->description;
        return (array) $options;
    }

    /**
     * @return void
     */
    public function __invoke(): void
    {
        print($this);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $template = '"%s" => "%s"';
        if (is_numeric($this->default)){
            $template = '"%s" => %s,';
        }
        if($this->description != '' ) {
            $template .= ' // %s';
        } else {
            $template .= '%s';
        }
        return sprintf($template , $this->key, (string) $this->default, $this->description);
    }

}

