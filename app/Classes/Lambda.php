<?php namespace App\Classes;

use ReflectionFunction;

class Lambda
{
    private $f;
    private $args;
    private $count;

    public static function instance($f, $args = [])
    {
        return new Lambda($f, $args);
    }

    public function __construct($f, $args = false)
    {
        if ($f instanceof lambda) {
            $this->f = $f->f;
            $this->count = $f->count;
            $this->args = array_merge($f->args, is_array($args) ? $args : []);
        }
        else {
            $this->f = $f;
            $this->count = is_numeric($args) ? $args : count((new ReflectionFunction($f))->getParameters());
            $this->args = is_array($args) ? $args : [];
        }
    }

    public function __invoke()
    {
        if (count($this->args) + func_num_args() < $this->count) {
            return new lambda($this, func_get_args());
        }
        else {
            $args = array_merge($this->args, func_get_args());
            $r = call_user_func_array($this->f, array_splice($args, 0, $this->count));
            return is_callable($r) ? call_user_func(new lambda($r, $args)) : $r;
        }
    }
}

function lambda($f)
{
    return new Lambda($f);
}
