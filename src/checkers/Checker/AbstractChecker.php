<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;

abstract class AbstractChecker implements Checker
{
    protected $code = '';

    /**
     * @param string $check_var
     * @param string $checker_type
     * @param array $args
     * @return Checker
     * @throws \RuntimeException
     */
    public static function make(string $check_var, string $checker_type, array $args): Checker
    {
        if ($class = Checker::MAP[$checker_type] ?? null) {
            array_unshift($args, $check_var);

            return new $class(...$args);
        }

        throw new \RuntimeException('Unexpected checker');
    }

    public function getValidationError(string $pattern, array $params = []): string
    {
        return ("throw new \RuntimeException('Validation error: " . sprintf($pattern, ...$params) . "');");
    }

    protected function inclusive($property, $value, $is_min = true, $inclusive = false)
    {
        $sing = $is_min ? '>' : '<';
        $sing .= ($inclusive ? '=' : '');

        $sing_word = $is_min ? 'greater' : 'less';
        $sing_word .= ($inclusive ? ' or equal' : '');

        $this->code = <<<CODE
if(!is_numeric({$property}) || !({$property} {$sing} {$value})){
    {$this->getValidationError('The value "%s" must be %s than "%s"', ["'.$property.'", $sing_word, $value])}
}
CODE;
    }

    public function render(): string
    {
        return $this->code;
    }
}