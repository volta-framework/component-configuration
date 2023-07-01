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
     * @var string
     */
    private string $_key;

    /**
     * @var mixed|null
     */
    private mixed $_default;

    /**
     * @var string
     */
    private string $_description;

    /**
     * @param string $key
     * @param mixed|null $default
     * @param string $description
     */
    public function __construct(string $key,  mixed $default=null, string $description='')
    {
       $this->_key = $key;
       $this->_default= $default;
       $this->_description= $description;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->_key;
    }

    /**
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->_default;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->_description;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        $keys = explode('.', $this->getKey());
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
        $keys = explode('.', $this->getKey());
        $current = &$options;
        foreach($keys as $subKey){
            $current[$subKey] = [];
            $current = &$current[$subKey];
        }
        $current = $this->getDefault();
        return (array)$options;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDescriptionAsArray(): array
    {
        $options = [];
        $keys = explode('.', $this->getKey());
        $current = &$options;
        foreach($keys as $subKey){
            $current[$subKey] = [];
            $current = &$current[$subKey];
        }
        $current = $this->getDescription();
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
        if (is_numeric($this->getDefault())){
            $template = '"%s" => %s,';
        }
        if($this->getDescription() != '' ) {
            $template .= ' // %s';
        } else {
            $template .= '%s';
        }
        return sprintf($template , $this->getKey(), (string) $this->getDefault(), $this->getDescription());
    }

}

