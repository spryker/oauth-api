<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace Spryker\Glue\OauthApi\Processor\Matcher;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Spryker\Glue\OauthApi\OauthApiConfig;
use Spryker\Glue\OauthExtension\Dependency\Plugin\AccessTokenValidationExcludedPathPatternProviderPluginInterface;
use Spryker\Shared\Log\LoggerTrait;

class ExcludedPathMatcher implements ExcludedPathMatcherInterface
{
    use LoggerTrait;

    protected const string PATTERN_ANCHOR = '^';

    /**
     * @var list<string>|null
     */
    protected ?array $excludedPathPatterns = null;

    /**
     * @param list<\Spryker\Glue\OauthExtension\Dependency\Plugin\AccessTokenValidationExcludedPathPatternProviderPluginInterface> $accessTokenValidationExcludedPathPatternProviderPlugins
     */
    public function __construct(
        protected OauthApiConfig $oauthApiConfig,
        protected array $accessTokenValidationExcludedPathPatternProviderPlugins,
    ) {
    }

    public function isExcludedPath(GlueRequestTransfer $glueRequestTransfer): bool
    {
        $path = rawurldecode((string)$glueRequestTransfer->getPath());

        foreach ($this->getExcludedPathPatterns() as $excludedPathPattern) {
            if (preg_match($this->wrapPattern($excludedPathPattern), $path) === 1) {
                $this->getLogger()->debug(sprintf('Path "%s" is excluded from access token validation by pattern "%s".', $path, $excludedPathPattern));

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function getExcludedPathPatterns(): array
    {
        if ($this->excludedPathPatterns !== null) {
            return $this->excludedPathPatterns;
        }

        $excludedPathPatterns = $this->oauthApiConfig->getAccessTokenValidationExcludedPathPatterns();

        foreach ($this->accessTokenValidationExcludedPathPatternProviderPlugins as $accessTokenValidationExcludedPathPatternProviderPlugin) {
            $excludedPathPatterns = array_merge($excludedPathPatterns, $this->getProvidedPatterns($accessTokenValidationExcludedPathPatternProviderPlugin));
        }

        $this->excludedPathPatterns = $this->filterUsablePatterns($excludedPathPatterns);

        return $this->excludedPathPatterns;
    }

    /**
     * @return list<string>
     */
    protected function getProvidedPatterns(
        AccessTokenValidationExcludedPathPatternProviderPluginInterface $accessTokenValidationExcludedPathPatternProviderPlugin,
    ): array {
        return $accessTokenValidationExcludedPathPatternProviderPlugin->getExcludedPathPatterns();
    }

    /**
     * @param list<string> $excludedPathPatterns
     *
     * @return list<string>
     */
    protected function filterUsablePatterns(array $excludedPathPatterns): array
    {
        $usableExcludedPathPatterns = [];

        foreach ($excludedPathPatterns as $excludedPathPattern) {
            if (!str_starts_with($excludedPathPattern, static::PATTERN_ANCHOR)) {
                $this->getLogger()->warning(sprintf('Excluded path pattern "%s" is ignored because it does not start with "%s".', $excludedPathPattern, static::PATTERN_ANCHOR));

                continue;
            }

            if (!$this->isValidRegularExpression($excludedPathPattern)) {
                $this->getLogger()->error(sprintf('Excluded path pattern "%s" is ignored because it is not a valid regular expression: %s.', $excludedPathPattern, preg_last_error_msg()));

                continue;
            }

            $usableExcludedPathPatterns[] = $excludedPathPattern;
        }

        return $usableExcludedPathPatterns;
    }

    protected function isValidRegularExpression(string $excludedPathPattern): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match($this->wrapPattern($excludedPathPattern), '') !== false;
        } finally {
            restore_error_handler();
        }
    }

    protected function wrapPattern(string $excludedPathPattern): string
    {
        return '{^(?:' . $excludedPathPattern . ')}s';
    }
}
