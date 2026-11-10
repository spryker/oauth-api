<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthApi\Processor\Validator;

use Generated\Shared\Transfer\GlueErrorTransfer;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueRequestValidationTransfer;
use Spryker\Glue\OauthApi\OauthApiConfig;
use Spryker\Glue\OauthApi\Processor\Matcher\ExcludedPathMatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class CustomerRequestValidator implements CustomerRequestValidatorInterface
{
    public function __construct(protected ExcludedPathMatcherInterface $excludedPathMatcher)
    {
    }

    public function validate(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        $glueRequestValidationTransfer = new GlueRequestValidationTransfer();

        if ($this->excludedPathMatcher->isExcludedPath($glueRequestTransfer)) {
            return $glueRequestValidationTransfer->setIsValid(true);
        }

        if ($this->headerAuthorizationExist($glueRequestTransfer) && $glueRequestTransfer->getRequestCustomer() === null) {
            return $glueRequestValidationTransfer
                ->setIsValid(false)
                ->setStatus(Response::HTTP_BAD_REQUEST)
                ->addError(
                    (new GlueErrorTransfer())
                        ->setStatus(Response::HTTP_BAD_REQUEST)
                        ->setCode(OauthApiConfig::RESPONSE_CODE_ACCESS_CODE_INVALID)
                        ->setMessage(OauthApiConfig::RESPONSE_DETAIL_INVALID_ACCESS_TOKEN),
                );
        }

        return $glueRequestValidationTransfer->setIsValid(true);
    }

    protected function headerAuthorizationExist(GlueRequestTransfer $glueRequestTransfer): bool
    {
        return $glueRequestTransfer->getMeta() &&
            array_key_exists(OauthApiConfig::HEADER_AUTHORIZATION, $glueRequestTransfer->getMeta());
    }
}
