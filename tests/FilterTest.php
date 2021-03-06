<?php

use Clue\StreamFilter;

class FilterTest extends PHPUnit_Framework_TestCase
{
    public function testAppendSimpleCallback()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            return strtoupper($chunk);
        });

        fwrite($stream, 'hello');
        fwrite($stream, 'world');
        rewind($stream);

        $this->assertEquals('HELLOWORLD', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendReturningEmptyStringWillNotPassThrough()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            return '';
        });

        fwrite($stream, 'hello');
        fwrite($stream, 'world');
        rewind($stream);

        $this->assertEquals('', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendWriteOnly()
    {
        $stream = $this->createStream();

        $invoked = 0;

        StreamFilter\append($stream, function ($chunk) use (&$invoked) {
            ++$invoked;

            return $chunk;
        }, STREAM_FILTER_WRITE);

        fwrite($stream, 'a');
        fwrite($stream, 'b');
        fwrite($stream, 'c');
        rewind($stream);

        $this->assertEquals(3, $invoked);
        $this->assertEquals('abc', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendReadOnly()
    {
        $stream = $this->createStream();

        $invoked = 0;

        StreamFilter\append($stream, function ($chunk) use (&$invoked) {
            ++$invoked;

            return $chunk;
        }, STREAM_FILTER_READ);

        fwrite($stream, 'a');
        fwrite($stream, 'b');
        fwrite($stream, 'c');
        rewind($stream);

        $this->assertEquals(0, $invoked);
        $this->assertEquals('abc', stream_get_contents($stream));
        $this->assertEquals(1, $invoked);

        fclose($stream);
    }

    public function testOrderCallingAppendAfterPrepend()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            return '[' . $chunk . ']';
        }, STREAM_FILTER_WRITE);

        StreamFilter\prepend($stream, function ($chunk) {
            return '(' . $chunk . ')';
        }, STREAM_FILTER_WRITE);

        fwrite($stream, 'hello');
        rewind($stream);

        $this->assertEquals('[(hello)]', stream_get_contents($stream));

        fclose($stream);
    }

    public function testRemoveFilter()
    {
        $stream = $this->createStream();

        $first = StreamFilter\append($stream, function ($chunk) {
            return $chunk . '?';
        }, STREAM_FILTER_WRITE);

        StreamFilter\append($stream, function ($chunk) {
            return $chunk . '!';
        }, STREAM_FILTER_WRITE);

        StreamFilter\remove($first);

        fwrite($stream, 'hello');
        rewind($stream);

        $this->assertEquals('hello!', stream_get_contents($stream));

        fclose($stream);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAppendInvalidStreamIsRuntimeError()
    {
        StreamFilter\append(false, function () { });
    }

    /**
     * @expectedException RuntimeException
     */
    public function testPrependInvalidStreamIsRuntimeError()
    {
        StreamFilter\prepend(false, function () { });
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRemoveInvalidFilterIsRuntimeError()
    {
        StreamFilter\remove(false);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCallbackIsInvalidArgument()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, 'a-b-c');
    }

    private function createStream()
    {
        return fopen('php://memory', 'r+');
    }
}
