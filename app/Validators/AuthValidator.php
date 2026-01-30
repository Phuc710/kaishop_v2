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
            $errors[] = 'Vui lòng nhập đầy đủ thông tin';
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
            $errors[] = 'Vui lòng nhập đầy đủ thông tin';
            return $errors;
        }
        
        // Username format (alphanumeric only)
        if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
            $errors[] = 'Tên đăng nhập không bao gồm các kí tự đặc biệt và có dấu';
        }
        
        // Username = password check
        if ($username === $password) {
            $errors[] = 'Tên Đăng Nhập Không Được Trùng Mật Khẩu!';
        }
        
        // Email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email Không Đúng Định Dạng!';
        }
        
        return $errors;
    }
    
    /**
     * V alidate change password
     */
    public function validateChangPassword($oldPassword, $newPassword, $confirmPassword) {
        $errors = [];
        
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu mới không khớp';
        }
        
        if (strlen($newPassword) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }
        
        return $errors;
    }
}
