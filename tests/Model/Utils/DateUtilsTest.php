<?php

namespace Core\Tests\Model\Utils;

use Core\Model\Utils\DateUtils;
use DateTime;
use PHPUnit\Framework\TestCase;

class DateUtilsTest extends TestCase
{

    public function testUserDateConvertsFromDatabaseFormat(): void
    {
        $this->assertSame('01/06/2024', DateUtils::userDate('2024-06-01'));
    }

    public function testDatabaseDateConvertsFromUserFormat(): void
    {
        $this->assertSame('2024-06-01', DateUtils::databaseDate('01/06/2024'));
    }

    public function testUserDateReturnsNullForAnUnparseableString(): void
    {
        $this->assertNull(DateUtils::userDate('not-a-date'));
    }

    public function testFormatReturnsFalseForAMalformedDate(): void
    {
        // regression: DateTime::createFromFormat() returns false rather than
        // throwing, and ->format() on false is a PHP Error (not an
        // Exception) - format() must check for that itself
        $this->assertFalse(DateUtils::format('Y-m-d', 'd/m/Y', 'not-a-date'));
    }

    public function testFormatReturnsFalseForAnEmptyDate(): void
    {
        $this->assertFalse(DateUtils::format('Y-m-d', 'd/m/Y', ''));
    }

    public function testFormatSecondsPadsMinutesAndSeconds(): void
    {
        $this->assertSame('02:05', DateUtils::formatSeconds(125));
    }

    public function testGetTimeIntervalComputesDaysHoursAndMinutes(): void
    {
        $interval = DateUtils::getTimeInterval($this->start(), $this->end());

        $this->assertSame(2, $interval->d);
        $this->assertSame(2, $interval->h);
        $this->assertSame(30, $interval->i);
    }

    public function testGetDbIntervalListsEachNonZeroUnit(): void
    {
        $this->assertSame(
            array('INTERVAL 2 DAY', 'INTERVAL 2 HOUR', 'INTERVAL 30 MINUTE'),
            DateUtils::getDbInterval($this->start(), $this->end())
        );
    }

    public function testIntervalToSecondsConvertsAllUnits(): void
    {
        $interval = DateUtils::getTimeInterval($this->start(), $this->end());

        // 2 days + 2 hours + 30 minutes
        $this->assertSame((2 * 86400) + (2 * 3600) + (30 * 60), DateUtils::intervalToSeconds($interval));
    }

    public function testSecondsBetweenMatchesTheRawDifference(): void
    {
        $this->assertSame(181800, DateUtils::secondsBetween($this->start(), $this->end()));
    }

    public function testMinutesBetweenMatchesTheRawDifference(): void
    {
        $this->assertSame(3030, DateUtils::minutesBetween($this->start(), $this->end()));
    }

    public function testTimeBetweenFormatsAsHoursColonMinutes(): void
    {
        $this->assertSame('50:30', DateUtils::timeBetween($this->start(), $this->end()));
    }

    public function testTimeBetweenReturnsZeroWhenStartIsNotBeforeEnd(): void
    {
        $this->assertSame('00:00', DateUtils::timeBetween($this->end(), $this->start()));
    }

    public function testTimeDifferenceReturnsTheRequestedIntervalUnit(): void
    {
        $this->assertSame(2, DateUtils::timeDifference($this->start(), $this->end(), 'h'));
        $this->assertSame(2, DateUtils::timeDifference($this->start(), $this->end(), 'd'));
    }

    public function testAgeComputesFullYearsFromAUserFormattedDate(): void
    {
        $twentyFifthBirthday = (new DateTime())->modify('-25 years')->format(DateUtils::FORMAT_DATE_USER);

        $this->assertSame(25, DateUtils::age($twentyFifthBirthday));
    }

    private function start(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', '2024-06-01 10:00:00');
    }

    private function end(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', '2024-06-03 12:30:00');
    }

}
