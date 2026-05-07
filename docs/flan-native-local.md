# Flan Native Local Integration

Purpose:
- Run Cloudflare Flan workflow locally without Docker or Kubernetes.

Why this file exists:
- Documents PayCal's local-first scanner path and GCS upload wiring.

## Entry Point

Run:

```bash
bash scripts/flan-native-scan.sh
```

The script:
1. Reads targets from tools/flan-native/shared/ips.txt
2. Runs Nmap + vulners NSE per target
3. Writes XML artifacts under tools/flan-native/shared/xml_files/<timestamp>/
4. Generates a Flan report under tools/flan-native/shared/reports/
5. Uploads report + XML artifacts to GCS

## Required Local Dependencies

- nmap
- python3
- curl

Install nmap on macOS if needed:

```bash
brew install nmap
```

## Required Environment for GCS Upload

Uses existing PayCal env variables by default:
- GCS_SOC2_KEY_FILE -> service account key path
- GCS_SOC2_BUCKET -> bucket name (default paycal-soc2-evidence)
- GCS_SOC2_ENVIRONMENT -> environment label (for object prefix)

Optional Flan-specific overrides:
- FLAN_GCS_UPLOAD=true|false (default true)
- FLAN_GCS_BUCKET=<bucket>
- FLAN_GCS_ENVIRONMENT=<env>
- FLAN_GCS_PREFIX_BASE=security/flan
- FLAN_REPORT_FORMAT=tex|md|html|json (default html)
- FLAN_IPS_FILE=<path>

## Target File Format

Edit tools/flan-native/shared/ips.txt and provide one target per line:
- IP address
- hostname
- CIDR block

Lines beginning with # are ignored.
