<?php
/*
 * Copyright 2025 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;

/**
 * Credentials that generate RS256 JWT Bearer tokens with a kid header.
 *
 * This class generates JWT tokens signed with RS256 algorithm using a private key.
 * The kid (Key ID) is included in the JWT header for token validation.
 */
class JwtBearerCredentials implements FetchAuthTokenInterface
{
    /**
     * @var string The Key ID (kid) to include in JWT header
     */
    private $kid;

    /**
     * @var string The RSA private key in PEM format
     */
    private $privateKeyPem;

    /**
     * @var JWT JWT service instance
     */
    private $jwt;

    /**
     * @var array|null Last generated token and expiration
     */
    private $lastToken;

    /**
     * @param string $kid The Key ID (kid) to include in JWT header
     * @param string $privateKeyPem The RSA private key in PEM format
     * @param JWT|null $jwt Optional JWT service instance
     */
    public function __construct($kid, $privateKeyPem, ?JWT $jwt = null)
    {
        if (empty($kid)) {
            throw new InvalidArgumentException('kid is required');
        }
        if (empty($privateKeyPem)) {
            throw new InvalidArgumentException('privateKeyPem is required');
        }

        $this->kid = $kid;
        $this->privateKeyPem = $privateKeyPem;
        $this->jwt = $jwt ?: $this->getJwtService();
    }

    /**
     * Generate a JWT Bearer token with custom claims.
     *
     * @param array $claims JWT claims (iss, sub, exp, iat, etc.)
     * @return string The signed JWT token
     */
    public function generateToken(array $claims = [])
    {
        // Set default expiration (1 hour from now) if not provided
        $now = time();
        if (!isset($claims['exp'])) {
            $claims['exp'] = $now + 3600;
        }
        if (!isset($claims['iat'])) {
            $claims['iat'] = $now;
        }

        // Create header with kid
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $this->kid,
        ];

        // Encode and sign the token
        $token = JWT::encode($claims, $this->privateKeyPem, 'RS256', null, $header);

        // Store last token for getLastReceivedToken()
        $this->lastToken = [
            'access_token' => $token,
            'expires_at' => $claims['exp'],
        ];

        return $token;
    }

    /**
     * Fetches auth tokens. Required by FetchAuthTokenInterface.
     *
     * For JWT Bearer tokens, this generates a basic token with minimal claims.
     * For custom claims, use generateToken() directly.
     *
     * @param callable|null $httpHandler Unused for JWT generation
     * @return array Array containing 'access_token'
     */
    public function fetchAuthToken(?callable $httpHandler = null)
    {
        // Generate a basic token with minimal claims
        // In practice, you should use generateToken() with specific claims
        $token = $this->generateToken([
            'iss' => 'jwt-bearer',
            'sub' => 'jwt-bearer',
        ]);

        return [
            'access_token' => $token,
        ];
    }

    /**
     * Gets a cache key for the credentials.
     *
     * @return string Cache key based on kid
     */
    public function getCacheKey()
    {
        return 'jwt_bearer_' . md5($this->kid);
    }

    /**
     * Returns the last received token.
     *
     * @return array|null Last token with access_token and expires_at, or null
     */
    public function getLastReceivedToken()
    {
        return $this->lastToken;
    }

    /**
     * Get the Key ID (kid).
     *
     * @return string
     */
    public function getKid()
    {
        return $this->kid;
    }

    /**
     * Get a JWT service instance.
     *
     * @return JWT
     */
    private function getJwtService()
    {
        $jwt = new JWT();
        if ($jwt::$leeway < 1) {
            // Ensures JWT leeway is at least 1
            // @see https://github.com/google/google-api-php-client/issues/827
            $jwt::$leeway = 1;
        }
        return $jwt;
    }
}
