# Dupay production release checklist

Before running `production-deploy.sh`, set production secrets outside Git and verify that MySQL is reachable only from the application host/private network.

The release script enables maintenance mode, creates a compressed MySQL backup, installs locked production dependencies, runs migrations with `--force`, rebuilds Laravel caches, restarts workers, and disables maintenance mode only after those steps succeed.

After release, verify `/health-check`, password login, OTP delivery, OTP verification, token issuance, lockout/revocation, browser login, admin unlock, and logout. Monitor `storage/logs/laravel.log`, Nginx, PHP-FPM, SSH, Fail2ban, queue, and mail logs.

Configure UFW to allow only SSH from approved addresses and HTTPS, disable root/password SSH login, enable Fail2ban, install the Nginx TLS configuration, and test encrypted backup restoration before the first release.
