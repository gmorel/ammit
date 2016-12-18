<?php
declare(strict_types=1);

namespace Tests\Units\Imedia\Ammit\UI\Resolver;

use Imedia\Ammit\UI\Resolver\Asserter\RequestAttributeValueAsserter;
use Imedia\Ammit\UI\Resolver\Asserter\RequestQueryValueAsserter;
use Imedia\Ammit\UI\Resolver\Exception\CommandMappingException;
use mageekguy\atoum;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Units\Imedia\Ammit\Stub\Application\Command\RegisterUserCommand;
use Tests\Units\Imedia\Ammit\Stub\UI\CommandResolver\RegisterUserCommandResolver as SUT;

/**
 * @author Guillaume MOREL <g.morel@imediafrance.fr>
 */
class AbstractCommandResolver extends atoum
{
    public function test_it_can_be_constructed_without_injection()
    {
        // Given
        $sut = new SUT();
        $requestMock = $this->mockServerRequest(
            [
                'firstName' => 'Stephen',
                'lastName' => 'Hawking',
                'email' => 'stephen.hawking.me',
            ]
        );

        // When
        $actual = $sut->resolve($requestMock);

        // Then
        $this->object($actual)
            ->isEqualTo(new RegisterUserCommand('Stephen', 'Hawking', 'stephen.hawking.me'))
        ;
    }

    public function test_it_can_be_constructed_with_ui_validation_engine()
    {
        // Given
        $validationEngineMock = $this->mockUIValidationEngine();
        $sut = new SUT(
            $validationEngineMock
        );
        $requestMock = $this->mockServerRequest(
            [
                'firstName' => 'Stephen',
                'lastName' => 'Hawking',
                'email' => 'stephen.hawking.me',
            ]
        );

        // When
        $actual = $sut->resolve($requestMock);

        // Then
        $this
            ->object($actual)
                ->isEqualTo(new RegisterUserCommand('Stephen', 'Hawking', 'stephen.hawking.me'))
            ->mock($validationEngineMock)
                    ->call('validateFieldValue')->thrice()
                    ->call('guardAgainstAnyUIValidationException')->once()
        ;
    }

    public function test_it_can_be_constructed_with_request_attribute_value_asserter()
    {
        // Given
        $requestAttributeValueAsserteMock = $this->mockRequestAttributeValueAsserter();
        $sut = new SUT(
            null,
            $requestAttributeValueAsserteMock
        );
        $requestMock = $this->mockServerRequest(
            [
                'firstName' => 'Stephen',
                'lastName' => 'Hawking',
                'email' => 'stephen.hawking.me',
            ]
        );

        // When
        $actual = $sut->resolve($requestMock);

        // Then
        $this
            ->object($actual)
                ->isEqualTo(new RegisterUserCommand('azerty', 'azerty', 'azerty'))
            ->mock($requestAttributeValueAsserteMock)
                    ->call('attributeMustBeString')->thrice()
        ;
    }

    public function test_it_can_be_constructed_with_request_query_value_asserter()
    {
        // Given
        $requestQueryValueAsserterMock = $this->mockRequestQueryValueAsserter();
        $sut = new SUT(
            null,
            null,
            $requestQueryValueAsserterMock
        );
        $requestMock = $this->mockServerRequest(
            [
                'firstName' => 'Stephen',
                'lastName' => 'Hawking',
                'email' => 'stephen.hawking.me',
            ]
        );

        // When
        $actual = $sut->resolve($requestMock);

        // Then
        $this
            ->object($actual)
                ->isEqualTo(new RegisterUserCommand('Stephen', 'Hawking', 'stephen.hawking.me'))
            ->mock($requestQueryValueAsserterMock)
                    ->call('valueMustBeString')->thrice()
        ;
    }

    public function test_it_can_resolve_a_request()
    {
        // Given
        $sut = new SUT();
        $requestMock = $this->mockServerRequest(
            [
                'firstName' => 'Stephen',
                'lastName' => 'Hawking',
                'email' => 'stephen.hawking.me',
            ]
        );

        // When
        $actual = $sut->resolve($requestMock);

        // Then
        $this->object($actual)
            ->isEqualTo(new RegisterUserCommand('Stephen', 'Hawking', 'stephen.hawking.me'))
        ;
    }

    public function test_it_can_intercept_a_command_mapping_exception()
    {
        // Given
        $expected = new CommandMappingException('Array does not contain an element with key "firstName"', 'root');
        $expected = $expected->normalize();

        $sut = new SUT();
        $requestMock = $this->mockServerRequest(
            [
                'firstName2' => 'Stephen',
                'lastName' => 'Hawking',
                'email' => 'stephen.hawking.me',
            ]
        );

        // When
        try {
            $sut->resolve($requestMock);
        } catch (CommandMappingException $e) {
            $actual = $e->normalize();

            // Then
            $this
                ->array($actual)
                    ->isEqualTo($expected)
                ->mock($requestMock)
                    ->call('getParsedBody')->once()
            ;

            return;
        }

        $this->throwException();
    }

    private function mockServerRequest(array $requestAttributes): ServerRequestInterface
    {
        $mock = new \mock\Psr\Http\Message\ServerRequestInterface();
        $this->calling($mock)->getParsedBody = $requestAttributes;

        return $mock;
    }

    private function mockUIValidationEngine(): \Imedia\Ammit\UI\Resolver\UIValidationEngine
    {
        $mock = new \mock\Imedia\Ammit\UI\Resolver\UIValidationEngine();
        $this->calling($mock)->validateFieldValue = null;
        $this->calling($mock)->guardAgainstAnyUIValidationException = null;

        return $mock;
    }

    private function mockRequestAttributeValueAsserter(): RequestAttributeValueAsserter
    {
        $this->mockGenerator->orphanize('__construct');
        $mock = new \mock\Imedia\Ammit\UI\Resolver\Asserter\RequestAttributeValueAsserter();
        $this->calling($mock)->attributeMustBeString = 'azerty';

        return $mock;
    }

    private function mockRequestQueryValueAsserter(): RequestQueryValueAsserter
    {
        $this->mockGenerator->orphanize('__construct');
        $mock = new \mock\Imedia\Ammit\UI\Resolver\Asserter\RequestQueryValueAsserter();
        $this->calling($mock)->valueMustBeString = function ($value) { return $value; };

        return $mock;
    }

    private function throwException()
    {
        throw new \mageekguy\atoum\asserter\exception($this->variable(), 'CommandMappingException not thrown.');
    }
}
