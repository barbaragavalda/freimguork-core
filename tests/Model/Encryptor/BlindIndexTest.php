<?php

namespace Core\Tests\Model\Encryptor;

use Core\Model\Encryptor\BlindIndex;
use Core\Model\Encryptor\Secret;
use PHPUnit\Framework\TestCase;

class BlindIndexTest extends TestCase
{

    protected function setUp(): void
    {
        Secret::setForTesting(bin2hex(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        Secret::setForTesting(null);
    }

    public function testSameValueProducesTheSameIndexRegardlessOfCaseOrWhitespace(): void
    {
        $this->assertSame(
            BlindIndex::compute('Hello@Example.com', 'email'),
            BlindIndex::compute(' hello@example.com ', 'email')
        );
    }

    public function testIsDeterministicAcrossCalls(): void
    {
        // unlike TwoWay::encrypt(), this must be repeatable - it's what
        // makes `WHERE email_bidx = :bidx` work
        $this->assertSame(
            BlindIndex::compute('hello@example.com', 'email'),
            BlindIndex::compute('hello@example.com', 'email')
        );
    }

    public function testDifferentFieldNamesProduceDifferentIndexesForTheSameValue(): void
    {
        $this->assertNotSame(
            BlindIndex::compute('hello@example.com', 'email'),
            BlindIndex::compute('hello@example.com', 'other_field')
        );
    }

    public function testDifferentValuesProduceDifferentIndexes(): void
    {
        $this->assertNotSame(
            BlindIndex::compute('hello@example.com', 'email'),
            BlindIndex::compute('goodbye@example.com', 'email')
        );
    }

}
