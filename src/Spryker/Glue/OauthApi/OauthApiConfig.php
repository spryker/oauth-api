<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthApi;

use Spryker\Glue\Kernel\AbstractBundleConfig;

class OauthApiConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @uses \Spryker\Glue\GlueApplication\Rest\RequestConstantsInterface::HEADER_AUTHORIZATION
     *
     * @var string
     */
    public const HEADER_AUTHORIZATION = 'authorization';

    /**
     * @api
     *
     * @uses \Spryker\Glue\GlueApplication\Rest\RequestConstantsInterface::ATTRIBUTE_IS_PROTECTED
     *
     * @var string
     */
    public const REQUEST_ATTRIBUTE_IS_PROTECTED = 'is-protected';

    /**
     * @api
     *
     * @var string
     */
    public const RESPONSE_DETAIL_MISSING_ACCESS_TOKEN = 'Missing access token.';

    /**
     * @api
     *
     * @var string
     */
    public const RESPONSE_DETAIL_INVALID_ACCESS_TOKEN = 'Invalid access token.';

    /**
     * @api
     *
     * @var string
     */
    public const RESPONSE_CODE_ACCESS_CODE_INVALID = '001';

    /**
     * @api
     *
     * @var string
     */
    public const RESPONSE_CODE_FORBIDDEN = '002';

    /**
     * @api
     *
     * @var string
     */
    public const RESOURCE_TOKEN = 'token';

    /**
     * Specification:
     * - Returns if conventional response code for OAuth flow is enabled.
     * - If True - forces returning 200 OK.
     * - If False - forces returning 201 CREATED.
     *
     * @api
     *
     * @deprecated Will be removed in next major. Becomes default behavior.
     *
     * @return bool
     */
    public function isConventionalResponseCodeEnabled(): bool
    {
        return false;
    }

    /**
     * Specification:
     * - Returns regular expressions, without delimiters, for request paths whose Authorization header another authenticator validates.
     * - Patterns are matched like a Symfony firewall `pattern`: against the URL-decoded path, with the `s` modifier.
     * - A pattern must start with `^`; a pattern that does not, or that is not a valid regular expression, is ignored and logged.
     * - A matching path gets no storefront token validation and no request customer; every pattern must be covered by another firewall, or the path must be public.
     * - The access token and customer request validators accept a request on a matching path without validating its token.
     * - The customer request builder builds no request customer from the token of a request on a matching path.
     *
     * @api
     *
     * @return list<string>
     */
    public function getAccessTokenValidationExcludedPathPatterns(): array
    {
        return [];
    }
}
