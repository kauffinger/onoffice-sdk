<?php

namespace Tests\onOffice\SDK;

use onOffice\SDK\internal\ApiAction;

class ApiActionTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultCreationOfActionParameters()
    {
        $parameters = [
            'param1' => 'value1',
            [
                'param2' => 'value2',
                'param3' => 'value3',
            ],
        ];

        $apiAction = new ApiAction(
            'someId',
            'someResource',
            $parameters
        );

        $result = $apiAction->getActionParameters();

        $expectation = [
            'actionid' => 'someId',
            'identifier' => '',
            'parameters' => [
                'param1' => 'value1',
                [
                    'param2' => 'value2',
                    'param3' => 'value3',
                ],
            ],
            'resourceid' => '',
            'resourcetype' => 'someResource',
            'timestamp' => null,
        ];

        $this->assertEquals($expectation, $result);
    }

    public function testDefaultIdentifier()
    {
        $parameters = [
            'param1' => 'value1',
            [
                'param2' => 'value2',
                'param3' => 'value3',
            ],
        ];

        $apiAction = new ApiAction(
            'someId',
            'someResource',
            $parameters
        );

        $result = $apiAction->getIdentifier();

        ksort($parameters);
        $expectedArray = [
            'actionid' => 'someId',
            'identifier' => '',
            'parameters' => $parameters,
            'resourceid' => '',
            'resourcetype' => 'someResource',
            'timestamp' => null,
        ];

        $this->assertEquals(
            $expectedArray,
            $apiAction->getActionParameters()
        );

        $expectedHash = md5(serialize($expectedArray));
        $this->assertEquals($expectedHash, $result);
    }

    public function testCustomCreationOfActionParameters()
    {
        $parameters = [
            'param1' => 'value1',
            [
                'param2' => 'value2',
                'param3' => 'value3',
            ],
        ];

        $apiAction = new ApiAction(
            'someId',
            'someResource',
            $parameters,
            'someResourceId',
            'someIdentifier'
        );

        $result = $apiAction->getActionParameters();

        $expectation = [
            'actionid' => 'someId',
            'identifier' => 'someIdentifier',
            'parameters' => [
                'param1' => 'value1',
                [
                    'param2' => 'value2',
                    'param3' => 'value3',
                ],
            ],
            'resourceid' => 'someResourceId',
            'resourcetype' => 'someResource',
            'timestamp' => null,
        ];

        $this->assertEquals($expectation, $result);
    }

    public function testCustomIdentifier()
    {
        $parameters = [
            'param1' => 'value1',
            [
                'param2' => 'value2',
                'param3' => 'value3',
            ],
        ];

        $apiAction = new ApiAction(
            'someId',
            'someResource',
            $parameters,
            'someResourceId',
            'someIdentifier',
            123
        );

        $result = $apiAction->getIdentifier();

        ksort($parameters);
        $expectedArray = [
            'actionid' => 'someId',
            'identifier' => 'someIdentifier',
            'parameters' => $parameters,
            'resourceid' => 'someResourceId',
            'resourcetype' => 'someResource',
            'timestamp' => 123,
        ];

        $this->assertEquals(
            $expectedArray,
            $apiAction->getActionParameters()
        );

        $expectedHash = md5(serialize($expectedArray));
        $this->assertEquals($expectedHash, $result);
    }
}
