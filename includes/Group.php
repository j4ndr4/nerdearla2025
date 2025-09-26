<?php

/**
 * Abstract class for managing user groups within courses or sections
 */
abstract class Group
{
    /**
     * Get the group/subgroup that a user belongs to within a given course
     *
     * @param string $courseId The ID of the course
     * @param string $userId The ID of the user (e.g., student profile ID)
     * @return string The subgroup/cell name the user belongs to
     */
    abstract public function getUserGroup(string $courseId, string $userId): string;
}

require_once __DIR__.'/LocalFileGroup.php';
