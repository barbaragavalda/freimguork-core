<?php

namespace Core\Tests\Utils;

use Core\Utils\Language;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

}
