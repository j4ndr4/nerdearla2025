<?php

abstract class User
{
    /**
     * Get the user role based on the provided email.
     *
     * @param string $email The user's email address.
     * @return string The user role (e.g., 'student', 'teacher', 'coordinator').
     */
    abstract public function getRole(string $email): string;
}

require_once __DIR__.'/LocalFileUser.php';
