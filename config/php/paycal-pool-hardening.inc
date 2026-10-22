; Include inside each PayCal pool after its existing process manager settings.
; Requires the complete PHP CVE-2026-109618/109619 patches, not a substitute.
pm.max_requests = 500
security.limit_extensions = .php
clear_env = yes
php_admin_flag[session.upload_progress.enabled] = off
; PayCal uses FormData fields and image data URLs, not PHP file uploads.
php_admin_flag[file_uploads] = off
php_admin_flag[expose_php] = off
php_admin_value[memory_limit] = 128M
php_admin_value[post_max_size] = 1M
php_admin_value[max_input_nesting_level] = 16
php_admin_value[max_input_vars] = 1000
php_admin_value[max_multipart_body_parts] = 1000
