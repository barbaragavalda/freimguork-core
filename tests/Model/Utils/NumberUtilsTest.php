<?php

namespace Core\Tests\Model\Utils;

use Core\Model\Utils\NumberUtils;
use PHPUnit\Framework\TestCase;

class NumberUtilsTest extends TestCase
{

    public function testDistanceComputesKilometersBetweenTwoCoordinates(): void
    {
        // Barcelona to Madrid, real-world distance is ~505km great-circle
        $distance = NumberUtils::distance(41.3851, 2.1734, 40.4168, -3.7038);

        $this->assertEqualsWithDelta(505.0, $distance, 5.0);
    }

    public function testDistanceReturnsNullWhenAnyCoordinateIsZero(): void
    {
        $this->assertNull(NumberUtils::distance(0, 2.1734, 40.4168, -3.7038));
    }

    public function testFormatBytesConvertsToTheLargestSensibleUnit(): void
    {
        $this->assertSame(1.0, NumberUtils::formatBytes(1024));
    }

    public function testFormatBytesCanBeForcedToASpecificUnit(): void
    {
        $this->assertSame(1024.0, NumberUtils::formatBytes(1024 * 1024, 2, 'KB'));
    }

    public function testFormatMBytesConvertsBytesToMegabytes(): void
    {
        $this->assertSame(2.0, NumberUtils::formatMBytes(2 * 1024 * 1024));
    }

    public function testFormatBytesNeverGoesNegative(): void
    {
        $this->assertSame(0.0, NumberUtils::formatBytes(-100));
    }

}
