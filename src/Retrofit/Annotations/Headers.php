<?php
/**
 * Created by PhpStorm.
 * User: lizhaoguang
 * Date: 16/7/9
 * Time: 下午6:47
 */

namespace Retrofit\Annotations;

/**
 * Class Method
 * @package Retrofit\Annotations
 * @Annotation
 * @Target({"METHOD"})
 */
class Headers
{
    /**
     * @var array<string>
     */
    public $headers;

    static function __set_state($values)
    {
        $r = new self();
        $r->headers = $values['headers'];
        return $r;
    }
}