<?php

namespace SecureAuth\validation;

use SecureAuth\validation\ValidatorExtend;

class ValidatorMessages
{
    public  $validator;

    public function __construct($validator)
    {
        $this->validator = $validator;
    }

    public  function validationMessage(): array
    {

        $customMessgaes = [
            'required' => ':attribute field is required',
            'email' => ':attribute field content is in valid',
            'min' => [
                'string' => ':attribute must be at least :min characters'
            ],
            'strong_password' => ValidatorExtend::strongpassword($this->validator),
            'verify_csrftoken' => ValidatorExtend::verifyCSRFtoken($this->validator)
        ];

        return  $customMessgaes;
    }
}
