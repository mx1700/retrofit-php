<?php
/**
 * Created by PhpStorm.
 * User: lizhaoguang
 * Date: 16/7/9
 * Time: 上午11:07
 */

namespace Retrofit\Annotations;

/**
 * Class Method
 * @package Retrofit\Annotations
 * @Annotation
 * @Target({"METHOD"})
 */
class Timeout
{
    /**
     * @var integer
     */
    public $second;

    static function __set_state($values)
    {
        $r = new self();
        $r->second = $values['second'];
        return $r;
    }
}