<?php

namespace SecureAuth\validation;

use Illuminate\Validation\Factory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Container\Container;

class Validator
{
    private Factory $factory;
    private array $errors = [];

    public function __construct()
    {
        // Setup translator (required by Laravel validation)
        $translator = new Translator(new ArrayLoader(), 'en');
        // Setup validator factory
        $this->factory = new Factory($translator, new Container());
    }

    /**
     * Validate input data against rules
     *
     * @param array $data  Associative array of input data
     * @param array $rules Associative array of validation rules
     * @param array $messages Optional custom error messages
     * @return bool True if passes, false if fails
     */
    public function validate(array $data, array $rules, array $messages = []): bool
    {
        // If no messages are provided, use default translator messages
        $validator = $this->factory->make($data, $rules, $messages);

        if ($validator->fails()) {
            $this->errors = $validator->errors()->all();
            return false;
        }

        return true;
    }

    /**
     * Return validation errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Add custom rule at runtime
     *
     * @param string $name
     * @param callable $callback
     * @param string|null $message
     */
    public function extend(string $name, callable $callback, string $message = null): void
    {
        $this->factory->extend($name, $callback, $message);
    }
}
