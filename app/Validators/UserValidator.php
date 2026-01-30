<?php

/**
 * User Validator
 * Validates user input
 */
class UserValidator {
    
    /**
     * Validate email
     * @param string $email
     * @return array Errors array (empty if valid)
     */
    public function validateEmail($email) {
        $errors = [];
        
        if (empty($email)) {
            $errors[] = 'Please enter email';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        return $errors;
    }
    
    /**
     * Validate profile update data
     * @param array $data
     * @return array Errors array
     */
    public function validateProfileUpdate($data) {
        $errors = [];
        
        // Validate email
        if (isset($data['email'])) {
            $emailErrors = $this->validateEmail($data['email']);
            $errors = array_merge($errors, $emailErrors);
        }
        
        return $errors;
    }
}
