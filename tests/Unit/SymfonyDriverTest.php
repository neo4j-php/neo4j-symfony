<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit;

use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Databags\ServerInfo;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Neo4j\Neo4jBundle\Decorators\SymfonyDriver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class SymfonyDriverTest extends TestCase
{
    private ReflectionClass $symfonyDriverReflection;
    private ReflectionClass $driverInterfaceReflection;

    #[\Override]
    protected function setUp(): void
    {
        $this->symfonyDriverReflection = new ReflectionClass(SymfonyDriver::class);
        $this->driverInterfaceReflection = new ReflectionClass(DriverInterface::class);
    }

    public function testImplementsDriverInterface(): void
    {
        $this->assertTrue(
            $this->symfonyDriverReflection->implementsInterface(DriverInterface::class),
            'SymfonyDriver must implement DriverInterface'
        );
    }

    public function testHasGetServerInfoMethod(): void
    {
        $this->assertTrue(
            $this->symfonyDriverReflection->hasMethod('getServerInfo'),
            'SymfonyDriver must have getServerInfo method'
        );
    }

    public function testGetServerInfoMethodSignature(): void
    {
        $method = $this->symfonyDriverReflection->getMethod('getServerInfo');

        $this->assertMethodSignatureMatchesInterface($method, 'getServerInfo');
    }

    public function testVerifyConnectivityMethodSignature(): void
    {
        $method = $this->symfonyDriverReflection->getMethod('verifyConnectivity');

        $this->assertMethodSignatureMatchesInterface($method, 'verifyConnectivity');
    }

    public function testCloseConnectionsMethodSignature(): void
    {
        $method = $this->symfonyDriverReflection->getMethod('closeConnections');

        $this->assertMethodSignatureMatchesInterface($method, 'closeConnections');
    }

    public function testCreateSessionMethodSignature(): void
    {
        $method = $this->symfonyDriverReflection->getMethod('createSession');

        $interfaceMethod = $this->driverInterfaceReflection->getMethod('createSession');

        $this->assertSame(
            $interfaceMethod->getNumberOfParameters(),
            $method->getNumberOfParameters(),
            'createSession parameter count must match interface'
        );
    }

    public function testGetServerInfoReturnType(): void
    {
        $method = $this->symfonyDriverReflection->getMethod('getServerInfo');
        $returnType = $method->getReturnType();

        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame(ServerInfo::class, $returnType->getName());
    }

    public function testGetServerInfoParameterType(): void
    {
        $method = $this->symfonyDriverReflection->getMethod('getServerInfo');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('config', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());

        $paramType = $parameters[0]->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $paramType);
        $this->assertSame(SessionConfiguration::class, $paramType->getName());
    }

    public function testAllInterfaceMethodsAreImplemented(): void
    {
        $interfaceMethods = $this->driverInterfaceReflection->getMethods();

        foreach ($interfaceMethods as $interfaceMethod) {
            $methodName = $interfaceMethod->getName();

            $this->assertTrue(
                $this->symfonyDriverReflection->hasMethod($methodName),
                "SymfonyDriver must implement method: {$methodName}"
            );

            $implementedMethod = $this->symfonyDriverReflection->getMethod($methodName);

            $this->assertFalse(
                $implementedMethod->isAbstract(),
                "Method {$methodName} must not be abstract"
            );
        }
    }

    private function assertMethodSignatureMatchesInterface(ReflectionMethod $method, string $methodName): void
    {
        $interfaceMethod = $this->driverInterfaceReflection->getMethod($methodName);

        $this->assertSame(
            $interfaceMethod->getNumberOfParameters(),
            $method->getNumberOfParameters(),
            "{$methodName} parameter count must match interface"
        );

        $interfaceParams = $interfaceMethod->getParameters();
        $implParams = $method->getParameters();

        foreach ($interfaceParams as $i => $interfaceParam) {
            $implParam = $implParams[$i];

            $this->assertSame(
                $interfaceParam->getName(),
                $implParam->getName(),
                "{$methodName} parameter {$i} name must match interface"
            );

            $this->assertSame(
                $interfaceParam->allowsNull(),
                $implParam->allowsNull(),
                "{$methodName} parameter {$i} nullability must match interface"
            );
        }
    }
}
