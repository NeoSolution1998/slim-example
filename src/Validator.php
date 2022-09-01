<?php

namespace App;

class Validator implements ValidatorInterface
{
    public function validate(array $user)
    {
        $errors = [];
        if ($user['name'] === '') {
            $errors['name'] = "Заполните поле";
        }

        if (empty($user['email'])) {
            $errors['email'] = "Заполните поле";
        }

        if (empty($user['password'])) {
            $errors['password'] = "Заполните поле";
        }

        return $errors;
    }
}
