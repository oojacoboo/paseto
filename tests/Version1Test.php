<?php
namespace ParagonIE\PAST\Tests;

use ParagonIE\ConstantTime\Binary;
use ParagonIE\PAST\Keys\AsymmetricPublicKey;
use ParagonIE\PAST\Keys\AsymmetricSecretKey;
use ParagonIE\PAST\Keys\SymmetricAuthenticationKey;
use ParagonIE\PAST\Keys\SymmetricEncryptionKey;
use ParagonIE\PAST\Protocol\Version1;
use PHPUnit\Framework\TestCase;

class Version1Test extends TestCase
{
    /**
     * @covers Version1::auth()
     * @covers Version1::verify()
     */
    public function testAuth()
    {
        $key = new SymmetricAuthenticationKey(random_bytes(32));
        $year = (int) (\date('Y')) + 1;
        $messages = [
            'test',
            \json_encode(['data' => 'this is a signed message', 'expires' => $year . '-01-01T00:00:00'])
        ];

        foreach ($messages as $message) {
            $auth = Version1::auth($message, $key);
            $this->assertTrue(\is_string($auth));
            $this->assertSame('v1.auth.', Binary::safeSubstr($auth, 0, 8));

            $decode = Version1::authVerify($auth, $key);
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);

            // Now with a footer
            $auth = Version1::auth($message, $key, 'footer');
            $this->assertTrue(\is_string($auth));
            $this->assertSame('v1.auth.', Binary::safeSubstr($auth, 0, 8));
            try {
                Version1::authVerify($auth, $key);
                $this->fail('Missing footer');
            } catch (\Exception $ex) {
            }
            $decode = Version1::authVerify($auth, $key, 'footer');
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);
        }
    }

    /**
     * @covers Version1::decrypt()
     * @covers Version1::encrypt()
     */
    public function testEncrypt()
    {
        $key = new SymmetricEncryptionKey(random_bytes(32));
        $year = (int) (\date('Y')) + 1;
        $messages = [
            'test',
            \json_encode(['data' => 'this is a signed message', 'expires' => $year . '-01-01T00:00:00'])
        ];

        foreach ($messages as $message) {
            $encrypted = Version1::encrypt($message, $key);
            $this->assertTrue(\is_string($encrypted));
            $this->assertSame('v1.enc.', Binary::safeSubstr($encrypted, 0, 7));

            $decode = Version1::decrypt($encrypted, $key);
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);

            // Now with a footer
            try {
                Version1::decrypt($message, $key);
                $this->fail('Missing footer');
            } catch (\Exception $ex) {
            }
            $encrypted = Version1::encrypt($message, $key, 'footer');
            $this->assertTrue(\is_string($encrypted));
            $this->assertSame('v1.enc.', Binary::safeSubstr($encrypted, 0, 7));

            $decode = Version1::decrypt($encrypted, $key, 'footer');
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);
        }
    }

    /**
     * @covers Version1::seal()
     * @covers Version1::unseal()
     */
    public function testSeal()
    {
        $rsa = Version1::getRsa(false);
        $keypair = $rsa->createKey(2048);
        $privateKey = new AsymmetricSecretKey($keypair['privatekey']);
        $publicKey = new AsymmetricPublicKey($keypair['publickey']);

        $year = (int) (\date('Y')) + 1;
        $messages = [
            'test',
            \json_encode(['data' => 'this is a signed message', 'expires' => $year . '-01-01T00:00:00'])
        ];

        foreach ($messages as $message) {
            $sealed = Version1::seal($message, $publicKey);
            $this->assertTrue(\is_string($sealed));
            $this->assertSame('v1.seal.', Binary::safeSubstr($sealed, 0, 8));

            $decode = Version1::unseal($sealed, $privateKey);
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);

            // Now with a footer
            $sealed = Version1::seal($message, $publicKey, 'footer');
            $this->assertTrue(\is_string($sealed));
            $this->assertSame('v1.seal.', Binary::safeSubstr($sealed, 0, 8));

            try {
                Version1::unseal($sealed, $privateKey);
                $this->fail('Missing footer');
            } catch (\Exception $ex) {
            }
            $decode = Version1::unseal($sealed, $privateKey, 'footer');
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);
        }
    }

    /**
     * @covers Version1::sign()
     * @covers Version1::signVerify()
     */
    public function testSign()
    {
        $rsa = Version1::getRsa(false);
        $keypair = $rsa->createKey(2048);
        $privateKey = new AsymmetricSecretKey($keypair['privatekey']);
        $publicKey = new AsymmetricPublicKey($keypair['publickey']);

        $year = (int) (\date('Y')) + 1;
        $messages = [
            'test',
            \json_encode(['data' => 'this is a signed message', 'expires' => $year . '-01-01T00:00:00'])
        ];

        foreach ($messages as $message) {
            $signed = Version1::sign($message, $privateKey);
            $this->assertTrue(\is_string($signed));
            $this->assertSame('v1.sign.', Binary::safeSubstr($signed, 0, 8));

            $decode = Version1::signVerify($signed, $publicKey);
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);

            // Now with a footer
            $signed = Version1::sign($message, $privateKey, 'footer');
            $this->assertTrue(\is_string($signed));
            $this->assertSame('v1.sign.', Binary::safeSubstr($signed, 0, 8));
            try {
                Version1::signVerify($signed, $publicKey);
                $this->fail('Missing footer');
            } catch (\Exception $ex) {
            }
            $decode = Version1::signVerify($signed, $publicKey, 'footer');
            $this->assertTrue(\is_string($decode));
            $this->assertSame($message, $decode);
        }
    }
}
