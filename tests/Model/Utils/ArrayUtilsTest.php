<?php

namespace Core\Tests\Model\Utils;

use Core\Model\Utils\ArrayUtils;
use PHPUnit\Framework\TestCase;

class ArrayUtilsTest extends TestCase
{

    public function testReplaceKeysRenamesIdAndNameFields(): void
    {
        $items = array(
            array('product_id' => 1, 'label' => 'Apple'),
            array('product_id' => 2, 'label' => 'Pear'),
        );

        $result = ArrayUtils::replaceKeys($items, 'product_id', 'label');

        $this->assertSame(
            array(
                array('id' => 1, 'name' => 'Apple'),
                array('id' => 2, 'name' => 'Pear'),
            ),
            $result
        );
    }

    public function testReplaceKeysCombinesMultipleNameFields(): void
    {
        $items = array(
            array('id_x' => 1, 'first' => 'Barbara', 'last' => 'Gavalda'),
        );

        $result = ArrayUtils::replaceKeys($items, 'id_x', array('first', 'last'));

        $this->assertSame(1, $result[0]['id']);
        // note the double space before the parenthesis - $value already has a
        // leading space from the "(...)" wrapping, and the loop adds its own
        // ' ' separator on top of it; asserting the actual behavior as-is
        $this->assertSame(' Barbara  (Gavalda)', $result[0]['name']);
        $this->assertArrayNotHasKey('first', $result[0]);
        $this->assertArrayNotHasKey('last', $result[0]);
    }

    public function testSumAddsPositionalValues(): void
    {
        $this->assertSame(array(3, 5), ArrayUtils::sum(array(1, 1), array(2, 4)));
    }

    public function testSumAssocAddsMatchingKeys(): void
    {
        $this->assertSame(
            array('a' => 3, 'b' => 5),
            ArrayUtils::sumAssoc(array('a' => 1, 'b' => 1), array('a' => 2, 'b' => 4))
        );
    }

    public function testAllKeysExistIsTrueWhenEveryKeyPresent(): void
    {
        $this->assertTrue(ArrayUtils::allKeysExist(array('a' => 1, 'b' => 2, 'c' => 3), array('a', 'b')));
    }

    public function testAllKeysExistIsFalseWhenAKeyIsMissing(): void
    {
        $this->assertFalse(ArrayUtils::allKeysExist(array('a' => 1), array('a', 'b')));
    }

    public function testArraySpliceAssocInsertsAtOffsetPreservingKeys(): void
    {
        $result = ArrayUtils::arraySpliceAssoc(
            array('a' => 1, 'b' => 2, 'c' => 3),
            1,
            1,
            array('x' => 9)
        );

        $this->assertSame(array('a' => 1, 'x' => 9, 'c' => 3), $result);
    }

    public function testMoveRelocatesAnElementToANewIndex(): void
    {
        $array = array('a', 'b', 'c', 'd');
        ArrayUtils::move($array, 0, 2);

        $this->assertSame(array('b', 'c', 'a', 'd'), $array);
    }

    public function testMoveClampsFinalIndexToArrayBounds(): void
    {
        $array = array('a', 'b', 'c');
        ArrayUtils::move($array, 0, 99);

        $this->assertSame(array('b', 'c', 'a'), $array);
    }

    public function testMergeConcatenatesWhenBothArraysHaveItems(): void
    {
        $this->assertSame(array(1, 2, 3, 4), ArrayUtils::merge(array(1, 2), array(3, 4)));
    }

    public function testMergeReturnsWhicheverArrayIsNonEmpty(): void
    {
        $this->assertSame(array(1, 2), ArrayUtils::merge(array(1, 2), array()));
        $this->assertSame(array(3, 4), ArrayUtils::merge(array(), array(3, 4)));
    }

}
