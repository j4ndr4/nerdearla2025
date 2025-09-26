<?php

require_once 'User.php';

class LocalFileUser extends User
{
    /**
     * Get the user role based on the provided email by reading from resources/roles.csv.
     *
     * @param string $email The user's email address.
     * @return string The user role (e.g., 'student', 'teacher', 'coordinator'). Defaults to 'student' if not found.
     */
    public function getRole(string $email): string
    {
        $filePath = __DIR__ . '/../resources/roles.csv';

        if (!file_exists($filePath)) {
            return 'student';
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return 'student';
        }

        while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
            if (count($data) >= 2 && trim($data[0]) === $email) {
                fclose($handle);
                return strtolower(trim($data[1]));
            }
        }

        fclose($handle);
        return 'student';
    }
}
