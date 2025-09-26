<?php

require_once 'Group.php';

class LocalFileGroup extends Group
{
    /**
     * Get the user group based on courseId and userId by reading from resources/groups.csv.
     * CSV format: courseId,userId,groupName
     *
     * @param string $courseId The course ID
     * @param string $userId The user ID
     * @return string The group name, or 'default' if not found
     */
    public function getUserGroup(string $courseId, string $userId): string
    {
        $filePath = __DIR__ . '/../resources/groups.csv';

        if (!file_exists($filePath)) {
            return 'default';
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return 'default';
        }

        while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
            if (count($data) >= 3 && trim($data[0]) === $courseId && trim($data[1]) === $userId) {
                fclose($handle);
                return trim($data[2]);
            }
        }

        fclose($handle);
        return 'default';
    }
}
