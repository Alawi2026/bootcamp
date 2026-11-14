import hashlib
import time

CHUNK_SIZE = 4096
CHECK_INTERVAL = 3


def calculate_sha256(file_path):
    """Calculate the SHA-256 hash of a file using binary chunks."""
    sha256 = hashlib.sha256()

    with open(file_path, "rb") as file:
        while True:
            chunk = file.read(CHUNK_SIZE)
            if not chunk:
                break
            sha256.update(chunk)

    return sha256.hexdigest()


def monitor_file(file_path):
    """Monitor a file and alert when its hash differs from the baseline."""
    try:
        baseline_hash = calculate_sha256(file_path)
    except OSError as error:
        print(f"[ERROR] Could not create the baseline hash: {error}")
        return

    print("=" * 55)
    print("       FILE INTEGRITY MONITORING (FIM)")
    print("=" * 55)
    print(f"File being monitored: {file_path}")
    print(f"Baseline SHA-256: {baseline_hash}")
    print(f"Check interval: {CHECK_INTERVAL} seconds")
    print("Monitoring started... Press Ctrl+C to stop.\n")

    last_state = "safe"

    try:
        while True:
            try:
                current_hash = calculate_sha256(file_path)

                if current_hash != baseline_hash:
                    if last_state != "modified":
                        print("\n[CRITICAL ALERT] File modification detected!")
                        print(f"Baseline hash: {baseline_hash}")
                        print(f"Current hash:  {current_hash}")

                    last_state = "modified"

                else:
                    if last_state == "modified":
                        print("[INFO] File hash matches the original baseline again.")
                    elif last_state == "unavailable":
                        print("[INFO] File is accessible again and matches the baseline.")

                    last_state = "safe"

            except OSError as error:
                if last_state != "unavailable":
                    print(
                        "\n[CRITICAL ALERT] File cannot be read or is unavailable: "
                        f"{error}"
                    )

                last_state = "unavailable"

            time.sleep(CHECK_INTERVAL)

    except KeyboardInterrupt:
        print("\nMonitoring stopped by user.")


def main():
    file_path = input("Enter the path of the file to monitor: ").strip()

    if not file_path:
        print("[ERROR] No file path was entered.")
        return

    monitor_file(file_path)


if __name__ == "__main__":
    main()