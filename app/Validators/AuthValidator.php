<?php

/**
 * Auth Validator
 * Validates authentication inputs
 */
class AuthValidator
{

    /**
     * Validate login credentials
     */
    public function validateLogin($username, $password)
    {
        $errors = [];

        if (empty($username) || empty($password)) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
        }

        return $errors;
    }

    /**
     * Validate registration data
     */
    public function validateRegister($username, $password, $email)
    {
        $errors = [];

        // Required fields
        if (empty($username) || empty($password) || empty($email)) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
            return $errors;
        }

        // Username format (alphanumeric only)
        if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
            $errors[] = 'Tên đăng nhập chỉ được gồm chữ cái và số.';
        }

        // Username = password check
        if ($username === $password) {
            $errors[] = 'Tên đăng nhập và mật khẩu không được trùng nhau.';
        }

        // Password length check
        if (strlen($password) < 6 || strlen($password) > 20) {
            $errors[] = 'Mật khẩu phải từ 6 đến 20 ký tự.';
        }

        // Email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không đúng định dạng.';
        }

        return $errors;
    }

    /**
     * Validate change password
     */
    public function validateChangePassword($oldPassword, $newPassword, $confirmPassword)
    {
        $errors = [];

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
            return $errors;
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không khớp.';
        }

        return $errors;
    }
}
