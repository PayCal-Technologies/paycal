#!/usr/bin/env python3
"""Bounded HTTPS probes using repository owned expectations, without notifications."""
import concurrent.futures
import json
import subprocess
import tempfile
from pathlib import Path

# Development was intentionally disabled by the owner on October 21, 2026.
PROBES = (
    ('prod-home', 'https://paycal.app/', 302, 'https://paycal.app/auth/', 'production'),
    ('prod-css', 'https://paycal.app/css/', 200, None, 'production'),
    ('prod-health', 'https://paycal.app/api/v1/health', 200, None, 'production'),
    ('dev-home', 'https://dev.paycal.app/', 503, None, 'planned-maintenance'),
)

def probe(spec):
    name, url, expected, location, scope = spec
    record = dict(endpoint=name, url=url, expected_status=expected, scope=scope,
                  http_code=None, result='CURL_ERROR')
    with tempfile.TemporaryDirectory(prefix='paycal-probe-') as folder:
        headers, body = Path(folder)/'headers', Path(folder)/'body'
        try:
            run = subprocess.run(['curl', '--silent', '--show-error', '--proto', '=https',
                                  '--connect-timeout', '8', '--max-time', '20',
                                  '--max-filesize', '65536', '--dump-header', str(headers),
                                  '--output', str(body), '--write-out', '%{http_code}',
                                  *(['--head'] if name == 'prod-css' else []), url],
                                 capture_output=True, text=True, timeout=22, check=False)
            if run.returncode or not run.stdout.isdigit():
                return record
            record['http_code'] = int(run.stdout)
            if record['http_code'] != expected:
                record['result'] = 'UNEXPECTED_STATUS'
                return record
            if location:
                values = [line.split(':', 1)[1].strip() for line in headers.read_text().splitlines()
                          if line.lower().startswith('location:')]
                if values != [location]:
                    record['result'] = 'UNEXPECTED_REDIRECT'
                    return record
            if name == 'prod-health':
                payload = json.loads(body.read_text())
                if not isinstance(payload, dict) or payload.get('status') != 'ok' or payload.get('data', {}).get('status') != 'ok':
                    record['result'] = 'UNHEALTHY_BODY'
                    return record
            record['result'] = 'EXPECTED_MAINTENANCE' if scope == 'planned-maintenance' else 'OK'
        except (OSError, ValueError, AttributeError, subprocess.TimeoutExpired):
            record['result'] = 'PROBE_ERROR'
    return record

def collect():
    with concurrent.futures.ThreadPoolExecutor(max_workers=4) as workers:
        records = list(workers.map(probe, PROBES))
    healthy = all(r['result'] in ('OK', 'EXPECTED_MAINTENANCE') for r in records)
    return dict(conclusion='HEALTHY' if healthy else 'DEGRADED', probes=records)

if __name__ == '__main__':
    print(json.dumps(collect(), separators=(',', ':')))
