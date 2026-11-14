import re
import ipaddress
from collections import Counter


PATTERNS = {

    "SQL Injection": re.compile(
        r"\bUNION\s+(?:ALL\s+)?SELECT\b|"
        r"(?:'|%27)\s*(?:OR|AND)\s+['\"]?\d+['\"]?\s*=\s*['\"]?\d+",
        re.IGNORECASE
    ),

    "Path Traversal": re.compile(
        r"(?:\.\.[/\\]|%2e%2e(?:%2f|%5c)|"
        r"%2e%2e[/\\]|\.{2}%2f|\.{2}%5c)",
        re.IGNORECASE
    )

}


def extract_ip(log_line):

    ip_address = log_line.split(maxsplit=1)[0]

    try:
        ipaddress.ip_address(ip_address)
        return ip_address

    except ValueError:
        return "Unknown"


def analyze_logs(file_path):

    attack_counts = Counter()
    total_attacks = 0
    total_lines = 0

    print("\nStarting Log Analysis...")
    print("-" * 50)

    try:
        with open(file_path, "r", encoding="utf-8", errors="replace") as log_file:

            for line_number, line in enumerate(log_file, start=1):

                total_lines += 1

                attacker_ip = extract_ip(line)

                for attack_type, pattern in PATTERNS.items():

                    if re.search(pattern, line):

                        total_attacks += 1
                        attack_counts[attack_type] += 1

                        print("\n[!] SECURITY ALERT!")
                        print(f"Attack Type: {attack_type}")
                        print(f"Line Number: {line_number}")
                        print(f"Attacker IP: {attacker_ip}")
                        print("-" * 50)

        print("\n" + "=" * 50)
        print("SECURITY ANALYSIS REPORT")
        print("=" * 50)

        print(f"Total Log Lines: {total_lines}")
        print(f"Total Attacks Detected: {total_attacks}")

        print("\nAttacks By Type:")

        if attack_counts:

            for attack_type, count in attack_counts.items():
                print(f"{attack_type}: {count}")

        else:
            print("No suspicious activities detected.")

        print("=" * 50)
        print("Log Analysis Completed!")

    except FileNotFoundError:
        print("[ERROR] Log file not found!")

    except PermissionError:
        print("[ERROR] Permission denied!")

    except OSError as error:
        print(f"[ERROR] Unable to read log file: {error}")


def main():

    print("Automated Log Analyzer")
    print("-" * 50)

    file_path = input("Enter Log File Path: ").strip()

    if not file_path:
        print("[ERROR] Please enter a valid file path!")
        return

    analyze_logs(file_path)


if __name__ == "__main__":
    main()