# Library

The Configuration component counts 3 classes all in the namespace *Volta\Component\Configuration*:

1. Volta\Component\Configuration\Config
2. Volta\Component\Configuration\Key
3. Volta\Component\Configuration\Exception

## ~\Config

```mermaid
classDiagram
    direction LR
    class Config
    class ArrayAccess
    class JsonSerializable
    
    Config <|.. ArrayAccess: implements
    Config <|.. JsonSerializable: implements
    
```

## ~\Key

```mermaid
classDiagram
    class Key{ 
        <<attribute>> 
        +  &laquo;readonly&raquo; key:string 
        +  &laquo;readonly&raquo; default:mixed = NULL
        +  &laquo;readonly&raquo; description:string = &ldquo;&rdquo;
    }        
```

## ~\Exception

Basic Exception for all exceptions thrown in this Component.

```mermaid
classDiagram
    direction TB
    class Exception
    class Std_Exception
    class Stringable {
        &lt;&lt;interface&gt;&gt;
    }
    class Throwable {
        &lt;&lt;interface&gt;&gt;
    }
    
    Std_Exception  <|-- Exception : extends
    Stringable ..|> Throwable : implements
    Throwable  ..|> Std_Exception : implements
```