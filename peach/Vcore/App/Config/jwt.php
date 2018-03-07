<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2016/9/22
 * Time: 12:52
 */
if (!defined('BASEDIR')) {
    exit('File not found');
}
return [
    'alg' => 'HS256',
    'key' => 'testkey',
    'alg_classes' => [
        'HS256' => \Lcobucci\JWT\Signer\Hmac\Sha256::class,
        'HS384' => \Lcobucci\JWT\Signer\Hmac\Sha384::class,
        'HS512' => \Lcobucci\JWT\Signer\Hmac\Sha512::class,
        'RS256' => \Lcobucci\JWT\Signer\Rsa\Sha256::class,
        'RS384' => \Lcobucci\JWT\Signer\Rsa\Sha384::class,
        'RS512' => \Lcobucci\JWT\Signer\Rsa\Sha512::class,
        'ES256' => \Lcobucci\JWT\Signer\Ecdsa\Sha256::class,
        'ES384' => \Lcobucci\JWT\Signer\Ecdsa\Sha384::class,
        'ES512' => \Lcobucci\JWT\Signer\Ecdsa\Sha512::class,
    ],
    'expire'=>3650*24*60*60,//过期时间（秒）
];