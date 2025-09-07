<?php

namespace SecureAuth\validation;

use SecureAuth\Security\Csrf;

class ValidatorExtend
{

    // check if password contains at least one uppercase , lowercase letter also contains one  number and  one symbol
    public static function strongpassword($validator)
    {

        $validator->extend('strong_password', function ($attribute, $value, $parameters, $validator) {
            $errors = [];
            if (!preg_match('/[A-Z]/', $value)) $errors[] = 'at least one uppercase letter';
            if (!preg_match('/[a-z]/', $value)) $errors[] = 'at least one lowercase letter';
            if (!preg_match('/[0-9]/', $value)) $errors[] = 'at least one number';
            if (!preg_match('/[\W_]/', $value)) $errors[] = 'at least one symbol';

            if (!empty($errors)) {
                $validator->errors()->add($attribute, 'Password must contain ' . implode(', ', $errors) . '.');
                return false;
            }
            return true;
        });
    }

    // validate csrf token
    public static function verifyCSRFtoken($validator)
    {
        $validator->extend('verify_csrftoken', function ($attribute, $value, $parameters, $validator) {
            if (!Csrf::verify($value)) {
                $validator->errors()->add($attribute, 'The CSRF token is invalid.');
                return false; // token verification failed
            }
            return true; // token is valid
        });
    }
}
