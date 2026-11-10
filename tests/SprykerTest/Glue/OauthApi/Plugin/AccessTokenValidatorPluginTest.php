<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\OauthApi\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueRequestValidationTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationResponseTransfer;
use Spryker\Glue\OauthApi\Dependency\Client\OauthApiToOauthClientInterface;
use Spryker\Glue\OauthApi\OauthApiConfig;
use Spryker\Glue\OauthApi\OauthApiDependencyProvider;
use Spryker\Glue\OauthApi\OauthApiFactory;
use Spryker\Glue\OauthApi\Plugin\AccessTokenValidatorPlugin;
use Spryker\Glue\OauthExtension\Dependency\Plugin\AccessTokenValidationExcludedPathPatternProviderPluginInterface;
use Spryker\Service\Container\Container;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group OauthApi
 * @group Plugin
 * @group AccessTokenValidatorPluginTest
 * Add your own group annotations below this line
 */
class AccessTokenValidatorPluginTest extends Unit
{
    /**
     * @var string
     */
    public const HTTP_AUTHORIZATION = 'HTTP_AUTHORIZATION';

    protected const string EXCLUDED_PATH_PATTERN = '^/excluded-resource/';

    protected const string PLUGIN_EXCLUDED_PATH_PATTERN = '^/plugin-excluded-resource/';

    /**
     * @var \SprykerTest\Glue\OauthApi\OauthApiTester
     */
    protected $tester;

    public function testValidatorIsNotProtected(): void
    {
        //Act
        $result = (new AccessTokenValidatorPlugin())->validate(new GlueRequestTransfer());

        //Assert
        $this->assertTrue($result->getIsValid());
    }

    public function testValidatorEmptyAuth(): void
    {
        //Arrange
        $glueRequestTransfer = new GlueRequestTransfer();

        //Act
        $result = (new AccessTokenValidatorPlugin())->validate($glueRequestTransfer);

        //Assert
        $this->assertInstanceOf(GlueRequestValidationTransfer::class, $result);

        $this->assertTrue($result->getIsValid());
    }

    public function testValidatorTokenNotValid(): void
    {
        //Arrange
        $this->tester->setDependency(OauthApiDependencyProvider::CLIENT_OAUTH, function (Container $container) {
            $oauthFacade = $this->createMock(OauthApiToOauthClientInterface::class);
            $oauthFacade->method('validateOauthAccessToken')
                ->willReturn((new OauthAccessTokenValidationResponseTransfer())->setIsValid(false));

            return $oauthFacade;
        });
        $glueRequestTransfer = (new GlueRequestTransfer())
            ->setMeta(['authorization' => [0 => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9']]);

        //Act
        $result = (new AccessTokenValidatorPlugin())->validate($glueRequestTransfer);

        //Assert
        $this->assertFalse($result->getIsValid());

        $glueErrorTransfer = $result->getErrors()->getArrayCopy()[0];
        $this->assertSame(OauthApiConfig::RESPONSE_CODE_ACCESS_CODE_INVALID, $glueErrorTransfer->getCode());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $glueErrorTransfer->getStatus());
        $this->assertSame(OauthApiConfig::RESPONSE_DETAIL_INVALID_ACCESS_TOKEN, $glueErrorTransfer->getMessage());
    }

    public function testValidatorTokenValid(): void
    {
        //Arrange
        $this->tester->setDependency(OauthApiDependencyProvider::CLIENT_OAUTH, function (Container $container) {
            $oauthFacade = $this->createMock(OauthApiToOauthClientInterface::class);
            $oauthFacade->method('validateOauthAccessToken')
                ->willReturn((new OauthAccessTokenValidationResponseTransfer())->setIsValid(true));

            return $oauthFacade;
        });
        $glueRequestTransfer = (new GlueRequestTransfer())
            ->setMeta(['authorization' => [0 => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9']]);

        //Act
        $result = (new AccessTokenValidatorPlugin())->validate($glueRequestTransfer);

        //Assert
        $this->assertTrue($result->getIsValid());
    }

    public function testGivenAnExcludedPathWhenTheTokenIsInvalidThenTheRequestIsValid(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/excluded-resource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths([static::EXCLUDED_PATH_PATTERN])->validate($glueRequestTransfer);

        // Assert
        $this->assertTrue($glueRequestValidationTransfer->getIsValid());
        $this->assertCount(0, $glueRequestValidationTransfer->getErrors());
    }

    public function testGivenAPathOutsideTheExcludedPatternsWhenTheTokenIsInvalidThenTheRequestIsUnauthorized(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/carts/excluded-resource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths([static::EXCLUDED_PATH_PATTERN])->validate($glueRequestTransfer);

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $glueRequestValidationTransfer->getStatus());
    }

    public function testGivenAPercentEncodedExcludedPathWhenTheTokenIsInvalidThenTheRequestIsValid(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/excluded%2Dresource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths([static::EXCLUDED_PATH_PATTERN])->validate($glueRequestTransfer);

        // Assert
        $this->assertTrue($glueRequestValidationTransfer->getIsValid());
    }

    public function testGivenTheModuleDefaultConfigWhenAnInvalidTokenIsSentToADelegatedAccessPathThenTheRequestIsUnauthorized(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/agentic-commerce/principal');
        $oauthApiFactory = new OauthApiFactory();
        $oauthApiFactory->setConfig(new OauthApiConfig());
        $accessTokenValidatorPlugin = new AccessTokenValidatorPlugin();
        $accessTokenValidatorPlugin->setFactory($oauthApiFactory);

        // Act
        $glueRequestValidationTransfer = $accessTokenValidatorPlugin->validate($glueRequestTransfer);

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $glueRequestValidationTransfer->getStatus());
    }

    public function testGivenAPluginProvidedPatternWhenTheTokenIsInvalidOnAMatchingPathThenTheRequestIsValid(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $this->mockExcludedPathPatternProviderPlugins([static::PLUGIN_EXCLUDED_PATH_PATTERN]);
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/plugin-excluded-resource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths([])->validate($glueRequestTransfer);

        // Assert
        $this->assertTrue($glueRequestValidationTransfer->getIsValid());
    }

    public function testGivenConfigAndPluginPatternsWhenTheTokenIsInvalidOnAConfigPathThenTheRequestIsValid(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $this->mockExcludedPathPatternProviderPlugins([static::PLUGIN_EXCLUDED_PATH_PATTERN]);
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/excluded-resource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths([static::EXCLUDED_PATH_PATTERN])->validate($glueRequestTransfer);

        // Assert
        $this->assertTrue($glueRequestValidationTransfer->getIsValid());
    }

    public function testGivenAPluginProvidedPatternWhenTheTokenIsInvalidOnAnotherPathThenTheRequestIsUnauthorized(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $this->mockExcludedPathPatternProviderPlugins([static::PLUGIN_EXCLUDED_PATH_PATTERN]);
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/carts/plugin-excluded-resource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths([])->validate($glueRequestTransfer);

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $glueRequestValidationTransfer->getStatus());
    }

    public function testGivenAnUnanchoredExcludedPatternWhenTheTokenIsInvalidOnAPathContainingItThenTheRequestIsUnauthorized(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/carts/excluded-resource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths(['/excluded-resource/'])->validate($glueRequestTransfer);

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $glueRequestValidationTransfer->getStatus());
    }

    public function testGivenAMalformedExcludedPatternWhenTheTokenIsInvalidThenTheRequestIsUnauthorized(): void
    {
        // Arrange
        $this->mockInvalidatingOauthClient();
        $glueRequestTransfer = $this->createGlueRequestWithBearerToken('/excluded-resource/item');

        // Act
        $glueRequestValidationTransfer = $this->createAccessTokenValidatorPluginExcludingPaths(['^/excluded-resource/('])->validate($glueRequestTransfer);

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $glueRequestValidationTransfer->getStatus());
    }

