<?php

/**
 * JwtHelper - Xử lý JWT thuần PHP (HMAC-SHA256)
 * Không cần thư viện bên ngoài
 */
class JwtHelper
{
    /**
     * Secret key dùng để ký JWT
     * Trong production nên đặt trong biến môi trường
     */
    private static string $secretKey = 'CINEMAX_JWT_SECRET_KEY_2026_!@#$%^&*';

    /**
     * Thời gian sống mặc định của token (giây) - 7 ngày
     */
    private static int $defaultTTL = 604800; // 7 * 24 * 60 * 60

    /**
     * Encode chuỗi sang Base64 URL-safe
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode chuỗi Base64 URL-safe
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Tạo JWT token
     *
     * @param array $payload Dữ liệu cần mã hóa (user_id, email, role_id, ...)
     * @param int|null $ttl Thời gian sống (giây), null = dùng mặc định
     * @return string JWT token
     */
    public static function encode(array $payload, ?int $ttl = null): string
    {
        // Header
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        // Thêm iat (issued at) và exp (expiration)
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + ($ttl ?? self::$defaultTTL);

        // Payload
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Signature
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", self::$secretKey, true)
        );

        return "$header.$payloadEncoded.$signature";
    }

    /**
     * Giải mã và xác thực JWT token
     *
     * @param string $token JWT token
     * @return array|null Payload nếu hợp lệ, null nếu không
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        // JWT phải có đúng 3 phần
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payloadEncoded, $signature] = $parts;

        // Xác thực chữ ký
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", self::$secretKey, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null; // Chữ ký không hợp lệ
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            return null;
        }

        // Kiểm tra hết hạn
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token đã hết hạn
        }

        return $payload;
    }

    /**
     * Lấy thời gian sống mặc định (giây)
     */
    public static function getDefaultTTL(): int
    {
        return self::$defaultTTL;
    }
}

