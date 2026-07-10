<?php

namespace Core\Tests\Utils;

use Core\Utils\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{

    private array $originalCookie;

    protected function setUp(): void
    {
        $this->originalCookie = $_COOKIE;
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->originalCookie;
    }

    public function testGetReturnsNullWhenCookieIsNotSet(): void
    {
        unset($_COOKIE['missing']);

        $this->assertNull((new Session())->get('missing'));
    }

    public function testSetThenGetRoundTripsAStringValue(): void
    {
        $session = new Session();
        $session->set('lang_culture', 'ca');

        $this->assertSame('ca', $session->get('lang_culture'));
    }

    public function testSetThenGetRoundTripsAnArrayValue(): void
    {
        $session = new Session();
        $session->set('cart', array('id' => 3, 'qty' => 2));

        $this->assertSame(array('id' => 3, 'qty' => 2), $session->get('cart'));
    }

    public function testGetDecodesRawCookiesSetOutsideOfSession(): void
    {
        $_COOKIE['lang_id'] = json_encode(42);

        $this->assertSame(42, (new Session())->get('lang_id'));
    }

    public function testDeleteRemovesTheCookie(): void
    {
        $session = new Session();
        $session->set('to_delete', 'value');

        $session->delete('to_delete');

        $this->assertNull($session->get('to_delete'));
        $this->assertArrayNotHasKey('to_delete', $_COOKIE);
    }

    public function testDeleteOnAMissingCookieIsANoOp(): void
    {
        unset($_COOKIE['never_set']);

        (new Session())->delete('never_set');

        $this->assertArrayNotHasKey('never_set', $_COOKIE);
    }

    public function testClearRemovesEveryCookie(): void
    {
        $session = new Session();
        $session->set('a', 1);
        $session->set('b', 2);

        $session->clear();

        $this->assertSame(array(), $_COOKIE);
    }

}
