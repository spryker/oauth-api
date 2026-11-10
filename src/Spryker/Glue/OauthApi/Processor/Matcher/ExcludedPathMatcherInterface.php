<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace Spryker\Glue\OauthApi\Processor\Matcher;

use Generated\Shared\Transfer\GlueRequestTransfer;

interface ExcludedPathMatcherInterface
{
    public function isExcludedPath(GlueRequestTransfer $glueRequestTransfer): bool;
}
