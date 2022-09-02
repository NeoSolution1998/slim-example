<?php 

namespace App;

class UserRepository
{
    public function __construct()
    {
        session_start();
    }

    public function all()
    {
        return array_values($_SESSION);
    }

    public function find(string $id)
    {
        if (!isset($_SESSION[$id])) {
            throw new \Exception("Wrong course id: {$id}");
        }

        return $_SESSION[$id];
    }

    public function destroy(string $id)
    {
        unset($_SESSION[$id]);
    }

    public function save(array $item)
    {
        if (empty($item['name']) || $item['email'] === '' || $item['password'] === "") {
            $json = json_encode($item);
            throw new \Exception("Wrong data: {$json}");
        }
        $item['id'] = uniqid();
        $_SESSION[$item['id']] = $item;
    }
}