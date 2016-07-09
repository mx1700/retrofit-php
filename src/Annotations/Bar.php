<?php
namespace Retrofit\Annotations;

/**
 * Class Bar
 * @package Retrofit\Annotations
 * @Annotation
 * @Target({"METHOD","PROPERTY", "CLASS"})
 */
class Bar
{
    private $foo;
    public function __construct(array $values)
    {
        $this->foo = $values['foo'];
    }

    static function __set_state($values)
    {
        return new Bar(['foo' => $values['foo']]);
    }
}