    protected function mockInvalidatingOauthClient(): void
    {
        $this->tester->setDependency(OauthApiDependencyProvider::CLIENT_OAUTH, function (Container $container) {
            $oauthClientMock = $this->createMock(OauthApiToOauthClientInterface::class);
            $oauthClientMock->method('validateOauthAccessToken')
                ->willReturn((new OauthAccessTokenValidationResponseTransfer())->setIsValid(false));

            return $oauthClientMock;
        });
    }

    /**
     * @param list<string> $excludedPathPatterns
     */
    protected function mockExcludedPathPatternProviderPlugins(array $excludedPathPatterns): void
    {
        $excludedPathPatternProviderPluginMock = $this->createMock(AccessTokenValidationExcludedPathPatternProviderPluginInterface::class);
        $excludedPathPatternProviderPluginMock->method('getExcludedPathPatterns')->willReturn($excludedPathPatterns);

        $this->tester->setDependency(
            OauthApiDependencyProvider::PLUGINS_ACCESS_TOKEN_VALIDATION_EXCLUDED_PATH_PATTERN_PROVIDER,
            [$excludedPathPatternProviderPluginMock],
        );
    }

    /**
     * @param list<string> $excludedPathPatterns
     */
    protected function createAccessTokenValidatorPluginExcludingPaths(array $excludedPathPatterns): AccessTokenValidatorPlugin
    {
        /** @var \Spryker\Glue\OauthApi\OauthApiConfig $oauthApiConfig */
        $oauthApiConfig = $this->tester->mockConfigMethod('getAccessTokenValidationExcludedPathPatterns', $excludedPathPatterns);
        $oauthApiFactory = new OauthApiFactory();
        $oauthApiFactory->setConfig($oauthApiConfig);
        $accessTokenValidatorPlugin = new AccessTokenValidatorPlugin();
        $accessTokenValidatorPlugin->setFactory($oauthApiFactory);

        return $accessTokenValidatorPlugin;
    }

    protected function createGlueRequestWithBearerToken(string $path): GlueRequestTransfer
    {
        return (new GlueRequestTransfer())
            ->setPath($path)
            ->setMeta(['authorization' => [0 => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9']]);
    }
}
