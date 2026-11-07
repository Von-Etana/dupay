<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NotDisposableEmail implements Rule
{
    private static ?array $cachedDomains = null;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $parts = explode('@', strtolower(trim($value)));
        if (count($parts) !== 2) {
            return false;
        }

        $domain = $parts[1];

        return !self::isDisposable($domain);
    }

    /**
     * Check if a domain (or any parent domain) is disposable.
     */
    public static function isDisposable(string $domain): bool
    {
        $domain = strtolower(trim($domain));

        if (self::$cachedDomains === null) {
            $configured = config('disposable_email.domains', []);
            self::$cachedDomains = array_fill_keys(array_map('strtolower', $configured), true);
        }

        // Direct match
        if (isset(self::$cachedDomains[$domain])) {
            return true;
        }

        // Subdomain match (e.g., sub.mailinator.com)
        $domainParts = explode('.', $domain);
        while (count($domainParts) > 1) {
            array_shift($domainParts);
            $parent = implode('.', $domainParts);
            if (isset(self::$cachedDomains[$parent])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Disposable or temporary email addresses are not allowed.';
    }
}
