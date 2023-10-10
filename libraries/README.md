# Library

The Configuration component counts 3 classes all in the namespace *Volta\Component\Configuration*:

1. Volta\Component\Configuration\Config
2. Volta\Component\Configuration\Key
3. Volta\Component\Configuration\Exception

## UML Class Diagram
```mermaid
classDiagram

    note "SPL = Standard PHP Library"
    namespace Spl {
        class _Throwable {
            &lt;&lt;interface&gt;&gt;
        }
        class _Stringable {
            &lt;&lt;interface&gt;&gt;
        }
        class _Exception
        class _Attribute
        class _ArrayAccess
        class _JsonSerializable
    }

    link Config "https://github.com/volta-framework/component-configuration/blob/main/libraries/Volta/Component/Configuration/Config.php"

    link Key "https://github.com/volta-framework/component-configuration/blob/main/libraries/Volta/Component/Configuration/Key.php"

    link Exception "https://github.com/volta-framework/component-configuration/blob/main/libraries/Volta/Component/Configuration/Exception.php"

    note for Config "Only the most used class members are displayed"
    
    namespace Volta-Component-Configuration {
        class Exception        
        class Key{            
            &lt;&lt;Attribute&gt;
        }
        
        class Config{
            +getOption($key:string, $default:mixed):mixed
            +setOption($key:string, $value:mixed, $overWrite: bool = false):mixed
            +hasOption($key: string): bool
            +setRequiredOptions($requiredOptions: string[]): self
            +setAllowedOptions($allowedOptions: string[]): self
            +setOptions($options: array&lt;string, mixed&gt;|string): self
        }
    }

    _Throwable <|.. _Stringable  : implements
    Exception  --|> _Exception  : extends
    _Throwable  ..|> _Exception : implements
    Key --|>  _Attribute  : extends
    Config ..> Exception : throws
    Config ..> _ArrayAccess : implements
    Config ..> _JsonSerializable : implements
    
```
<small>* *Only the most used class members are displayed in the UML ClassDiagram* </small>\
<small>* *SPL = Standard PHP Library* </small>