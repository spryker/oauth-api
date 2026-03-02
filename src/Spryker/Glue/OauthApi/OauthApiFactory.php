<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthApi;

use Spryker\Glue\Kernel\AbstractStorefrontApiFactory;
use Spryker\Glue\OauthApi\Dependency\Client\OauthApiToAuthenticationClientInterface;
use Spryker\Glue\OauthApi\Dependency\Client\OauthApiToOauthClientInterface;
use Spryker\Glue\OauthApi\Dependency\Service\OauthApiToOauthServiceInterface;
use Spryker\Glue\OauthApi\Dependency\Service\OauthApiToUtilEncodingServiceInterface;
use Spryker\Glue\OauthApi\Processor\Extractor\AccessTokenExtractor;
use Spryker\Glue\OauthApi\Processor\Extractor\AccessTokenExtractorInterface;
use Spryker\Glue\OauthApi\Processor\RequestBuilder\CustomerRequestBuilder;
use Spryker\Glue\OauthApi\Processor\RequestBuilder\CustomerRequestBuilderInterface;
use Spryker\Glue\OauthApi\Processor\Validator\AccessTokenValidator;
use Spryker\Glue\OauthApi\Processor\Validator\AccessTokenValidatorInterface;
use Spryker\Glue\OauthApi\Processor\Validator\CustomerRequestValidator;
use Spryker\Glue\OauthApi\Processor\Validator\CustomerRequestValidatorInterface;

/**
 * @method \Spryker\Glue\OauthApi\OauthApiConfig getConfig()
 */
class OauthApiFactory extends AbstractStorefrontApiFactory
{
    public function createAccessTokenValidator(): AccessTokenValidatorInterface
    {
        return new AccessTokenValidator(
            $this->getOauthClient(),
            $this->createAccessTokenExtractor(),
        );
    }

    public function getAuthenticationClient(): OauthApiToAuthenticationClientInterface
    {
        return $this->getProvidedDependency(OauthApiDependencyProvider::CLIENT_AUTHENTICATION);
    }

    public function getOauthService(): OauthApiToOauthServiceInterface
    {
        return $this->getProvidedDependency(OauthApiDependencyProvider::SERVICE_OAUTH);
    }

    public function getOauthClient(): OauthApiToOauthClientInterface
    {
        return $this->getProvidedDependency(OauthApiDependencyProvider::CLIENT_OAUTH);
    }

    public function getUtilEncodingService(): OauthApiToUtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(OauthApiDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    public function createAccessTokenExtractor(): AccessTokenExtractorInterface
    {
        return new AccessTokenExtractor();
    }

    public function createCustomerRequestBuilder(): CustomerRequestBuilderInterface
    {
        return new CustomerRequestBuilder(
            $this->getOauthService(),
            $this->getUtilEncodingService(),
            $this->createAccessTokenExtractor(),
        );
    }

    public function createCustomerRequestValidator(): CustomerRequestValidatorInterface
    {
        return new CustomerRequestValidator();
    }
}
