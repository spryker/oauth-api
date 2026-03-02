<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthApi\Dependency\Service;

use Generated\Shared\Transfer\OauthAccessTokenDataTransfer;

interface OauthApiToOauthServiceInterface
{
    public function extractAccessTokenData(string $accessToken): OauthAccessTokenDataTransfer;
}
