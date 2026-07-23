<?php

namespace Core\Tests\Utils;

use Core\Model\MySQL\PDO;
use Core\Utils\Language;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class LanguageTest extends TestCase
{

    /**
     * private, and a pure function of its two arguments - reflection is
     * simpler here than exercising it through the full Accept-Language ->
     * initLanguage() -> constructor path, which needs a real Project/Session
     */
    private function equivalents(string $language, array $projectLanguages): string
    {
        $method = new ReflectionMethod(Language::class, 'equivalents');
        return $method->invoke(new Language(), $language, $projectLanguages);
    }

    public function testReturnsTheLanguageAsIsWhenTheProjectSupportsItDirectly(): void
    {
        // regression: previously only checked against the project's single
        // first/default language, so a 3rd+ supported language (here 'en',
        // on a project supporting ['ca', 'es', 'en']) always fell through
        // to '' instead of being recognized - confirmed empirically against
        // tv-tracker-local's api sub-project before this fix
        $this->assertSame('en', $this->equivalents('en', array('ca', 'es', 'en')));
        $this->assertSame('es', $this->equivalents('es', array('ca', 'es', 'en')));
        $this->assertSame('ca', $this->equivalents('ca', array('ca', 'es', 'en')));
    }

    public function testFallsBackToSpanishForRelatedMinorityLanguagesTheProjectDoesNotSupport(): void
    {
        $this->assertSame('es', $this->equivalents('ca', array('es')));
        $this->assertSame('es', $this->equivalents('eu', array('es')));
        $this->assertSame('es', $this->equivalents('gl', array('es')));
    }

    public function testReturnsEmptyStringForAnUnsupportedUnrelatedLanguage(): void
    {
        $this->assertSame('', $this->equivalents('fr', array('ca', 'es')));
    }

    public function testGetLanguageIDReturnsNullWhenNotFound(): void
    {
        $language        = new Language();
        // public property, injected directly (bypassing ensureConnected()'s
        // real Manager::getInstance() connection) - an empty $dbConfig +
        // $throwError = false leaves the internal \PDO null, so query() just
        // returns [], same convention as Core\Tests\Model\ModelTest
        $language->mysql = new PDO(array(), false);

        $this->assertNull($language->getLanguageID('xx'));
    }

    public function testWithCultureReturnsTheCallbacksReturnValue(): void
    {
        $language = new Language();

        $result = $language->withCulture('en', fn() => 'hello');

        $this->assertSame('hello', $result);
    }

    public function testWithCultureCallsTheCallbackDirectlyForAnUnconfiguredCulture(): void
    {
        $language = new Language();

        $result = $language->withCulture('xx', fn() => 'unchanged');

        $this->assertSame('unchanged', $result);
    }

    public function testWithCultureRestoresThePreviousLocaleEvenIfTheCallbackThrows(): void
    {
        $language = new Language();
        $before   = getenv('LC_ALL');

        try {
            $language->withCulture('es', function () {
                throw new RuntimeException('boom');
            });
            $this->fail('expected exception to propagate');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame($before, getenv('LC_ALL'));
    }

}
