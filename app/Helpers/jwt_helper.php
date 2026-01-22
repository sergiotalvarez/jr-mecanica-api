<?php 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Genera un token JWT para un usuario.
 * Esta función la usaremos en el futuro controlador de Login.
 */
function getJWTForUser(string $email, int $userId)
{
    $issuedAtTime = time();
    // Accedemos a la variable de entorno con el nombre correcto
    $tokenTimeToLive = $_SERVER['JWT_TOKEN_LIVE_SECONDS']; 
    $tokenExpiration = $issuedAtTime + $tokenTimeToLive;

    $payload = [
        'email' => $email,
        'iat' => $issuedAtTime, // Issued at: ¿Cuándo se emitió el token?
        'exp' => $tokenExpiration, // Expiration time: ¿Cuándo expira el token?
        'uid' => $userId // User ID: El ID del usuario logueado
    ];

    // Accedemos a la clave secreta con el nombre correcto
    $secretKey = $_SERVER['JWT_SECRET_KEY'];
    $jwt = JWT::encode($payload, $secretKey, 'HS256');

    return $jwt;
}

/**
 * Valida un token JWT desde la cabecera 'Authorization' de la petición actual.
 */
function validateJWTFromRequest()
{
    $request = service('request');
    $authHeader = $request->getHeaderLine('Authorization');
    
    // Si no hay cabecera de autorización, no hay token.
    if (empty($authHeader)) {
        return null;
    }

    // El formato esperado es "Bearer <token>"
    $tokenParts = explode(' ', $authHeader);
    if (count($tokenParts) !== 2 || $tokenParts[0] !== 'Bearer') {
        return null;
    }
    $encodedToken = $tokenParts[1];

    // Usamos $_SERVER y el nombre correcto de la variable del .env
    $secretKey = $_SERVER['JWT_SECRET_KEY'];

    try {
        $decoded = JWT::decode($encodedToken, new Key($secretKey, 'HS256'));
        return $decoded;
    } catch (\Exception $e) {
        // En un entorno de producción, es bueno registrar el error para investigarlo.
        log_message('error', '[JWT] Error de decodificación: ' . $e->getMessage());
        return null;
    }
}