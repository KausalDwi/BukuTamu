<?php
/**
 * Helper sederhana untuk penanganan CSRF token.
 * Pastikan session_start() sudah dipanggil sebelum menggunakan fungsi ini.
 */

if (!function_exists('csrf_generate_token')) {
    /**
     * Menghasilkan token CSRF dan menyimpannya di session.
     *
     * @param string $tokenKey Kunci unik per form/aksi.
     * @return string Token yang bisa disisipkan ke form.
     */
    function csrf_generate_token(string $tokenKey = 'default'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session belum dimulai. Panggil session_start() terlebih dahulu.');
        }

        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        if (empty($_SESSION['csrf_tokens'][$tokenKey])) {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (Throwable $e) {
                // Fallback jika random_bytes tidak tersedia
                $token = bin2hex(openssl_random_pseudo_bytes(32));
            }

            $_SESSION['csrf_tokens'][$tokenKey] = $token;
        }

        return $_SESSION['csrf_tokens'][$tokenKey];
    }
}

if (!function_exists('csrf_validate_token')) {
    /**
     * Memvalidasi token CSRF dari request.
     *
     * @param string|null $token Token yang diterima dari form.
     * @param string $tokenKey Kunci unik per form/aksi.
     * @param bool $invalidateAfterSuccess Hapus token setelah validasi berhasil.
     * @return bool True jika valid, false jika tidak.
     */
    function csrf_validate_token(?string $token, string $tokenKey = 'default', bool $invalidateAfterSuccess = true): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        if (!isset($_SESSION['csrf_tokens'][$tokenKey])) {
            return false;
        }

        $isValid = is_string($token) && hash_equals($_SESSION['csrf_tokens'][$tokenKey], $token);

        if ($isValid && $invalidateAfterSuccess) {
            unset($_SESSION['csrf_tokens'][$tokenKey]);
        }

        return $isValid;
    }
}
