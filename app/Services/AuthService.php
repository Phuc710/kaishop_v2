<?php

/**
 * Authentication Service
 * Handles user authentication logic
 */
class AuthService
{
    private $userModel;
    private $authSecurity = null;
    private static $userCacheLoaded = false;
    private static $cachedUser = null;
    private static $cachedSessionToken = null;

    public function __construct()
    {
        $this->userModel = new User();
    }

    private function authSecurity()
    {
        if ($this->authSecurity !== null) {
            return $this->authSecurity;
        }

        if (!class_exists('AuthSecurityService')) {
            return null;
        }

        $this->authSecurity = new AuthSecurityService();
        return $this->authSecurity;
    }

    private function currentSessionToken()
    {
        return (string) ($_SESSION['session'] ?? '');
    }

    private function enforceActiveUser($user)
    {
        if (!is_array($user) || $user === []) {
            return null;
        }

        $authSecurity = $this->authSecurity();
        if (!$authSecurity) {
            return $user;
        }

        $validatedUser = $authSecurity->enforceActiveUser($user);
        if ($validatedUser === null) {
            $this->clearUserCache();
        }

        return $validatedUser;
    }

    private function loadUserFromSessionCache()
    {
        $session = $this->currentSessionToken();
        if ($session === '') {
            self::$userCacheLoaded = true;
            self::$cachedSessionToken = '';
            self::$cachedUser = null;
            return null;
        }

        if (self::$userCacheLoaded && self::$cachedSessionToken === $session) {
            return self::$cachedUser;
        }

        $user = $this->enforceActiveUser($this->userModel->findBySession($session) ?: null);
        self::$userCacheLoaded = true;
        self::$cachedSessionToken = $session;
        self::$cachedUser = $user;

        return self::$cachedUser;
    }

    private function storeUserCache($user)
    {
        self::$userCacheLoaded = true;
        self::$cachedSessionToken = $this->currentSessionToken();
        self::$cachedUser = $user;
    }

    private function clearUserCache()
    {
        self::$userCacheLoaded = true;
        self::$cachedSessionToken = '';
        self::$cachedUser = null;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn()
    {
        if ($this->currentSessionToken() !== '') {
            $user = $this->loadUserFromSessionCache();
            if ($user) {
                return true;
            }
            unset($_SESSION['session']);
            $this->clearUserCache();
        }

        $authSecurity = $this->authSecurity();
        if ($authSecurity) {
            $user = $authSecurity->bootstrapFromCookies();
            $this->storeUserCache($user ?: null);
            return $user !== null;
        }

        return false;
    }

    /**
     * Get current logged in user
     * @return array|null
     */
    public function getCurrentUser()
    {
        $user = $this->loadUserFromSessionCache();
        if ($user) {
            return $user;
        }

        // If a session token exists but no user found, clear invalid legacy session.
        if ($this->currentSessionToken() !== '') {
            unset($_SESSION['session']);
            $this->clearUserCache();
        }

        $authSecurity = $this->authSecurity();
        if ($authSecurity) {
            $user = $authSecurity->bootstrapFromCookies();
            $this->storeUserCache($user ?: null);
            return $user;
        }

        return null;
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public function getUserId()
    {
        $user = $this->getCurrentUser();
        return $user ? $user['id'] : null;
    }

    /**
     * Require authentication (redirect if not logged in)
     */
    public function requireAuth()
    {
        if ($this->getCurrentUser() === null) {
            header('Location: ' . url('login'));
            exit;
        }
    }

    public function logout()
    {
        $authSecurity = $this->authSecurity();
        if ($authSecurity) {
            $authSecurity->revokeCurrentAuthSessionFromCookies();
        }
        $this->clearUserCache();
        session_destroy();
    }
}
