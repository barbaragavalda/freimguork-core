<?php

namespace Core\Tests\Model\Utils;

use Core\Model\Utils\StringUtils;
use PHPUnit\Framework\TestCase;

class StringUtilsTest extends TestCase
{

    public function testNormalizeLowercasesTrimsAndStripsAccents(): void
    {
        $this->assertSame('barbara gavalda', StringUtils::normalize('  Bàrbara Gavaldà  '));
    }

    public function testRemoveSpecialCharactersProducesASlug(): void
    {
        $this->assertSame('barbara-gavalda', StringUtils::removeSpecialCharacters('Bàrbara Gavaldà'));
    }

    public function testRemoveSpecialCharactersCanKeepCaseAndDropPoints(): void
    {
        $this->assertSame('Barbara', StringUtils::removeSpecialCharacters('Barbara.', false, false));
    }

    public function testRemoveAccentsTransliteratesToAscii(): void
    {
        $this->assertSame('cafe', StringUtils::removeAccents('café'));
    }

    public function testReplaceAccentsEscapesHtmlEntities(): void
    {
        $this->assertSame('&lt;b&gt;&amp;&lt;/b&gt;', StringUtils::replaceAccents('<b>&</b>'));
    }

    public function testCheckEmbedYoutubeRewritesShortLinks(): void
    {
        $this->assertSame(
            'https://youtube.com/embed/abc123',
            StringUtils::checkEmbedYoutube('https://youtu.be/abc123')
        );
    }

    public function testCheckEmbedYoutubeLeavesAlreadyEmbeddedLinksUnchanged(): void
    {
        $url = 'https://www.youtube.com/embed/abc123';
        $this->assertSame($url, StringUtils::checkEmbedYoutube($url));
    }

    public function testFormatPriceFormatsANumericValue(): void
    {
        $this->assertSame('1.234,50&euro;', StringUtils::formatPrice(1234.5));
    }

    public function testFormatPriceReturnsEmptyStringForFalsyValue(): void
    {
        $this->assertSame('', StringUtils::formatPrice(0));
    }

    public function testFormatPriceReturnsNonNumericValueUnchanged(): void
    {
        $this->assertSame('n/a', StringUtils::formatPrice('n/a'));
    }

    /**
     * ground truth for these checksums was verified by running validateNif()/
     * validateCif() directly (see PR description) rather than hand-computed,
     * since the check-digit algorithms aren't easy to verify by inspection
     */
    public function testValidateNifAcceptsAValidDni(): void
    {
        $this->assertTrue(StringUtils::validateNif('12345678Z'));
    }

    public function testValidateNifRejectsAWrongCheckLetter(): void
    {
        $this->assertFalse(StringUtils::validateNif('12345678A'));
    }

    public function testValidateCifAcceptsAValidCif(): void
    {
        $this->assertTrue(StringUtils::validateCif('A58818501'));
    }

    public function testValidateCifRejectsAWrongCheckDigit(): void
    {
        $this->assertFalse(StringUtils::validateCif('A12345678'));
    }

    /**
     * regression: getCifSum() does arithmetic directly on $cif[1..7],
     * assuming they're digits - a 9+-character string that isn't shaped like
     * a real CIF used to throw a TypeError instead of returning false
     */
    public function testValidateCifRejectsNonNumericBodyInsteadOfCrashing(): void
    {
        $this->assertFalse(StringUtils::validateCif('not-valid'));
        $this->assertFalse(StringUtils::validateCif('Kxxxxxxxx'));
    }

    /**
     * regression: validateNif() used to call getCifSum() unconditionally for
     * every format, even "NIE extrano" (T-prefixed), whose format
     * legitimately allows non-digit characters that getCifSum() can't handle
     */
    public function testValidateNifRejectsNonNumericBodyInsteadOfCrashing(): void
    {
        $this->assertFalse(StringUtils::validateNif('not-valid'));
        $this->assertFalse(StringUtils::validateNif('Kxxxxxxxx'));
    }

    public function testValidateNifAcceptsTheAlphanumericNieExtranoFormat(): void
    {
        $this->assertTrue(StringUtils::validateNif('T12345678'));
    }

    public function testValidateNifCifAcceptsEitherFormat(): void
    {
        $this->assertTrue(StringUtils::validateNifCif('12345678Z'));
        $this->assertTrue(StringUtils::validateNifCif('A58818501'));
        $this->assertFalse(StringUtils::validateNifCif('short'));
    }

    public function testValidateMACAcceptsColonSeparatedAddress(): void
    {
        $this->assertTrue(StringUtils::validateMAC('00:1A:2B:3C:4D:5E'));
    }

    public function testValidateMACAcceptsBareHexAddress(): void
    {
        $this->assertTrue(StringUtils::validateMAC('001A2B3C4D5E'));
    }

    public function testValidateMACRejectsGarbage(): void
    {
        $this->assertFalse(StringUtils::validateMAC('not-a-mac'));
    }

    public function testGenerateTokenProducesRequestedLengthFromExpectedAlphabet(): void
    {
        $token = StringUtils::generateToken(16);

        $this->assertSame(16, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{16}$/', $token);
    }

    public function testGetStringBetweenExtractsSubstring(): void
    {
        $this->assertSame('bar', StringUtils::getStringBetween('foo[bar]baz', '[', ']'));
    }

    public function testGetStringBetweenReturnsEmptyWhenStartNotFound(): void
    {
        $this->assertSame('', StringUtils::getStringBetween('foobarbaz', '[', ']'));
    }

    public function testValidateURLAddsHttpPrefixWhenMissing(): void
    {
        $this->assertSame('http://example.com/path', StringUtils::validateURL('example.com/path'));
    }

    public function testValidateURLReturnsFalseWithoutAHost(): void
    {
        // regression: parse_url() returns false (not an array) for this one,
        // and validateURL() used to assume it always got an array back
        $this->assertFalse(StringUtils::validateURL('://nohost'));
    }

    public function testValidateURLPreservesSchemeAndQuery(): void
    {
        $this->assertSame(
            'https://example.com/path?x=1',
            StringUtils::validateURL('https://example.com/path?x=1')
        );
    }

    public function testAcronymTakesFirstLetterOfEachWord(): void
    {
        $this->assertSame('CDP', StringUtils::acronym('cuina de profit'));
    }

    public function testTruncateHtmlReturnsShortTextUnchanged(): void
    {
        $this->assertSame('short text', StringUtils::truncateHtml('short text', 100));
    }

    public function testTruncateHtmlCutsAtWordBoundaryAndAddsEnding(): void
    {
        $result = StringUtils::truncateHtml('one two three four five', 10, '...', false, false);

        $this->assertStringEndsWith('...', $result);
        $this->assertLessThanOrEqual(13, strlen($result));
    }

}
