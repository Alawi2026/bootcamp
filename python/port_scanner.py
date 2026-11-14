import socket
import time
import errno
import ipaddress
from concurrent.futures import ThreadPoolExecutor, as_completed

MAX_WORKERS = 50
START_PORT = 1
END_PORT = 1024
TIMEOUT = 0.5

CONNECTION_REFUSED_CODES = {errno.ECONNREFUSED, 10061}

def scan_port(target_ip, port):

    try:
        ip = ipaddress.ip_address(target_ip)

        if ip.version == 4:
            family = socket.AF_INET
            address = (str(ip), port)
        else:
            family = socket.AF_INET6
            address = (str(ip), port, 0, 0)

        with socket.socket(family, socket.SOCK_STREAM) as sock:
            sock.settimeout(TIMEOUT)
            result = sock.connect_ex(address)

    except socket.timeout:
        return port, "unconfirmed"

    except OSError as error:

        error_codes = {
            error.errno,
            getattr(error, "winerror", None)
        }

        if error_codes.intersection(CONNECTION_REFUSED_CODES):
            return port, "closed"

        return port, "unconfirmed"


    if result == 0:
        return port, "open"

    elif result in CONNECTION_REFUSED_CODES:
        return port, "closed"

    else:
        return port, "unconfirmed"



def format_port_ranges(ports):

    ports = sorted(set(ports))

    if not ports:
        return "None"

    ranges = []
    start = previous = ports[0]

    for port in ports[1:]:

        if port == previous + 1:
            previous = port
            continue

        if start == previous:
            ranges.append(str(start))
        else:
            ranges.append(f"{start}-{previous}")

        start = previous = port

    if start == previous:
        ranges.append(str(start))
    else:
        ranges.append(f"{start}-{previous}")

    return ", ".join(ranges)



def main():

    target_ip = input("Enter Target IP: ").strip()


    try:
        ipaddress.ip_address(target_ip)

    except ValueError:
        print("Invalid IP address!")
        return

    open_ports = []
    closed_ports = []
    unknown_ports = []

    start_time = time.perf_counter()

    print("\nStarting Port Scanner...")
    print(f"Target IP: {target_ip}")
    print(f"Scanning ports {START_PORT} to {END_PORT}")
    print("-" * 50)


    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:

        futures = {
            executor.submit(scan_port, target_ip, port): port
            for port in range(START_PORT, END_PORT + 1)
        }

        for future in as_completed(futures):

            port = futures[future]

            try:
                scanned_port, status = future.result()

            except Exception as error:
                unknown_ports.append(port)
                print(f"[!] Port {port}: Scan error ({error})")
                continue

            if status == "open":

                open_ports.append(scanned_port)

                print(f"[+] Port {scanned_port} is OPEN", flush=True)

            elif status == "closed":

                closed_ports.append(scanned_port)

            else:

                unknown_ports.append(scanned_port)

    end_time = time.perf_counter()
    elapsed_time = end_time - start_time

    print("\n" + "=" * 50)
    print("SCAN COMPLETED")
    print("=" * 50)

    print(f"Target IP: {target_ip}")
    print(f"Total Ports Scanned: {END_PORT - START_PORT + 1}")

    print(f"\nOpen Ports: {len(open_ports)}")
    print(format_port_ranges(open_ports))
    print(f"\nClosed Ports: {len(closed_ports)}")
    print(format_port_ranges(closed_ports))

    print(f"\nUnconfirmed Ports: {len(unknown_ports)}")
    print(format_port_ranges(unknown_ports))

    print(f"\nTime Taken: {elapsed_time:.2f} seconds")

if __name__ == "__main__":
    main()