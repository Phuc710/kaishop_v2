<?php

/**
 * Auth Validator
 * Validates authentication inputs
 */
class AuthValidator {
    
    /**
     * Validate login credentials
     */
    public function validateLogin($username, $password) {
        $errors = [];
        
        if (empty($username) || empty($password)) {
            $errors[] = 'Please enter all required fields';
        }
        
        return $errors;
    }
    
    /**
     * Validate registration data
     */
    public function validateRegister($username, $password, $email) {
        $errors = [];
        
        // Required fields
        if (empty($username) || empty($password) || empty($email)) {
            $errors[] = 'Please enter all required fields';
            return $errors;
        }
        
        // Username format (alphanumeric only)
        if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
            $errors[] = 'Username must contain only letters and numbers';
        }
        
        // Username = password check
        if ($username === $password) {
            $errors[] = 'Username and password cannot be the same';
        }
        
        // Email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        return $errors;
    }
    
    /**
     * V alidate change password
     */
    public function validateChangPassword($oldPassword, $newPassword, $confirmPassword) {
        $errors = [];
        
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'Please enter all required fields';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        if (strlen($newPassword) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        return $errors;
    }
}
