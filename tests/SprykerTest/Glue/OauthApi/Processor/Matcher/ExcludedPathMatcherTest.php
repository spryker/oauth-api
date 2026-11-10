<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\OauthApi\Processor\Matcher;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Glue\OauthApi\OauthApiConfig;
use Spryker\Glue\OauthApi\OauthApiDependencyProvider;
use Spryker\Glue\OauthApi\OauthApiFactory;
use Spryker\Glue\OauthApi\Processor\Matcher\ExcludedPathMatcher;
use Spryker\Glue\OauthExtension\Dependency\Plugin\AccessTokenValidationExcludedPathPatternProviderPluginInterface;
use Spryker\Shared\Log\Config\LoggerConfigInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group OauthApi
 * @group Processor
 * @group Matcher
 * @group ExcludedPathMatcherTest
 * Add your own group annotations below this line
 */
class ExcludedPathMatcherTest extends Unit
{
    protected const string ANCHORED_PATTERN = '^/excluded-resource/';

    protected const string UNANCHORED_PATTERN = '/excluded-resource/';

    protected const string MALFORMED_PATTERN = '^/excluded-resource/(';

    protected const string ALTERNATION_PATTERN = '^/oauth/authorize|/token';

    /**
     * @var \SprykerTest\Glue\OauthApi\OauthApiTester
     */
    protected $tester;

    public function testGivenAnUnanchoredPatternWhenMatchingAPathContainingItThenThePathIsNotExcluded(): void
    {
        // Arrange
        $excludedPathMatcher = $this->createExcludedPathMatcher([static::UNANCHORED_PATTERN], $this->createLoggerMock());

        // Act
        $isExcludedPath = $excludedPathMatcher->isExcludedPath((new GlueRequestTransfer())->setPath('/carts/excluded-resource/item'));

        // Assert
        $this->assertFalse($isExcludedPath);
    }

    public function testGivenAPatternWithAlternationWhenMatchingAPathContainingTheSecondBranchThenThePathIsNotExcluded(): void
    {
        // Arrange
        $excludedPathMatcher = $this->createExcludedPathMatcher([static::ALTERNATION_PATTERN], $this->createLoggerMock());

        // Act
        $isContainingPathExcluded = $excludedPathMatcher->isExcludedPath((new GlueRequestTransfer())->setPath('/carts/token'));
        $isStartingPathExcluded = $excludedPathMatcher->isExcludedPath((new GlueRequestTransfer())->setPath('/token'));

        // Assert
        $this->assertFalse($isContainingPathExcluded);
        $this->assertTrue($isStartingPathExcluded);
    }

    public function testGivenAnUnanchoredPatternWhenMatchingThenOneWarningNamesThePatternEvenForRepeatedMatches(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains(static::UNANCHORED_PATTERN));
        $excludedPathMatcher = $this->createExcludedPathMatcher([static::UNANCHORED_PATTERN], $loggerMock);
        $glueRequestTransfer = (new GlueRequestTransfer())->setPath('/excluded-resource/item');

        // Act
        $excludedPathMatcher->isExcludedPath($glueRequestTransfer);
        $isExcludedPath = $excludedPathMatcher->isExcludedPath($glueRequestTransfer);

        // Assert
        $this->assertFalse($isExcludedPath);
    }

    public function testGivenAMalformedPatternWhenMatchingThenItIsIgnoredAndReportedOnceWithoutAPhpWarning(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains(static::MALFORMED_PATTERN));
        $excludedPathMatcher = $this->createExcludedPathMatcher([static::MALFORMED_PATTERN, static::ANCHORED_PATTERN], $loggerMock);

        // Act
        $isMalformedPatternPathExcluded = $excludedPathMatcher->isExcludedPath((new GlueRequestTransfer())->setPath('/other/item'));
        $isValidPatternPathExcluded = $excludedPathMatcher->isExcludedPath((new GlueRequestTransfer())->setPath('/excluded-resource/item'));

        // Assert
        $this->assertFalse($isMalformedPatternPathExcluded);
        $this->assertTrue($isValidPatternPathExcluded);
    }

    public function testGivenAnAnchoredPatternWhenAPathMatchesThenTheMatchingPatternIsLoggedAtDebugLevel(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains(static::ANCHORED_PATTERN));
        $excludedPathMatcher = $this->createExcludedPathMatcher([static::ANCHORED_PATTERN], $loggerMock);

        // Act
        $isExcludedPath = $excludedPathMatcher->isExcludedPath((new GlueRequestTransfer())->setPath('/excluded-resource/item'));

        // Assert
        $this->assertTrue($isExcludedPath);
    }

    public function testGivenAProviderPluginWhenSeveralConsumersAreCreatedThenEachConsumerExcludesTheProvidedPath(): void
    {
        // Arrange
        $pluginMock = $this->createMock(AccessTokenValidationExcludedPathPatternProviderPluginInterface::class);
        $pluginMock->method('getExcludedPathPatterns')->willReturn([static::ANCHORED_PATTERN]);
        $this->tester->setDependency(
            OauthApiDependencyProvider::PLUGINS_ACCESS_TOKEN_VALIDATION_EXCLUDED_PATH_PATTERN_PROVIDER,
            [$pluginMock],
        );
        $oauthApiFactory = new OauthApiFactory();
        $oauthApiFactory->setConfig(new OauthApiConfig());
        $glueRequestTransfer = (new GlueRequestTransfer())
            ->setPath('/excluded-resource/item')
            ->setMeta(['authorization' => [0 => 'Bearer invalid-token']]);

        // Act
        $isAccessTokenValid = $oauthApiFactory->createAccessTokenValidator()->validate($glueRequestTransfer)->getIsValid();
        $isCustomerRequestValid = $oauthApiFactory->createCustomerRequestValidator()->validate($glueRequestTransfer)->getIsValid();
        $builtGlueRequestTransfer = $oauthApiFactory->createCustomerRequestBuilder()->buildRequest($glueRequestTransfer);

        // Assert
        $this->assertTrue($isAccessTokenValid);
        $this->assertTrue($isCustomerRequestValid);
        $this->assertNull($builtGlueRequestTransfer->getRequestCustomer());
    }

    /**
     * @param list<string> $excludedPathPatterns
     */
    protected function createExcludedPathMatcher(array $excludedPathPatterns, LoggerInterface $logger): ExcludedPathMatcher
    {
        $oauthApiConfig = $this->tester->mockConfigMethod('getAccessTokenValidationExcludedPathPatterns', $excludedPathPatterns);

        return new class ($oauthApiConfig, [], $logger) extends ExcludedPathMatcher
        {
            public function __construct(OauthApiConfig $oauthApiConfig, array $plugins, protected LoggerInterface $logger)
            {
                parent::__construct($oauthApiConfig, $plugins);
            }

            protected function getLogger(?LoggerConfigInterface $loggerConfig = null): LoggerInterface
            {
                return $this->logger;
            }
        };
    }

    protected function createLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }
}
