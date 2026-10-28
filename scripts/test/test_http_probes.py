import importlib.util
from pathlib import Path
from types import SimpleNamespace
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('probes', Path(__file__).parents[1]/'paycal-http-probes.py')
probes = importlib.util.module_from_spec(spec)
spec.loader.exec_module(probes)

class ProbeTests(unittest.TestCase):
    def run_probe(self, target, code, headers='', body=''):
        def curl(args, **kwargs):
            self.assertNotIn('-k', args)
            self.assertNotIn('--insecure', args)
            Path(args[args.index('--dump-header')+1]).write_text(headers)
            Path(args[args.index('--output')+1]).write_text(body)
            return SimpleNamespace(returncode=0, stdout=str(code))
        with patch.object(probes.subprocess, 'run', side_effect=curl):
            return probes.probe(probes.PROBES[target])
    def test_redirect_requires_exact_auth_destination(self):
        self.assertEqual(self.run_probe(0,302,'Location: https://paycal.app/auth/\r\n')['result'],'OK')
        self.assertEqual(self.run_probe(0,302,'Location: https://example.com/\r\n')['result'],'UNEXPECTED_REDIRECT')
    def test_health_requires_status_and_payload(self):
        self.assertEqual(self.run_probe(2,200,body='{"status":"error"}')['result'],'UNHEALTHY_BODY')
        self.assertEqual(self.run_probe(2,200,body='{"status":"ok","data":{"status":"ok"}}')['result'],'OK')
        self.assertEqual(self.run_probe(2,503)['result'],'UNEXPECTED_STATUS')
    def test_development_expectation_is_explicit(self):
        self.assertEqual(self.run_probe(3,503)['result'],'EXPECTED_MAINTENANCE')
        self.assertEqual(self.run_probe(3,200)['result'],'UNEXPECTED_STATUS')
    def test_transport_failure_is_not_healthy(self):
        with patch.object(probes.subprocess,'run',return_value=SimpleNamespace(returncode=60,stdout='000')):
            self.assertEqual(probes.probe(probes.PROBES[0])['result'],'CURL_ERROR')

if __name__ == '__main__': unittest.main()
